<?php

namespace App\Http\Controllers;

use App\Models\KbArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KbController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $q = KbArticle::with('author:id,name')
            ->select(['id', 'title', 'category', 'body', 'created_by', 'created_at'])
            ->where('is_published', true);
        if ($search !== '') {
            $q->where(fn ($qq) => $qq->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%"));
        }
        if ($request->filled('category')) {
            $q->where('category', $request->input('category'));
        }

        return Inertia::render('Kb/Index', [
            'articles' => $q->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'category']),
            'canManage' => auth()->user()->hasAnyRole(['SuperAdmin', 'Admin']),
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasAnyRole(['SuperAdmin', 'Admin'])) {
            abort(403);
        }
        $request->validate(['title' => 'required|string', 'category' => 'required|string', 'body' => 'required|string']);
        KbArticle::create(['title' => $request->title, 'category' => $request->category, 'body' => $request->body, 'created_by' => auth()->id()]);

        return back()->with('success', 'Article created');
    }
}
