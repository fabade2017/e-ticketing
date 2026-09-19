<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;

class EscalateTickets extends Command
{
    protected $signature = 'tickets:escalate';
    protected $description = 'Auto-escalate overdue tickets (SLA breach)';

    public function handle(): int
    {
        $overdue = Ticket::where('sla_due_at','<', now())->whereNull('escalated_at')->whereNotIn('status',['resolved','closed'])->get();
        foreach ($overdue as $t) {
            $t->update(['escalated_at'=>now()]);
            \App\Models\TicketHistory::create(['ticket_id'=>$t->id,'user_id'=>null,'action'=>'escalated','from'=>['sla_due_at'=>$t->sla_due_at],'to'=>['escalated_at'=>now()], 'note'=>'Auto-escalated: SLA breach']);
            // push stub: log + would send FCM/SMS here
            \Illuminate\Support\Facades\Log::info("Escalated ticket {$t->ticket_number} overdue");
        }
        $this->info("Escalated {$overdue->count()} tickets");
        return 0;
    }
}
