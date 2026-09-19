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
        // RBAC: Soldier sees own only, Admin/Support/SuperAdmin see all (unit/department badge shows scope but not filtered — use ?unit= to filter)
        if (!$user->hasAnyRole(['SuperAdmin','Admin','Support'])) {
            $query->where('user_id', $user->id);
        }
        // optional scope filter ?scope=unit or ?scope=department
        if ($request->scope === 'unit' && $user->unit) $query->where('unit', $user->unit);
        if ($request->scope === 'department' && $user->department_id) $query->where('department_id', $user->department_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) $query->where(fn($q) => $q->where('title', 'like', "%{$request->search}%")->orWhere('ticket_number', 'like', "%{$request->search}%"));
        if ($request->filter === 'overdue') $query->where('sla_due_at','<', now())->whereNotIn('status',['resolved','closed']);

        return Inertia::render('Tickets/Index', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'search','filter']),
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
        if (auth()->user()->hasAnyRole(['SuperAdmin', 'Admin', 'Support'])) return;
        if ($ticket->user_id !== auth()->id()) abort(403);
    }
}
