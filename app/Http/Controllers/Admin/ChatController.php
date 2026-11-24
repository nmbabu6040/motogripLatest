<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use Illuminate\Http\Request;

/** The staff side of live chat. */
class ChatController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');

        return view('admin.chats.index', [
            'status' => $status,
            'chats' => ChatConversation::withCount('messages')
                ->when(in_array($status, ['open', 'closed'], true), fn ($q) => $q->where('status', $status))
                ->orderByDesc('staff_unread')->orderByDesc('last_message_at')->paginate(25)->withQueryString(),
            'openCount' => ChatConversation::where('status', 'open')->count(),
            'closedCount' => ChatConversation::where('status', 'closed')->count(),
        ]);
    }

    public function show(ChatConversation $conversation)
    {
        $conversation->update(['staff_unread' => 0]);

        return view('admin.chats.show', ['chat' => $conversation, 'messages' => $conversation->messages()->with('user')->orderBy('id')->get()]);
    }

    /** Used by the open conversation page to pick up new visitor messages without reloading. */
    public function poll(Request $request, ChatConversation $conversation)
    {
        $messages = $conversation->messages()->with('user')->where('id', '>', (int) $request->query('after', 0))->orderBy('id')->get()
            ->map(fn ($m) => ['id' => $m->id, 'from' => $m->sender, 'body' => $m->body, 'at' => $m->created_at->format('H:i'), 'by' => $m->user?->name])->all();
        if ($messages) {
            $conversation->update(['staff_unread' => 0]);
        }

        return response()->json(['status' => $conversation->status, 'messages' => $messages]);
    }

    public function reply(Request $request, ChatConversation $conversation)
    {
        $d = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $m = $conversation->messages()->create(['sender' => 'staff', 'user_id' => $request->user()->id, 'body' => $d['body']]);
        $conversation->update(['status' => 'open', 'staff_unread' => 0, 'visitor_unread' => $conversation->visitor_unread + 1, 'last_message_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['id' => $m->id]);
        }

        return back();
    }

    public function close(Request $request, ChatConversation $conversation)
    {
        $conversation->update(['status' => 'closed']);
        activity('admin')->causedBy($request->user())->withProperties(['chat' => $conversation->id])->log('chat.closed');

        return back()->with('status', 'Chat closed. If the visitor writes again it opens again.');
    }

    public function reopen(Request $request, ChatConversation $conversation)
    {
        $conversation->update(['status' => 'open']);

        return back()->with('status', 'Chat reopened.');
    }

    public function destroy(Request $request, ChatConversation $conversation)
    {
        activity('admin')->causedBy($request->user())->withProperties(['chat' => $conversation->id])->log('chat.deleted');
        $conversation->delete();

        return redirect()->route('admin.chats.index')->with('status', 'Conversation deleted.');
    }
}
