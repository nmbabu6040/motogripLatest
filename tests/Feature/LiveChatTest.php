<?php

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
});

function chatOn(): void
{
    Settings::set('chat', 'enabled', '1', 'bool');
}
function chatStaff(string $role = 'sales-representative'): User
{
    return User::factory()->create()->assignRole($role);
}

/** Starts a chat the way the website does and returns [token, conversation]. */
function chatStart(array $over = []): array
{
    $r = test()->postJson('/chat/start', array_merge(['name' => 'Rahim', 'email' => 'rahim@example.com', 'message' => 'Do you sell 15W-40?', 'consent' => true], $over))->assertOk();

    return [$r->json('token'), ChatConversation::latest('id')->first()];
}

it('is off until it is switched on in Settings: no button, no endpoints', function () {
    $this->get('/')->assertDontSee('id="chat-panel"', false);
    $this->postJson('/chat/start', ['message' => 'Hi', 'consent' => true])->assertNotFound();
    $this->getJson('/chat/messages')->assertNotFound();

    chatOn();
    Settings::set('chat', 'welcome', 'We usually reply within an hour.');
    $this->get('/')->assertOk()->assertSee('id="chat-panel"', false)->assertSee('We usually reply within an hour.');
    $this->get('/bn')->assertSee('লাইভ চ্যাট');
});

it('starts a conversation, keeps only a hash of the visitor token and tells the staff', function () {
    chatOn();
    [$token, $c] = chatStart();

    expect(strlen($token))->toBe(40)->and($c->token_hash)->toBe(hash('sha256', $token))->and($c->token_hash)->not->toContain($token)
        ->and($c->visitor_name)->toBe('Rahim')->and($c->staff_unread)->toBe(1)->and($c->status)->toBe('open')
        ->and($c->messages()->first()->body)->toBe('Do you sell 15W-40?');
});

it('checks the chat form: consent, a message, spam links and the hidden robot field', function () {
    chatOn();
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

    $this->postJson('/chat/start', ['message' => 'Hi'])->assertStatus(422)->assertJsonValidationErrors('consent');
    $this->postJson('/chat/start', ['consent' => true])->assertStatus(422)->assertJsonValidationErrors('message');
    $this->postJson('/chat/start', ['message' => str_repeat('a', 1001), 'consent' => true])->assertStatus(422);
    $this->postJson('/chat/start', ['message' => 'buy http://a.example http://b.example www.c.example', 'consent' => true])->assertStatus(422)->assertJsonValidationErrors('message');
    $this->postJson('/chat/start', ['message' => 'Hi', 'consent' => true, 'email' => 'nope'])->assertStatus(422)->assertJsonValidationErrors('email');
    expect(ChatConversation::count())->toBe(0);

    $this->postJson('/chat/start', ['message' => 'Hi', 'consent' => true, 'website' => 'http://robot.example'])->assertOk();
    expect(ChatConversation::count())->toBe(0);
});

it('lets the visitor read new messages with the token only', function () {
    chatOn();
    [$token, $c] = chatStart();
    $c->messages()->create(['sender' => 'staff', 'body' => 'Yes, we do.']);
    $c->update(['visitor_unread' => 1]);

    $r = $this->withHeader('X-Chat-Token', $token)->getJson('/chat/messages?after=0')->assertOk()->json();
    expect($r['messages'])->toHaveCount(2)->and($r['messages'][1]['from'])->toBe('staff')->and($r['messages'][1]['body'])->toBe('Yes, we do.')->and($c->fresh()->visitor_unread)->toBe(0);

    $after = $r['messages'][1]['id'];
    expect($this->withHeader('X-Chat-Token', $token)->getJson("/chat/messages?after={$after}")->json('messages'))->toBe([]);

    $this->withHeader('X-Chat-Token', str_repeat('x', 40))->getJson('/chat/messages')->assertNotFound();
    $this->withHeader('X-Chat-Token', 'short')->getJson('/chat/messages')->assertNotFound();
    $this->getJson('/chat/messages')->assertNotFound();
});

it('sends more visitor messages, reopens a closed chat and returns only what is new', function () {
    chatOn();
    [$token, $c] = chatStart();
    $first = $c->messages()->first()->id;
    $c->update(['status' => 'closed', 'staff_unread' => 0]);

    $r = $this->withHeader('X-Chat-Token', $token)->postJson('/chat/send', ['message' => 'Hello again', 'after' => $first])->assertOk()->json();

    expect($r['messages'])->toHaveCount(1)->and($r['messages'][0]['body'])->toBe('Hello again')->and($c->fresh()->status)->toBe('open')->and($c->fresh()->staff_unread)->toBe(1);
    $this->withHeader('X-Chat-Token', $token)->postJson('/chat/send', ['message' => ''])->assertStatus(422);
    $this->flushHeaders()
        ->postJson('/chat/send', ['message' => 'no token'])
        ->assertNotFound();
});

it('stops a chat from growing without limit', function () {
    chatOn();
    [$token, $c] = chatStart();
    foreach (range(1, 299) as $i) {
        ChatMessage::create(['chat_conversation_id' => $c->id, 'sender' => 'visitor', 'body' => "m{$i}"]);
    }

    $this->withHeader('X-Chat-Token', $token)->postJson('/chat/send', ['message' => 'one too many'])->assertStatus(422);
});

it('shows staff the waiting chats, clears the unread count when opened and lets them reply', function () {
    chatOn();
    [$token, $c] = chatStart();
    $staff = chatStaff();
    $this->actingAs($staff);

    $this->get('/admin/chats')->assertOk()->assertSee('Rahim')->assertSee('1 new');
    $this->get("/admin/chats/{$c->id}")->assertOk()->assertSee('Do you sell 15W-40?');
    expect($c->fresh()->staff_unread)->toBe(0);

    $this->postJson("/admin/chats/{$c->id}/reply", ['body' => 'Yes, in 4 L and 208 L.'])->assertOk();
    $m = $c->messages()->where('sender', 'staff')->first();
    expect($m->body)->toBe('Yes, in 4 L and 208 L.')->and($m->user_id)->toBe($staff->id)->and($c->fresh()->visitor_unread)->toBe(1);

    auth()->logout();
    $seen = $this->withHeader('X-Chat-Token', $token)->getJson('/chat/messages')->json('messages');
    expect(collect($seen)->pluck('body')->all())->toBe(['Do you sell 15W-40?', 'Yes, in 4 L and 208 L.']);
});

it('lets the open conversation page pick up new visitor messages', function () {
    chatOn();
    [$token, $c] = chatStart();
    $first = $c->messages()->first()->id;
    $this->withHeader('X-Chat-Token', $token)->postJson('/chat/send', ['message' => 'Anyone there?']);

    $r = $this->actingAs(chatStaff())->getJson("/admin/chats/{$c->id}/messages?after={$first}")->assertOk()->json();
    expect($r['messages'])->toHaveCount(1)->and($r['messages'][0]['body'])->toBe('Anyone there?')->and($c->fresh()->staff_unread)->toBe(0);
});

it('closes, reopens and deletes conversations', function () {
    chatOn();
    [, $c] = chatStart();
    $this->actingAs(chatStaff('sales-manager'));

    $this->post("/admin/chats/{$c->id}/close")->assertRedirect();
    expect($c->fresh()->status)->toBe('closed');
    $this->get('/admin/chats?status=closed')->assertSee('Rahim');
    $this->get('/admin/chats?status=open')->assertDontSee('Rahim');

    $this->post("/admin/chats/{$c->id}/reopen")->assertRedirect();
    expect($c->fresh()->status)->toBe('open');

    $this->delete("/admin/chats/{$c->id}")->assertRedirect(route('admin.chats.index'));
    expect(ChatConversation::count())->toBe(0)->and(ChatMessage::count())->toBe(0);
});

it('keeps live chat to people who handle inquiries', function () {
    chatOn();
    [, $c] = chatStart();

    $this->actingAs(chatStaff('content-manager'))->get('/admin/chats')->assertForbidden();
    $this->get("/admin/chats/{$c->id}")->assertForbidden();
    $this->postJson("/admin/chats/{$c->id}/reply", ['body' => 'hi'])->assertForbidden();
    $this->delete("/admin/chats/{$c->id}")->assertForbidden();
    expect(ChatConversation::count())->toBe(1);
});

it('shows the number of waiting chats beside "Chats" in the admin sidebar', function () {
    chatOn();
    chatStart();
    chatStart(['message' => 'Second visitor']);

    $this->actingAs(chatStaff())->get('/admin')->assertOk()->assertSee('Chats (2)');
});

it('tells staff when chat is switched off, and deletes old conversations on schedule', function () {
    $this->actingAs(chatStaff())->get('/admin/chats')->assertOk()->assertSee('বন্ধ');

    $old = ChatConversation::create(['token_hash' => str_repeat('a', 64), 'last_message_at' => now()->subDays(100)]);
    $new = ChatConversation::create(['token_hash' => str_repeat('b', 64), 'last_message_at' => now()->subDays(5)]);
    $this->artisan('chat:prune')->assertSuccessful();

    expect(ChatConversation::find($old->id))->toBeNull()->and(ChatConversation::find($new->id))->not->toBeNull();
});
