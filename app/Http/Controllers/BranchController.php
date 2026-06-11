<?php
// app/Http/Controllers/BranchController.php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    // ── List All Branches ────────────────────────────────────────────
    public function index()
    {
        $branches = Branch::orderBy('name')->paginate(config('pagination.per_page'));
        $totalBranches = Branch::count();
        $activeBranches = Branch::where('status', 'active')->count();
        $inactiveBranches = Branch::where('status', 'inactive')->count();

        return view('branches.index', compact(
            'branches', 'totalBranches', 'activeBranches', 'inactiveBranches'
        ));
    }

    // ── Show Create Form ─────────────────────────────────────────────
    public function create()
    {
        return view('branches.create');
    }

    // ── Store New Branch ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255|unique:branches,name',
            'code'     => 'required|string|max:20|unique:branches,code',
            'location' => 'required|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
            'status'   => 'required|in:active,inactive',
        ]);

        Branch::create($validated);

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$validated['name']}' created successfully.");
    }

    // ── Show Edit Form ───────────────────────────────────────────────
    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    // ── Update Branch ────────────────────────────────────────────────
    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255|unique:branches,name,' . $branch->id,
            'code'     => 'required|string|max:20|unique:branches,code,' . $branch->id,
            'location' => 'required|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
            'status'   => 'required|in:active,inactive',
        ]);

        $branch->update($validated);

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$branch->name}' updated successfully.");
    }

    // ── Delete Branch ────────────────────────────────────────────────
    public function destroy(Branch $branch)
    {
        // Prevent deleting if branch has staff or customers
        if ($branch->users()->count() > 0 || $branch->customers()->count() > 0) {
            return back()->with('error', "Cannot delete '{$branch->name}' — it has staff or customers assigned.");
        }

        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', "Branch '{$branch->name}' deleted successfully.");
    }
}
