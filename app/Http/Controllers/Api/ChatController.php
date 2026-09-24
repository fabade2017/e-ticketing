<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function groups(Request $request)
    {
        $user = $request->user();
        $groups = ChatGroup::with(['creator:id,name,rank'])
            ->where(function ($q) use ($user) {
                $q->whereHas('members', fn($qq) => $qq->where('users.id', $user->id))
                  ->orWhere('is_announcement', true);
            })
            ->orWhere('created_by', $user->id)
            ->latest()->get()->unique('id')->values()
            ->map(function ($g) use ($user) {
                $pivot = $g->members->firstWhere('id', $user->id)?->pivot;
                $lastRead = $pivot?->last_read_at;
                $unread = ChatMessage::where('chat_group_id', $g->id)->where('user_id', '!=', $user->id)
                    ->when($lastRead, fn($q) => $q->where('created_at', '>', $lastRead))->count();
                return [
                    'id' => $g->id,
                    'name' => $g->name,
                    'description' => $g->description,
                    'is_announcement' => $g->is_announcement,
                    'created_by' => $g->created_by,
                    'creator' => $g->creator?->only(['id','name','rank']),
                    'members_count' => $g->members->count(),
                    'can_post' => $g->canPost($user),
                    'is_member' => $g->members->contains('id', $user->id) || $g->is_announcement,
                    'unread_count' => $unread,
                ];
            });

        return response()->json($groups);
    }

    public function messages(Request $request, ChatGroup $group)
    {
        $user = $request->user();
        if (!$group->members->contains('id', $user->id) && !$group->is_announcement && $group->created_by !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $group->members()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        $messages = ChatMessage::with('user:id,name,rank,service_number')
            ->where('chat_group_id', $group->id)->orderBy('created_at')->limit(100)->get();
        return response()->json($messages);
    }

    public function sendMessage(Request $request, ChatGroup $group)
    {
        $user = $request->user();
        if (!$group->canPost($user)) {
            return response()->json(['message' => 'Not allowed to post'], 403);
        }
        $request->validate(['message' => 'required|string|max:2000']);
        $msg = ChatMessage::create(['chat_group_id' => $group->id, 'user_id' => $user->id, 'message' => $request->message]);
        $msg->load('user:id,name,rank,service_number');
        return response()->json($msg, 201);
    }

    public function unread(Request $request)
    {
        $user = $request->user();
        $groups = ChatGroup::with('members')->where(function ($q) use ($user) {
            $q->whereHas('members', fn($qq) => $qq->where('users.id', $user->id))->orWhere('is_announcement', true);
        })->orWhere('created_by', $user->id)->get()->unique('id');
        $result = [];
        $total = 0;
        foreach ($groups as $g) {
            $pivot = $g->members->firstWhere('id', $user->id)?->pivot;
            $lastRead = $pivot?->last_read_at;
            $count = ChatMessage::where('chat_group_id', $g->id)->where('user_id', '!=', $user->id)
                ->when($lastRead, fn($q) => $q->where('created_at', '>', $lastRead))->count();
            if ($count) { $result[] = ['group_id'=>$g->id,'name'=>$g->name,'count'=>$count]; $total += $count; }
        }
        return response()->json(['total'=>$total,'groups'=>$result]);
    }
}
