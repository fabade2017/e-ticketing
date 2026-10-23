<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnonymousTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function soldier(): User
    {
        return User::where('email', 'soldier@army.mil.ng')->first();
    }

    private function otherSoldier(): User
    {
        $user = User::create([
            'name' => 'Other Soldier',
            'email' => 'other.soldier@army.mil.ng',
            'service_number' => 'NA/SL/999',
            'rank' => 'Pte',
            'unit' => 'Border Outpost B',
            'password' => Hash::make('Soldier@123'),
            'is_active' => true,
        ]);
        $user->assignRole('Soldier');

        return $user;
    }

    private function superadmin(): User
    {
        return User::where('email', 'superadmin@army.mil.ng')->first();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Anonymous welfare concern',
            'description' => 'Submitted without identifying the reporter',
            'category' => 'welfare',
            'priority' => 'medium',
            'country' => 'Nigeria',
            'state' => 'Borno',
            'lga' => 'Maiduguri',
            'is_anonymous' => true,
        ], $overrides);
    }

    public function test_api_anonymous_ticket_is_not_linked_to_the_submitter(): void
    {
        $soldier = $this->soldier();
        Sanctum::actingAs($soldier);

        $response = $this->postJson('/api/tickets', $this->payload());

        $response->assertStatus(201)
            ->assertJsonPath('ticket.is_anonymous', true)
            ->assertJsonPath('ticket.user_id', null)
            ->assertJsonPath('ticket.unit', null);

        $this->assertArrayNotHasKey('anonymous_token', $response->json('ticket'));
        $this->assertSame(64, strlen($response->json('anonymous_token')));

        $ticket = Ticket::latest('id')->first();

        $this->assertNull($ticket->user_id);
        $this->assertTrue($ticket->is_anonymous);
        $this->assertNull($ticket->unit);
        $this->assertSame(64, strlen($ticket->anonymous_token));

        $history = TicketHistory::where('ticket_id', $ticket->id)->where('action', 'created')->first();

        $this->assertNotNull($history);
        $this->assertNull($history->user_id);
        $this->assertNull($history->ip);
        $this->assertSame('Submitted anonymously', $history->note);
    }

    public function test_anonymous_ticket_is_visible_to_its_submitter_via_token_but_not_to_others(): void
    {
        $soldier = $this->soldier();
        Sanctum::actingAs($soldier);

        $token = $this->postJson('/api/tickets', $this->payload())
            ->assertStatus(201)
            ->json('anonymous_token');

        $anonymousId = Ticket::where('is_anonymous', true)->value('id');

        $submitter = $this->withHeader('X-Anonymous-Tokens', $token)->getJson('/api/tickets');
        $submitter->assertOk();
        $this->assertTrue(collect($submitter->json('data'))->pluck('id')->contains($anonymousId));
        $this->assertArrayNotHasKey('anonymous_token', $submitter->json('data.0'));

        $this->withHeader('X-Anonymous-Tokens', $token)
            ->getJson("/api/tickets/{$anonymousId}")
            ->assertOk()
            ->assertJsonPath('ticket.is_anonymous', true)
            ->assertJsonPath('ticket.user_id', null);

        $this->flushHeaders();
        Sanctum::actingAs($this->otherSoldier());
        $otherIds = collect($this->getJson('/api/tickets')->json('data'))->pluck('id');
        $this->assertFalse($otherIds->contains($anonymousId), 'Anonymous ticket must not be visible to other soldiers.');
        $this->getJson("/api/tickets/{$anonymousId}")->assertForbidden();

        Sanctum::actingAs($this->superadmin());
        $allIds = collect($this->getJson('/api/tickets?scope=all')->json('data'))->pluck('id');
        $this->assertTrue($allIds->contains($anonymousId));
    }

    public function test_anonymous_offline_sync_is_idempotent_without_a_user_id(): void
    {
        Sanctum::actingAs($this->soldier());

        $uuid = (string) Str::uuid();

        $token = $this->postJson('/api/tickets', $this->payload(['client_uuid' => $uuid]))
            ->assertStatus(201)
            ->json('anonymous_token');

        $duplicate = $this->postJson('/api/tickets', $this->payload(['client_uuid' => $uuid]))
            ->assertStatus(200)
            ->assertJsonPath('duplicate', true);

        $this->assertSame($token, $duplicate->json('anonymous_token'));
        $this->assertSame(1, Ticket::where('client_uuid', $uuid)->count());
    }

    public function test_web_anonymous_ticket_is_visible_in_the_submitters_grid(): void
    {
        $soldier = $this->soldier();

        $this->actingAs($soldier)
            ->post('/tickets', $this->payload(['is_anonymous' => '1']))
            ->assertRedirect('/tickets');

        $ticket = Ticket::latest('id')->first();

        $this->assertNull($ticket->user_id);
        $this->assertTrue($ticket->is_anonymous);
        $this->assertNull($ticket->unit);

        $history = TicketHistory::where('ticket_id', $ticket->id)->where('action', 'created')->first();
        $this->assertNull($history->user_id);
        $this->assertNull($history->ip);

        $this->get('/tickets')->assertInertia(fn ($page) => $page
            ->where('tickets.data', fn ($data) => collect($data)->pluck('id')->contains($ticket->id))
        );

        $this->get("/tickets/{$ticket->id}")->assertOk();
    }

    public function test_web_anonymous_ticket_is_not_visible_to_another_user(): void
    {
        $this->actingAs($this->soldier())
            ->post('/tickets', $this->payload(['is_anonymous' => '1']))
            ->assertRedirect('/tickets');

        $ticket = Ticket::latest('id')->first();

        $this->actingAs($this->otherSoldier())
            ->withSession(['anonymous_ticket_tokens' => []])
            ->get("/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_regular_tickets_still_record_the_submitter(): void
    {
        $soldier = $this->soldier();
        Sanctum::actingAs($soldier);

        $this->postJson('/api/tickets', $this->payload(['is_anonymous' => false]))
            ->assertStatus(201)
            ->assertJsonPath('ticket.is_anonymous', false)
            ->assertJsonPath('ticket.user_id', $soldier->id)
            ->assertJsonPath('ticket.unit', $soldier->unit);
    }
}
