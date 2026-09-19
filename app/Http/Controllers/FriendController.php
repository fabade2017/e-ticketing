<?php

namespace App\Http\Controllers;

use App\Models\ChatGroup;
use App\Models\FriendRequest;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FriendController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $search = $request->input('search');
        $q = User::with('roles')->where('id','!=',$user->id);
        if ($search) $q->where(fn($qq)=>$qq->where('name','like',"%{$search}%")->orWhere('service_number','like',"%{$search}%")->orWhere('rank','like',"%{$search}%"));

        $friendIds = Friendship::where('user_id',$user->id)->pluck('friend_id');
        $friends = User::whereIn('id',$friendIds)->with('roles')->get()->map(fn($u)=>$u->only(['id','name','rank','service_number','unit']));

        $pendingReceived = FriendRequest::with(['sender','group'])->where('receiver_id',$user->id)->where('status','pending')->latest()->get();
        $pendingSent = FriendRequest::with(['receiver','group'])->where('sender_id',$user->id)->where('status','pending')->latest()->get();

        // also group join requests where user is group admin or superadmin
        $groupRequests = collect();
        if ($user->hasAnyRole(['SuperAdmin','Admin'])) {
            $groupRequests = FriendRequest::with(['sender','group'])->where('type','group_join')->where('status','pending')->latest()->get();
        } else {
            $myGroups = ChatGroup::where('created_by',$user->id)->pluck('id');
            if ($myGroups->isNotEmpty()) $groupRequests = FriendRequest::with(['sender','group'])->whereIn('group_id',$myGroups)->where('status','pending')->latest()->get();
        }

        return Inertia::render('Friends/Index', [
            'users'=> $q->paginate(24)->withQueryString(),
            'friends'=> $friends,
            'pendingReceived'=> $pendingReceived,
            'pendingSent'=> $pendingSent,
            'groupRequests'=> $groupRequests,
            'filters'=> $request->only('search'),
        ]);
    }

    public function sendColleague(Request $request)
    {
        $request->validate(['receiver_id'=>'required|exists:users,id','message'=>'nullable|string|max:200']);
        $user = $request->user();
        if ($request->receiver_id == $user->id) return back()->withErrors(['receiver_id'=>'Cannot add yourself']);
        if (Friendship::where('user_id',$user->id)->where('friend_id',$request->receiver_id)->exists()) return back()->withErrors(['receiver_id'=>'Already colleagues']);
        if (FriendRequest::where('sender_id',$user->id)->where('receiver_id',$request->receiver_id)->where('status','pending')->exists()) return back()->withErrors(['receiver_id'=>'Request already pending']);

        FriendRequest::create(['sender_id'=>$user->id,'receiver_id'=>$request->receiver_id,'type'=>'colleague','message'=>$request->message]);
        return back()->with('success','Colleague request sent — they will see it under Friends → Received');
    }

    public function sendGroupJoin(Request $request)
    {
        $request->validate(['group_id'=>'required|exists:chat_groups,id','message'=>'nullable|string|max:200']);
        $user = $request->user();
        $group = ChatGroup::findOrFail($request->group_id);
        if ($group->members()->where('users.id',$user->id)->exists()) return back()->withErrors(['group_id'=>'Already a member']);
        if (FriendRequest::where('sender_id',$user->id)->where('group_id',$group->id)->where('status','pending')->exists()) return back()->withErrors(['group_id'=>'Join request already pending']);

        FriendRequest::create(['sender_id'=>$user->id,'group_id'=>$group->id,'type'=>'group_join','message'=>$request->message]);
        return back()->with('success','Request to join '.$group->name.' sent');
    }

    public function accept(Request $request, FriendRequest $friendRequest)
    {
        $user = $request->user();
        // authorize: receiver for colleague, group admin for group_join
        if ($friendRequest->type === 'colleague') {
            if ($friendRequest->receiver_id !== $user->id && !$user->hasRole('SuperAdmin')) abort(403);
            $friendRequest->update(['status'=>'accepted']);
            Friendship::firstOrCreate(['user_id'=>$friendRequest->sender_id,'friend_id'=>$friendRequest->receiver_id]);
            Friendship::firstOrCreate(['user_id'=>$friendRequest->receiver_id,'friend_id'=>$friendRequest->sender_id]);
            // create private 1-1 chat group if not exists
            $existing = ChatGroup::where('is_private',true)->whereHas('members', fn($q)=>$q->where('users.id',$friendRequest->sender_id))->whereHas('members', fn($q)=>$q->where('users.id',$friendRequest->receiver_id))->first();
            if (!$existing) {
                $sender = User::find($friendRequest->sender_id); $recv = User::find($friendRequest->receiver_id);
                $g = ChatGroup::create(['name'=> $sender->name.' & '.$recv->name,'description'=>'Private chat','is_private'=>true,'created_by'=>$friendRequest->sender_id]);
                $g->members()->attach([$sender->id => ['role'=>'member'], $recv->id => ['role'=>'member']]);
            }
        } else {
            $group = $friendRequest->group;
            if (!$group) abort(404);
            $isGroupAdmin = $group->created_by === $user->id || $group->members()->wherePivot('role','admin')->where('users.id',$user->id)->exists() || $user->hasAnyRole(['SuperAdmin','Admin']);
            if (!$isGroupAdmin) abort(403);
            $friendRequest->update(['status'=>'accepted']);
            $group->members()->syncWithoutDetaching([$friendRequest->sender_id => ['role'=>'member']]);
        }
        return back()->with('success','Request accepted — now colleagues/can chat');
    }

    public function reject(Request $request, FriendRequest $friendRequest)
    {
        $user = $request->user();
        if ($friendRequest->type === 'colleague' && $friendRequest->receiver_id !== $user->id && !$user->hasRole('SuperAdmin')) abort(403);
        if ($friendRequest->type === 'group_join') {
            $group = $friendRequest->group;
            $isGroupAdmin = $group && ($group->created_by === $user->id || $group->members()->wherePivot('role','admin')->where('users.id',$user->id)->exists() || $user->hasAnyRole(['SuperAdmin','Admin']));
            if (!$isGroupAdmin) abort(403);
        }
        $friendRequest->update(['status'=>'rejected']);
        return back()->with('success','Request rejected');
    }
}
