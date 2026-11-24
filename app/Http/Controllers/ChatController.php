<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Support\Notify;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** The visitor side of live chat. The visitor is recognised only by a secret token kept in their own browser. */
class ChatController extends Controller
{
    private function enabled(): bool
    {
        return (bool) Settings::get('chat', 'enabled', false);
    }

    private function conversation(Request $request): ChatConversation
    {
        abort_unless($this->enabled(), 404);
        $token = (string) $request->header('X-Chat-Token');

        return ChatConversation::where('token_hash', hash('sha256', $token))->when(strlen($token) !== 40, fn($q) => $q->whereRaw('1 = 0'))->firstOrFail();
    }

    private function noSpam(string $body): void
    {
        if (preg_match_all('~https?://|www\.~i', $body) > 2) {
            throw ValidationException::withMessages(['message' => 'Please do not paste several links in a chat message.']);
        }
    }

    private function messages(ChatConversation $c, int $after = 0): array
    {
        return $c->messages()->where('id', '>', $after)->orderBy('id')->limit(100)->get()
            ->map(fn($m) => ['id' => $m->id, 'from' => $m->sender, 'body' => $m->body, 'at' => $m->created_at->format('H:i')])->all();
    }

    public function start(Request $request): JsonResponse
    {
        abort_unless($this->enabled(), 404);
        if ($request->filled('website')) {
            return response()->json(['token' => Str::random(40), 'status' => 'open', 'messages' => []]);   // honeypot: a robot gets a harmless answer
        }
        $d = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'message' => ['required', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ]);
        $this->noSpam($d['message']);

        $token = Str::random(40);
        $c = ChatConversation::create([
            'token_hash' => hash('sha256', $token),
            'visitor_name' => $d['name'] ?? null,
            'visitor_email' => $d['email'] ?? null,
            'locale' => $request->input('locale') === 'bn' ? 'bn' : 'en',
            'status' => 'open',
            'staff_unread' => 1,
            'last_message_at' => now(),
        ]);
        $c->messages()->create(['sender' => 'visitor', 'body' => $d['message']]);
        Notify::staff('New live chat', [($c->label()) . ': ' . Str::limit($d['message'], 160)], route('admin.chats.show', $c));

        return response()->json(['token' => $token, 'status' => 'open', 'messages' => $this->messages($c)]);
    }

    public function send(Request $request): JsonResponse
    {
        $c = $this->conversation($request);
        $d = $request->validate(['message' => ['required', 'string', 'max:1000']]);
        $this->noSpam($d['message']);
        if ($c->messages()->count() >= 300) {
            throw ValidationException::withMessages(['message' => 'This chat has reached its length limit. Please start a new one.']);
        }

        $after = (int) $request->input('after', 0);
        $c->messages()->create(['sender' => 'visitor', 'body' => $d['message']]);
        $c->update(['status' => 'open', 'staff_unread' => $c->staff_unread + 1, 'last_message_at' => now()]);

        return response()->json(['status' => 'open', 'messages' => $this->messages($c, $after)]);
    }

    public function poll(Request $request): JsonResponse
    {
        $c = $this->conversation($request);
        $messages = $this->messages($c, (int) $request->query('after', 0));
        if ($c->visitor_unread > 0) {
            $c->update(['visitor_unread' => 0]);
        }

        return response()->json(['status' => $c->status, 'messages' => $messages]);
    }
}
