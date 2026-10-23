<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Ticket::with(['user', 'assignee', 'department'])->latest();

        // RBAC: default is per-user scoped. ?scope=all allows privileged to see everything.
        $scopeParam = $request->input('scope');
        $isAll = $scopeParam === 'all' && $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support']);
        $anonymousTokens = $this->anonymousTokens($request);
        if (! $isAll) {
            $query->visibleTo($user, false, $anonymousTokens);
        }
        // legacy explicit filters still apply (intersect with visible scope)
        if ($request->scope === 'unit' && $user->unit) {
            $query->where('unit', $user->unit);
        }
        if ($request->scope === 'department' && $user->department_id) {
            $query->where('department_id', $user->department_id);
        }
        // additional filter param ?view=unit|department|mine for toggle UI
        if ($request->input('view') === 'mine') {
            $query = Ticket::with(['user', 'assignee', 'department'])->where(function ($q) use ($user, $anonymousTokens) {
                $q->where('user_id', $user->id);
                if ($anonymousTokens) {
                    $q->orWhereIn('anonymous_token', $anonymousTokens);
                }
            })->latest();
            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->search) {
                $query->where(fn ($q) => $q->where('title', 'like', "%{$request->search}%")->orWhere('ticket_number', 'like', "%{$request->search}%"));
            }
            if ($request->filter === 'overdue') {
                $query->where('sla_due_at', '<', now())->whereNotIn('status', ['resolved', 'closed']);
            }

            return Inertia::render('Tickets/Index', [
                'tickets' => $query->paginate(15)->withQueryString(),
                'filters' => $request->only(['status', 'search', 'filter', 'scope', 'view']),
                'scope' => 'mine',
                'canSeeAll' => $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support']),
                'isAll' => false,
            ]);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$request->search}%")->orWhere('ticket_number', 'like', "%{$request->search}%"));
        }
        if ($request->filter === 'overdue') {
            $query->where('sla_due_at', '<', now())->whereNotIn('status', ['resolved', 'closed']);
        }

        $scopeLabel = $isAll ? 'all' : ($user->hasRole('SuperAdmin') ? 'all' : ($user->department_id ? 'department' : ($user->unit ? 'unit' : 'personal')));
        if (! $isAll && $user->hasAnyRole(['Admin', 'Support']) && ! $user->department_id && ! $user->unit) {
            $scopeLabel = 'personal';
        }
        if (! $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support'])) {
            $scopeLabel = 'personal';
        }

        return Inertia::render('Tickets/Index', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'search', 'filter', 'scope', 'view']),
            'scope' => $scopeLabel,
            'canSeeAll' => $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support']),
            'isAll' => $isAll,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tickets/Create', [
            'departments' => Department::pluck('name', 'id'),
            'defaultDepartmentId' => auth()->user()->department_id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:welfare,logistics,medical,equipment,operations,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'location' => 'nullable|string',
            'country' => 'required|string|max:100',
            'state' => 'required|string',
            'lga' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'offline_created' => 'boolean',
            'is_anonymous' => 'boolean',
        ]);

        $isAnonymous = $request->boolean('is_anonymous');
        $anonymousToken = $isAnonymous ? Str::random(64) : null;
        $validated['user_id'] = $isAnonymous ? null : auth()->id();
        $validated['unit'] = $isAnonymous ? null : auth()->user()->unit;
        $validated['is_anonymous'] = $isAnonymous;
        $validated['anonymous_token'] = $anonymousToken;
        $validated['department_id'] = $request->input('department_id') ?: auth()->user()->department_id;

        $ticket = Ticket::create($validated);

        if ($isAnonymous) {
            $tokens = $this->anonymousTokens($request);
            $tokens[] = $anonymousToken;
            $request->session()->put('anonymous_ticket_tokens', array_slice(array_values(array_unique($tokens)), -100));
        }
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $isAnonymous ? null : auth()->id(),
            'action' => 'created',
            'to' => ['title' => $ticket->title, 'priority' => $ticket->priority],
            'ip' => $isAnonymous ? null : $request->ip(),
            'note' => $isAnonymous ? 'Submitted anonymously' : null,
        ]);

        $message = $isAnonymous
            ? "Anonymous ticket {$ticket->ticket_number} created — it is not linked to your account, but it will appear in your ticket list."
            : "Ticket {$ticket->ticket_number} created — SLA ".Ticket::SLA_HOURS[$ticket->priority].'h';

        return redirect()->route('tickets.index')->with('success', $message);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeAccess($ticket, $this->anonymousTokens($request));

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket->load(['user', 'assignee', 'department', 'histories.user']),
            'departments' => Department::pluck('name', 'id'),
            'roles' => Role::pluck('name'),
            'histories' => $ticket->histories()->with('user')->latest()->get(),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        if (! auth()->user()->hasAnyRole(['SuperAdmin', 'Admin', 'Support'])) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);
        $before = $ticket->only(['status', 'priority', 'department_id', 'assigned_to']);
        if ($validated['status'] === 'closed') {
            $validated['closed_at'] = now();
        }
        // auto-escalate check
        if ($ticket->isOverdue() && $ticket->status !== 'closed') {
            $validated['escalated_at'] = now();
        }
        $ticket->update($validated);
        $after = $ticket->only(['status', 'priority', 'department_id', 'assigned_to']);
        $action = $before['status'] !== $after['status'] ? 'status_changed' : ($before['assigned_to'] !== $after['assigned_to'] ? 'assigned' : 'updated');
        TicketHistory::create(['ticket_id' => $ticket->id, 'user_id' => auth()->id(), 'action' => $action, 'from' => $before, 'to' => $after, 'ip' => $request->ip()]);

        return back()->with('success', 'Ticket updated');
    }

    private function authorizeAccess(Ticket $ticket, array $anonymousTokens = []): void
    {
        $user = auth()->user();
        if ($user->hasRole('SuperAdmin')) {
            return;
        }
        // Admin/Support can access if ticket is visible to them (department/unit/assigned/own/anonymous)
        if ($user->hasAnyRole(['Admin', 'Support'])) {
            $visible = Ticket::where('id', $ticket->id)->visibleTo($user, false, $anonymousTokens)->exists();
            if ($visible) {
                return;
            }
            abort(403, 'Ticket not in your department/unit/assigned scope');
        }
        if ($ticket->user_id !== $user->id) {
            if (! $ticket->anonymous_token || ! in_array($ticket->anonymous_token, $anonymousTokens, true)) {
                abort(403);
            }
        }
    }

    private function anonymousTokens(Request $request): array
    {
        $tokens = $request->session()->get('anonymous_ticket_tokens', []);

        return is_array($tokens) ? $tokens : [];
    }
}
