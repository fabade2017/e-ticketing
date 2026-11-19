<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AiInsightsService;
use App\Shared\Reporting\SuiteReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index(Request $request, SuiteReportService $suiteReports)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $drill = $request->input('drill');
        $user = $request->user();
        $module = $this->resolveModule($request, $user, $suiteReports);
        $isAll = $this->allScope($request, $user);
        $scopedUser = $isAll ? null : $user;
        $data = AiInsightsService::worldClassReport($request->input('type', 'SITREP'), $from, $to, $scopedUser);
        $adhoc = $this->adhocStats($from, $to, $scopedUser);
        $drillTickets = null;
        $drillLabel = null;
        if ($drill) {
            [$label, $tickets] = $this->drillThrough($drill, $from, $to, $scopedUser);
            $drillLabel = $label;
            $drillTickets = $tickets;
        }
        $narrative = null;
        $key = config('ai.key');
        if ($key) {
            try {
                $prompt = 'Generate WORLD-CLASS Army SITREP for Nigerian Army E-Ticket. Data: '.json_encode($data['stats']).". Readiness {$data['readiness']}. Adhoc range ".($from ?: 'all').' to '.($to ?: 'now').". Format: 1. Classification & DTG, 2. Executive Summary, 3. Situation (metrics table), 4. Analysis (hotspots, SLA, biometrics), 5. Forecast, 6. Commander's Recommendations. Markdown, 350 words.";
                $res = Http::withToken($key)->timeout(20)->post(config('ai.groq_url'), [
                    'model' => config('ai.model', 'groq/compound-mini'),
                    'messages' => [['role' => 'system', 'content' => 'You are NATO J2/J4 SITREP writer. World-class, concise.'], ['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.35,
                ]);
                if ($res->successful()) {
                    $narrative = $res->json('choices.0.message.content');
                }
            } catch (\Throwable $e) {
            }
        }
        $scopeLabel = $isAll ? 'all' : ($user->hasRole('SuperAdmin') ? 'scoped' : ($user->department_id ? 'department' : ($user->unit ? 'unit' : 'personal')));
        if (! $isAll && $user->hasRole('Soldier') && ! $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support'])) {
            $scopeLabel = 'personal';
        }

        return Inertia::render('Reports/Index', [
            'report' => $data,
            'narrative' => $narrative,
            'canGenerate' => (bool) $key,
            'adhoc' => $adhoc,
            'filters' => $request->only(['from', 'to', 'drill', 'type', 'scope', 'module']),
            'drillLabel' => $drillLabel,
            'drillTickets' => $drillTickets,
            'scope' => $scopeLabel,
            'isAll' => $isAll,
            'canSeeAll' => $this->canSeeAll($user),
            'suiteSections' => $suiteReports->sections($user, $isAll, $module),
            'availableModules' => $suiteReports->availableModules($user),
            'selectedModule' => $module,
            'canSeeNetworkReports' => $user->hasRole('SuperAdmin') || $user->can('view network reports'),
        ]);
    }

    private function adhocStats($from, $to, ?User $user = null): array
    {
        $q = Ticket::query();
        if ($user) {
            $q->visibleTo($user);
        }
        if ($from) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('created_at', '<=', $to);
        }
        $base = clone $q;

        return [
            'total' => (clone $q)->count(),
            'byStatus' => (clone $base)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status')->toArray(),
            'byCategory' => (clone $base)->selectRaw('category, count(*) as c')->groupBy('category')->pluck('c', 'category')->toArray(),
            'byPriority' => (clone $base)->selectRaw('priority, count(*) as c')->groupBy('priority')->pluck('c', 'priority')->toArray(),
            'byDept' => (clone $base)->selectRaw('department_id, count(*) as c')->groupBy('department_id')->get()->map(fn ($r) => ['dept' => Department::find($r->department_id)?->name ?? 'Unassigned', 'dept_id' => $r->department_id, 'count' => $r->c])->toArray(),
            'overdue' => (clone $base)->where('sla_due_at', '<', now())->whereNotIn('status', ['resolved', 'closed'])->count(),
            'urgentOpen' => (clone $base)->where('priority', 'urgent')->whereIn('status', ['open', 'in_progress'])->count(),
            'escalated' => (clone $base)->whereNotNull('escalated_at')->count(),
        ];
    }

    private function drillThrough(string $drill, $from, $to, ?User $user = null): array
    {
        $q = Ticket::with(['user:id,name,rank,service_number', 'department:id,name'])->latest();
        if ($user) {
            $q->visibleTo($user);
        }
        if ($from) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('created_at', '<=', $to);
        }
        $label = $drill;
        if (str_starts_with($drill, 'status:')) {
            $v = substr($drill, 7);
            $q->where('status', $v);
            $label = "Status = $v";
        } elseif (str_starts_with($drill, 'category:')) {
            $v = substr($drill, 9);
            $q->where('category', $v);
            $label = "Category = $v";
        } elseif (str_starts_with($drill, 'priority:')) {
            $v = substr($drill, 9);
            $q->where('priority', $v);
            $label = "Priority = $v";
        } elseif (str_starts_with($drill, 'dept:')) {
            $v = substr($drill, 5);
            $q->where('department_id', $v);
            $label = 'Department = '.(Department::find($v)?->name ?? $v);
        } elseif (str_starts_with($drill, 'unit:')) {
            $v = substr($drill, 5);
            $q->where('unit', $v);
            $label = "Unit = $v";
        } elseif ($drill === 'overdue') {
            $q->where('sla_due_at', '<', now())->whereNotIn('status', ['resolved', 'closed']);
            $label = 'Overdue SLA';
        } elseif ($drill === 'escalated') {
            $q->whereNotNull('escalated_at');
            $label = 'Escalated';
        } elseif ($drill === 'urgentOpen') {
            $q->where('priority', 'urgent')->whereIn('status', ['open', 'in_progress']);
            $label = 'Urgent Open';
        }
        $tickets = $q->limit(50)->get();

        return [$label, $tickets];
    }

    public function download(Request $request, SuiteReportService $suiteReports)
    {
        $user = $request->user();
        $module = $this->resolveModule($request, $user, $suiteReports);
        $isAll = $this->allScope($request, $user);
        $scopedUser = $isAll ? null : $user;
        $data = AiInsightsService::worldClassReport($request->input('type', 'SITREP'), $request->input('from'), $request->input('to'), $scopedUser);
        $content = "NA E-TICKET WORLD-CLASS SITREP\nGenerated: {$data['generated_at']}\nModule: {$module}\nRange: ".($request->input('from') ?: 'all').' to '.($request->input('to') ?: 'now')."\nReadiness: {$data['readiness']}\n\nSTATS:\n".json_encode($data['stats'], JSON_PRETTY_PRINT)."\n\nINSIGHTS:\n".implode("\n", $data['insights']);

        return response($content, 200, ['Content-Type' => 'text/plain', 'Content-Disposition' => 'attachment; filename="NA_SITREP_'.now()->format('Ymd_His').'.txt"']);
    }

    public function downloadWorkbook(Request $request, SuiteReportService $suiteReports)
    {
        $user = $request->user();
        $module = $this->resolveModule($request, $user, $suiteReports);
        $isAll = $this->allScope($request, $user);
        $scopedUser = $isAll ? null : $user;
        $includeTicketData = $user->can('use ticketing') && in_array($module, ['ticketing', 'all'], true);
        $data = $includeTicketData
            ? AiInsightsService::worldClassReport($request->input('type', 'SITREP'), $request->input('from'), $request->input('to'), $scopedUser)
            : [];
        $sections = $suiteReports->sections($user, $isAll, $module);

        $spreadsheet = new Spreadsheet;
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Suite Summary');
        $summary->fromArray(['Module', 'Metric', 'Value'], null, 'A1');
        $row = 2;
        foreach ($sections as $section) {
            foreach ($section['metrics'] as $metric) {
                $summary->fromArray([$section['label'], $metric['label'], $metric['value']], null, "A{$row}");
                $row++;
            }
        }
        $this->styleHeader($summary, 'A1:C1');
        foreach (range('A', 'C') as $column) {
            $summary->getColumnDimension($column)->setWidth(28);
        }

        if ($includeTicketData) {
            $tickets = $spreadsheet->createSheet();
            $tickets->setTitle('Ticket Report');
            $tickets->fromArray(['Metric', 'Value'], null, 'A1');
            $ticketRows = [
                ['Generated', $data['generated_at']],
                ['From', $request->input('from') ?: 'all'],
                ['To', $request->input('to') ?: 'now'],
                ['Readiness', $data['readiness']],
            ];
            foreach ($data['stats'] as $key => $value) {
                $ticketRows[] = [$key, is_array($value) ? (string) json_encode($value) : $value];
            }
            $tickets->fromArray($ticketRows, null, 'A2');
            $this->styleHeader($tickets, 'A1:B1');
            $tickets->getColumnDimension('A')->setWidth(24);
            $tickets->getColumnDimension('B')->setWidth(80);

            $insights = $spreadsheet->createSheet();
            $insights->setTitle('Insights');
            $insights->fromArray(['Insight'], null, 'A1');
            foreach ($data['insights'] as $index => $insight) {
                $insights->fromArray([$insight], null, 'A'.($index + 2));
            }
            $this->styleHeader($insights, 'A1:A1');
            $insights->getColumnDimension('A')->setWidth(120);
        }

        $writer = new Xlsx($spreadsheet);
        $tmp = tempnam(sys_get_temp_dir(), 'suite_report');
        $writer->save($tmp);

        return response()->download($tmp, 'suite_report_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function resolveModule(Request $request, User $user, SuiteReportService $suiteReports): string
    {
        $module = $request->input('module');

        if ($module === null) {
            $module = $this->canSeeNetworkReports($user) ? 'all' : 'ticketing';
        }

        abort_unless(is_string($module) && $suiteReports->canExport($user, $module), 403);

        return $module;
    }

    private function canSeeAll(User $user): bool
    {
        return $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support']) || $user->can('view network reports');
    }

    private function canSeeNetworkReports(User $user): bool
    {
        return $user->hasRole('SuperAdmin') || $user->can('view network reports');
    }

    private function allScope(Request $request, User $user): bool
    {
        $isAll = $request->input('scope') === 'all' && $this->canSeeAll($user);

        if ($this->canSeeNetworkReports($user) && ! $request->has('scope')) {
            $isAll = true;
        }

        return $isAll;
    }

    private function styleHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B3D0B');
    }
}
