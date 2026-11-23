<?php

namespace App\Modules\Supply\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Supply\Models\SupplyItemCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupplyItemCategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorizeAdmin();

        return Inertia::render('Admin/SupplyCategories', [
            'categories' => SupplyItemCategory::withCount('items')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'description', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('supply_item_categories', 'name')],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('supply_item_categories', 'code')],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['code'] = ($validated['code'] ?? null) ?: null;
        $validated['is_active'] = $request->boolean('is_active', true);
        SupplyItemCategory::create($validated);

        return back()->with('success', 'Supply item category created.');
    }

    public function update(Request $request, SupplyItemCategory $supplyItemCategory): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('supply_item_categories', 'name')->ignore($supplyItemCategory->id)],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('supply_item_categories', 'code')->ignore($supplyItemCategory->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['code'] = ($validated['code'] ?? null) ?: null;
        $validated['is_active'] = $request->boolean('is_active');
        $supplyItemCategory->update($validated);

        return back()->with('success', 'Supply item category updated.');
    }

    public function destroy(SupplyItemCategory $supplyItemCategory): RedirectResponse
    {
        $this->authorizeAdmin();
        $supplyItemCategory->delete();

        return back()->with('success', 'Supply item category deleted.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['SuperAdmin', 'Admin']), 403);
    }
}
