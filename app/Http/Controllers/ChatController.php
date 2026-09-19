<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // groups where user is member or (if privileged) all announcement groups
        $groups = ChatGroup::with(['creator','members'])
            ->where(function($q) use ($user){
                $q->whereHas('members', fn($qq)=>$qq->where('users.id',$user->id))
                  ->orWhere('is_announcement', true);
            })
            ->orWhere('created_by',$user->id)
            ->latest()->get()->unique('id')->values();

        // also include groups user created
        $groups = $groups->map(function($g) use ($user){
            $pivot = $g->members->firstWhere('id',$user->id)?->pivot;
            $lastRead = $pivot?->last_read_at;
            $unread = \App\Models\ChatMessage::where('chat_group_id',$g->id)->where('user_id','!=',$user->id)->when($lastRead, fn($q)=>$q->where('created_at','>',$lastRead))->count();
            // announcement groups show unread even if not member
            if ($g->is_announcement && !$pivot) $unread = \App\Models\ChatMessage::where('chat_group_id',$g->id)->where('user_id','!=',$user->id)->count();
            return [
            'id'=>$g->id,'name'=>$g->name,'description'=>$g->description,'is_announcement'=>$g->is_announcement,'created_by'=>$g->created_by,'creator'=>$g->creator?->only(['id','name','rank']),
            'members_count'=>$g->members->count(),'can_post'=>$g->canPost($user),'is_member'=>$g->members->contains('id',$user->id) || $g->is_announcement,
            'unread_count'=>$unread
            ];
        });

        // search support for groups/individuals/numbers (service_number)
        $search = $request->input('search');
        if ($search) {
            $groups = $groups->filter(fn($g)=> stripos($g['name'], $search)!==false || stripos($g['description'] ?? '', $search)!==false || stripos($g['creator']['name'] ?? '', $search)!==false);
        }
        // also search users for quick find
        $userSearch = $request->input('user_search');
        $filteredUsers = User::select('id','name','rank','service_number');
        if ($userSearch) $filteredUsers->where(fn($q)=>$q->where('name','like',"%{$userSearch}%")->orWhere('service_number','like',"%{$userSearch}%")->orWhere('rank','like',"%{$userSearch}%"));
        $allUsers = $filteredUsers->limit(100)->get();
        if ($search && !$userSearch) {
            // if global search, also filter users by same term for convenience
            $allUsers = User::select('id','name','rank','service_number')->where(fn($q)=>$q->where('name','like',"%{$search}%")->orWhere('service_number','like',"%{$search}%")->orWhere('rank','like',"%{$search}%"))->limit(100)->get();
        }

        $selected = null; $messages = [];
        if ($request->group_id) {
            $selected = ChatGroup::with(['members','creator'])->find($request->group_id);
            if ($selected && ($selected->members->contains('id',$user->id) || $selected->is_announcement || $selected->created_by==$user->id)) {
                // mark read on open
                $selected->members()->updateExistingPivot($user->id, ['last_read_at'=>now()]);
                $messages = ChatMessage::with('user')->where('chat_group_id',$selected->id)->orderBy('created_at')->limit(100)->get()->map(fn($m)=>[
                    'id'=>$m->id,'message'=>$m->message,'created_at'=>$m->created_at->toDateTimeString(),'user'=>$m->user->only(['id','name','rank','service_number'])
                ]);
                $selected = ['id'=>$selected->id,'name'=>$selected->name,'description'=>$selected->description,'is_announcement'=>$selected->is_announcement,'can_post'=>$selected->canPost($user),'members'=>$selected->members->map(fn($u)=>$u->only(['id','name','rank','service_number']))];
            }
        }

        return Inertia::render('Chat/Index', [
            'groups'=>$groups->values(),
            'selected'=>$selected,
            'messages'=>$messages,
            'allUsers'=> $allUsers,
            'canCreateGroup'=> $user->can('create groups') || $user->hasAnyRole(['SuperAdmin','Admin','Support']),
            'filters'=> $request->only(['search','user_search','group_id']),
        ]);
    }

    public function storeGroup(Request $request)
    {
        $user = $request->user();
        if (!$user->can('create groups') && !$user->hasAnyRole(['SuperAdmin','Admin','Support'])) abort(403, 'No permission to create groups');
        $request->validate([
            'name'=>'required|string|max:100',
            'description'=>'nullable|string|max:255',
            'is_announcement'=>'boolean',
            'member_ids'=>'array',
            'member_ids.*'=>'exists:users,id',
        ]);
        $group = ChatGroup::create([
            'name'=>$request->name,
            'description'=>$request->description,
            'is_announcement'=>$request->boolean('is_announcement'),
            'created_by'=>$user->id,
        ]);
        $group->members()->attach($user->id, ['role'=>'admin']);
        foreach (($request->member_ids ?? []) as $mid) {
            if ($mid != $user->id) $group->members()->syncWithoutDetaching([$mid => ['role'=>'member']]);
        }
        return back()->with('success', 'Group created');
    }

    public function addMembers(Request $request, ChatGroup $group)
    {
        $user = $request->user();
        if (!$user->hasAnyRole(['SuperAdmin','Admin']) && $group->created_by !== $user->id) abort(403);
        $request->validate(['member_ids'=>'required|array','member_ids.*'=>'exists:users,id']);
        foreach ($request->member_ids as $mid) $group->members()->syncWithoutDetaching([$mid => ['role'=>'member']]);
        return back()->with('success','Members added');
    }

    public function removeMember(Request $request, ChatGroup $group, User $member)
    {
        $user = $request->user();
        if (!$user->hasAnyRole(['SuperAdmin','Admin']) && $group->created_by !== $user->id) abort(403);
        $group->members()->detach($member->id);
        return back()->with('success','Member removed');
    }

    public function storeMessage(Request $request, ChatGroup $group)
    {
        $user = $request->user();
        if (!$group->canPost($user)) abort(403, $group->is_announcement ? 'Announcement is readonly — only admins can post' : 'Not a member');
        $request->validate(['message'=>'required|string|max:2000']);
        ChatMessage::create(['chat_group_id'=>$group->id,'user_id'=>$user->id,'message'=>$request->message]);
        return back();
    }

    public function messages(ChatGroup $group)
    {
        $user = auth()->user();
        if (!$group->members->contains('id',$user->id) && !$group->is_announcement && $group->created_by !== $user->id) abort(403);
        // mark as read
        $group->members()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        return response()->json(ChatMessage::with('user:id,name,rank,service_number')->where('chat_group_id',$group->id)->orderBy('created_at')->limit(100)->get());
    }

    public function direct(Request $request, User $user)
    {
        $me = $request->user();
        if ($me->id === $user->id) abort(400, 'Cannot chat with yourself');
        // find existing private 1-1
        $existing = ChatGroup::where('is_private', true)
            ->whereHas('members', fn($q)=>$q->where('users.id',$me->id))
            ->whereHas('members', fn($q)=>$q->where('users.id',$user->id))
            ->withCount('members')->get()->filter(fn($g)=>$g->members_count==2)->first();
        if ($existing) return redirect()->route('chat.index', ['group_id'=>$existing->id]);
        $group = ChatGroup::create(['name'=>$me->name.' & '.$user->name,'description'=>'Direct chat','is_private'=>true,'created_by'=>$me->id]);
        $group->members()->attach([$me->id=>['role'=>'member'], $user->id=>['role'=>'member']]);
        return redirect()->route('chat.index', ['group_id'=>$group->id])->with('success','Direct chat with '.$user->name.' started');
    }

    public function unread(Request $request)
    {
        $user = $request->user();
        $groups = ChatGroup::with('members')->where(function($q) use ($user){
            $q->whereHas('members', fn($qq)=>$qq->where('users.id',$user->id))->orWhere('is_announcement', true);
        })->orWhere('created_by',$user->id)->get()->unique('id');
        $result = [];
        $total = 0;
        foreach ($groups as $g) {
            $pivot = $g->members->firstWhere('id',$user->id)?->pivot;
            $lastRead = $pivot?->last_read_at;
            $count = ChatMessage::where('chat_group_id',$g->id)->where('user_id','!=',$user->id)->when($lastRead, fn($q)=>$q->where('created_at','>',$lastRead))->count();
            if ($count) { $result[] = ['group_id'=>$g->id,'name'=>$g->name,'count'=>$count]; $total+=$count; }
        }
        return response()->json(['total'=>$total, 'groups'=>$result]);
    }
}
