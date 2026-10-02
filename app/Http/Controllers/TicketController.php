<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Ticket::with(['user', 'assignee','department'])->latest();

        // RBAC: default is per-user scoped. ?scope=all allows privileged to see everything.
        $scopeParam = $request->input('scope');
        $isAll = $scopeParam === 'all' && $user->hasAnyRole(['SuperAdmin','Admin','Support']);
        if (!$isAll) {
            $query->visibleTo($user);
        }
        // legacy explicit filters still apply (intersect with visible scope)
        if ($request->scope === 'unit' && $user->unit) $query->where('unit', $user->unit);
        if ($request->scope === 'department' && $user->department_id) $query->where('department_id', $user->department_id);
        // additional filter param ?view=unit|department|mine for toggle UI
        if ($request->input('view') === 'mine') {
            $query = Ticket::with(['user','assignee','department'])->where('user_id',$user->id)->latest();
            if ($request->status) $query->where('status', $request->status);
            if ($request->search) $query->where(fn($q) => $q->where('title', 'like', "%{$request->search}%")->orWhere('ticket_number', 'like', "%{$request->search}%"));
            if ($request->filter === 'overdue') $query->where('sla_due_at','<', now())->whereNotIn('status',['resolved','closed']);
            return Inertia::render('Tickets/Index', [
                'tickets' => $query->paginate(15)->withQueryString(),
                'filters' => $request->only(['status', 'search','filter','scope','view']),
                'scope' => 'mine',
                'canSeeAll' => $user->hasAnyRole(['SuperAdmin','Admin','Support']),
                'isAll' => false,
            ]);
        }
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) $query->where(fn($q) => $q->where('title', 'like', "%{$request->search}%")->orWhere('ticket_number', 'like', "%{$request->search}%"));
        if ($request->filter === 'overdue') $query->where('sla_due_at','<', now())->whereNotIn('status',['resolved','closed']);

        $scopeLabel = $isAll ? 'all' : ($user->hasRole('SuperAdmin') ? 'all' : ($user->department_id ? 'department' : ($user->unit ? 'unit' : 'personal')));
        if (!$isAll && $user->hasAnyRole(['Admin','Support']) && !$user->department_id && !$user->unit) $scopeLabel = 'personal';
        if (!$user->hasAnyRole(['SuperAdmin','Admin','Support'])) $scopeLabel = 'personal';

        return Inertia::render('Tickets/Index', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'search','filter','scope','view']),
            'scope' => $scopeLabel,
            'canSeeAll' => $user->hasAnyRole(['SuperAdmin','Admin','Support']),
            'isAll' => $isAll,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tickets/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:welfare,logistics,medical,equipment,operations,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'location' => 'nullable|string',
            'country' => 'required|string|in:Nigeria',
            'state' => 'required|string',
            'lga' => 'required|string',
            'offline_created' => 'boolean',
        ]);
        $validated['user_id'] = auth()->id();
        $validated['unit'] = auth()->user()->unit;
        $validated['department_id'] = auth()->user()->department_id;

        $ticket = Ticket::create($validated);
        \App\Models\TicketHistory::create(['ticket_id'=>$ticket->id,'user_id'=>auth()->id(),'action'=>'created','to'=>['title'=>$ticket->title,'priority'=>$ticket->priority],'ip'=>$request->ip()]);
        return redirect()->route('tickets.index')->with('success', "Ticket {$ticket->ticket_number} created — SLA ". \App\Models\Ticket::SLA_HOURS[$ticket->priority]."h");
    }

    public function show(Ticket $ticket)
    {
        $this->authorizeAccess($ticket);
        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket->load(['user', 'assignee','department','histories.user']),
            'departments' => \App\Models\Department::pluck('name','id'),
            'roles' => \Spatie\Permission\Models\Role::pluck('name'),
            'histories' => $ticket->histories()->with('user')->latest()->get(),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        if (!auth()->user()->hasAnyRole(['SuperAdmin', 'Admin', 'Support'])) abort(403);

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);
        $before = $ticket->only(['status','priority','department_id','assigned_to']);
        if ($validated['status'] === 'closed') $validated['closed_at'] = now();
        // auto-escalate check
        if ($ticket->isOverdue() && $ticket->status !== 'closed') $validated['escalated_at'] = now();
        $ticket->update($validated);
        $after = $ticket->only(['status','priority','department_id','assigned_to']);
        $action = $before['status'] !== $after['status'] ? 'status_changed' : ($before['assigned_to'] !== $after['assigned_to'] ? 'assigned' : 'updated');
        \App\Models\TicketHistory::create(['ticket_id'=>$ticket->id,'user_id'=>auth()->id(),'action'=>$action,'from'=>$before,'to'=>$after,'ip'=>$request->ip()]);
        return back()->with('success', 'Ticket updated');
    }

    private function authorizeAccess(Ticket $ticket): void
    {
        $user = auth()->user();
        if ($user->hasRole('SuperAdmin')) return;
        // Admin/Support can access if ticket is visible to them (department/unit/assigned/own)
        if ($user->hasAnyRole(['Admin','Support'])) {
            $visible = \App\Models\Ticket::where('id',$ticket->id)->visibleTo($user)->exists();
            if ($visible) return;
            abort(403, 'Ticket not in your department/unit/assigned scope');
        }
        if ($ticket->user_id !== $user->id) abort(403);
    }
}
