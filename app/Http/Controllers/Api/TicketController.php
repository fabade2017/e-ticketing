<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * GET /api/tickets - soldier sees own, privileged sees all
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Ticket::with(['user:id,name,rank,service_number', 'assignee:id,name,rank', 'department:id,name'])
            ->latest();

        $isAll = $request->input('scope') === 'all' && $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support']);
        if (! $isAll) {
            $query->visibleTo($user, false, $this->anonymousTokens($request));
        } else {
            // scope=all explicit, still allow additional unit/department filter as intersection
        }

        if ($request->scope === 'unit' && $user->unit) {
            $query->where('unit', $user->unit);
        }
        if ($request->scope === 'department' && $user->department_id) {
            $query->where('department_id', $user->department_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('title', 'like', "%{$s}%")->orWhere('ticket_number', 'like', "%{$s}%"));
        }
        if ($request->filter === 'overdue') {
            $query->where('sla_due_at', '<', now())->whereNotIn('status', ['resolved', 'closed']);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        $perPage = min((int) $request->input('per_page', 15), 50);
        $tickets = $query->paginate($perPage)->withQueryString();

        return response()->json($tickets);
    }

    /**
     * POST /api/tickets
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:welfare,logistics,medical,equipment,operations,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'location' => 'nullable|string|max:255',
            'country' => 'required|string|max:100',
            'state' => 'required|string',
            'lga' => 'required|string',
            'department_id' => 'nullable|exists:departments,id',
            'offline_created' => 'nullable|boolean',
            'is_anonymous' => 'nullable|boolean',
            'client_uuid' => 'nullable|uuid',
        ]);

        $isAnonymous = $request->boolean('is_anonymous');

        // Idempotent offline sync: a queued ticket re-sent with the same client_uuid
        // must not create a duplicate row. Anonymous tickets have no user_id, so we
        // de-duplicate them by client_uuid alone.
        if (! empty($validated['client_uuid'])) {
            $existing = Ticket::where('client_uuid', $validated['client_uuid'])
                ->when(
                    $isAnonymous,
                    fn ($q) => $q->where('is_anonymous', true),
                    fn ($q) => $q->where('user_id', $request->user()->id)
                )
                ->first();

            if ($existing) {
                $existing->load(['user:id,name,rank,service_number', 'department:id,name']);

                return response()->json([
                    'message' => "Ticket {$existing->ticket_number} already synced",
                    'ticket' => $existing,
                    'duplicate' => true,
                    'anonymous_token' => $existing->is_anonymous ? $existing->anonymous_token : null,
                ], 200);
            }
        }

        $anonymousToken = $isAnonymous ? Str::random(64) : null;
        $validated['user_id'] = $isAnonymous ? null : $request->user()->id;
        $validated['unit'] = $isAnonymous ? null : $request->user()->unit;
        $validated['is_anonymous'] = $isAnonymous;
        $validated['anonymous_token'] = $anonymousToken;
        $validated['department_id'] = $request->input('department_id') ?: $request->user()->department_id;
        $validated['offline_created'] = $request->boolean('offline_created');

        $ticket = Ticket::create($validated);
        $ticket->load(['user:id,name,rank,service_number', 'department:id,name']);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $isAnonymous ? null : $request->user()->id,
            'action' => 'created',
            'to' => ['title' => $ticket->title, 'priority' => $ticket->priority],
            'ip' => $isAnonymous ? null : $request->ip(),
            'note' => $isAnonymous ? 'Submitted anonymously' : null,
        ]);

        $message = $isAnonymous
            ? "Anonymous ticket {$ticket->ticket_number} created — it is not linked to your account, but it will appear in your ticket list."
            : "Ticket {$ticket->ticket_number} created — SLA ".Ticket::SLA_HOURS[$ticket->priority].'h';

        return response()->json([
            'message' => $message,
            'ticket' => $ticket,
            'anonymous_token' => $anonymousToken,
        ], 201);
    }

    /**
     * GET /api/tickets/{ticket}
     */
    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeAccess($request->user(), $ticket, $this->anonymousTokens($request));

        $ticket->load(['user:id,name,rank,service_number,unit', 'assignee:id,name,rank', 'department:id,name', 'histories.user:id,name,rank']);

        return response()->json([
            'ticket' => $ticket,
            'histories' => $ticket->histories()->with('user:id,name,rank')->latest()->get(),
        ]);
    }

    /**
     * PATCH /api/tickets/{ticket} - privileged only
     */
    public function update(Request $request, Ticket $ticket)
    {
        if (! $request->user()->hasAnyRole(['SuperAdmin', 'Admin', 'Support'])) {
            return response()->json(['message' => 'Forbidden'], 403);
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
        if ($ticket->isOverdue() && $ticket->status !== 'closed') {
            $validated['escalated_at'] = now();
        }
        $ticket->update($validated);
        $after = $ticket->only(['status', 'priority', 'department_id', 'assigned_to']);
        $action = $before['status'] !== $after['status'] ? 'status_changed' : ($before['assigned_to'] !== $after['assigned_to'] ? 'assigned' : 'updated');

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'action' => $action,
            'from' => $before,
            'to' => $after,
            'ip' => $request->ip(),
        ]);

        $ticket->load(['user:id,name,rank', 'assignee:id,name,rank', 'department:id,name']);

        return response()->json(['message' => 'Ticket updated', 'ticket' => $ticket]);
    }

    /**
     * GET /api/tickets/stats/summary - for soldier dashboard
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $query = Ticket::query();
        $isAll = $request->input('scope') === 'all' && $user->hasAnyRole(['SuperAdmin', 'Admin', 'Support']);
        if (! $isAll) {
            $query->visibleTo($user, false, $this->anonymousTokens($request));
        }

        return response()->json([
            'total' => (clone $query)->count(),
            'open' => (clone $query)->where('status', 'open')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'resolved' => (clone $query)->where('status', 'resolved')->count(),
            'closed' => (clone $query)->where('status', 'closed')->count(),
            'overdue' => (clone $query)->where('sla_due_at', '<', now())->whereNotIn('status', ['resolved', 'closed'])->count(),
            'sla_hours' => Ticket::SLA_HOURS,
        ]);
    }

    private function authorizeAccess($user, Ticket $ticket, array $anonymousTokens = []): void
    {
        if ($user->hasRole('SuperAdmin')) {
            return;
        }
        if ($user->hasAnyRole(['Admin', 'Support'])) {
            if (Ticket::where('id', $ticket->id)->visibleTo($user, false, $anonymousTokens)->exists()) {
                return;
            }
            abort(403, 'Ticket not in your scope');
        }
        if ($ticket->user_id !== $user->id) {
            if (! $ticket->anonymous_token || ! in_array($ticket->anonymous_token, $anonymousTokens, true)) {
                abort(403, 'You can only view your own tickets');
            }
        }
    }

    private function anonymousTokens(Request $request): array
    {
        $header = (string) $request->header('X-Anonymous-Tokens', '');
        if ($header === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $header)),
            fn ($token) => is_string($token) && strlen($token) === 64
        ));
    }
}
