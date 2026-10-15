<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTicketSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function payload(string $uuid): array
    {
        return [
            'title' => 'Water pump faulty at OP 1',
            'description' => 'No water supply for two days',
            'category' => 'logistics',
            'priority' => 'high',
            'country' => 'Nigeria',
            'state' => 'Borno',
            'lga' => 'Maiduguri',
            'offline_created' => true,
            'client_uuid' => $uuid,
        ];
    }

    public function test_offline_sync_is_idempotent_by_client_uuid(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        Sanctum::actingAs($soldier);

        $uuid = (string) Str::uuid();

        $first = $this->postJson('/api/tickets', $this->payload($uuid));
        $first->assertStatus(201);

        // Simulate the sync queue retrying the same ticket after a flaky connection
        $second = $this->postJson('/api/tickets', $this->payload($uuid));
        $second->assertStatus(200)->assertJson(['duplicate' => true]);

        $this->assertSame(1, Ticket::where('client_uuid', $uuid)->count());
        $this->assertSame($first->json('ticket.id'), $second->json('ticket.id'));
    }

    public function test_tickets_without_client_uuid_are_not_deduplicated(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        Sanctum::actingAs($soldier);

        $payload = $this->payload('');
        unset($payload['client_uuid']);

        $before = Ticket::whereNull('client_uuid')->count();

        $this->postJson('/api/tickets', $payload)->assertStatus(201);
        $this->postJson('/api/tickets', $payload)->assertStatus(201);

        $this->assertSame($before + 2, Ticket::whereNull('client_uuid')->count());
    }

    public function test_client_uuid_must_be_a_valid_uuid(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        Sanctum::actingAs($soldier);

        $this->postJson('/api/tickets', $this->payload('not-a-uuid'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('client_uuid');
    }

    public function test_ticket_uses_selected_department_over_user_default(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        $home = Department::create(['name' => 'Logistics', 'code' => 'LOG']);
        $other = Department::create(['name' => 'Medical', 'code' => 'MED']);
        $soldier->update(['department_id' => $home->id]);
        Sanctum::actingAs($soldier);

        $payload = $this->payload('');
        unset($payload['client_uuid']);
        $payload['department_id'] = $other->id;

        $response = $this->postJson('/api/tickets', $payload)->assertStatus(201);

        $this->assertSame($other->id, $response->json('ticket.department_id'));
    }

    public function test_ticket_department_defaults_to_users_own_department(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        $home = Department::create(['name' => 'Logistics', 'code' => 'LOG']);
        $soldier->update(['department_id' => $home->id]);
        Sanctum::actingAs($soldier);

        $payload = $this->payload('');
        unset($payload['client_uuid']);

        $response = $this->postJson('/api/tickets', $payload)->assertStatus(201);

        $this->assertSame($home->id, $response->json('ticket.department_id'));
    }

    public function test_ticket_rejects_unknown_department(): void
    {
        $soldier = User::where('email', 'soldier@army.mil.ng')->first();
        Sanctum::actingAs($soldier);

        $payload = $this->payload('');
        unset($payload['client_uuid']);
        $payload['department_id'] = 9999;

        $this->postJson('/api/tickets', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('department_id');
    }
}
