<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        $rootCategories = Category::root()->ordered()->get();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'rootCategories' => $rootCategories,
        ]);
    }

    public function tree()
    {
        $categories = Category::with('children')
            ->root()
            ->ordered()
            ->get();

        return Inertia::render('Admin/Categories/Tree', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        Category::create($validated);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // ✅ Prevent self-parent assignment
        if ($validated['parent_id'] == $category->id) {
            return redirect()->back()->withErrors(['parent_id' => 'A category cannot be its own parent.']);
        }

        // ✅ Prevent circular references — walk up from the proposed parent
        //    and abort if we ever hit the category being edited.
        if ($validated['parent_id'] && $this->wouldCreateCycle($category->id, $validated['parent_id'])) {
            return redirect()->back()->withErrors([
                'parent_id' => 'This would create a circular reference (the selected parent is a descendant of this category).',
            ]);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    /**
     * Determine whether assigning $newParentId as the parent of $categoryId
     * would create a cycle. Walks up the parent chain from $newParentId.
     */
    private function wouldCreateCycle(int $categoryId, int $newParentId): bool
    {
        $cursor = Category::find($newParentId);
        $guard = 0;

        while ($cursor && $guard < 100) {
            if ($cursor->id === $categoryId) {
                return true;
            }
            $cursor = $cursor->parent_id ? Category::find($cursor->parent_id) : null;
            $guard++;
        }

        return false;
    }

    public function destroy(Category $category)
    {
        // Check if category has children
        if ($category->children()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete category with subcategories. Move or delete subcategories first.');
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }

    public function toggleActive(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        return redirect()->back()->with('success', 'Category status updated successfully.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['categories'] as $categoryData) {
            Category::where('id', $categoryData['id'])
                ->update(['sort_order' => $categoryData['sort_order']]);
        }

        return response()->json(['success' => true]);
    }
}