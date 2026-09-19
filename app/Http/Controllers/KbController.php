<?php

namespace App\Http\Controllers;

use App\Models\KbArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KbController extends Controller
{
    public function index(Request $request)
    {
        $q = KbArticle::with('author')->where('is_published', true);
        if ($request->search) $q->where(fn($qq)=>$qq->where('title','like',"%{$request->search}%")->orWhere('body','like',"%{$request->search}%"));
        if ($request->category) $q->where('category',$request->category);
        return Inertia::render('Kb/Index', [
            'articles'=>$q->latest()->paginate(20)->withQueryString(),
            'filters'=>$request->only(['search','category']),
            'canManage'=> auth()->user()->hasAnyRole(['SuperAdmin','Admin']),
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['SuperAdmin','Admin'])) abort(403);
        $request->validate(['title'=>'required|string','category'=>'required|string','body'=>'required|string']);
        KbArticle::create(['title'=>$request->title,'category'=>$request->category,'body'=>$request->body,'created_by'=>auth()->id()]);
        return back()->with('success','Article created');
    }
}
