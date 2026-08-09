<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\KnowledgeEntryRequest;
use App\Http\Resources\KnowledgeEntryResource;
use App\Models\KnowledgeEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeEntryController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', KnowledgeEntry::class);

        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type');
        $isActive = $request->query('is_active');

        $entries = KnowledgeEntry::query()
            ->when($search, fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            }))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when(in_array($isActive, ['1', '0'], true), fn ($q) => $q->where('is_active', $isActive === '1'))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $types = KnowledgeEntry::query()
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->all();

        return Inertia::render('Knowledge/Index', [
            'entries' => KnowledgeEntryResource::collection($entries),
            'types' => $types,
            'filters' => ['search' => $search, 'type' => $type, 'is_active' => $isActive],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', KnowledgeEntry::class);

        return Inertia::render('Knowledge/Form', ['entry' => null]);
    }

    public function store(KnowledgeEntryRequest $request): RedirectResponse
    {
        Gate::authorize('create', KnowledgeEntry::class);

        KnowledgeEntry::create($request->validated());

        return redirect()->route('admin.knowledge.index')->with('success', 'เพิ่มความรู้เรียบร้อยแล้ว');
    }

    public function edit(KnowledgeEntry $knowledge): Response
    {
        Gate::authorize('update', $knowledge);

        return Inertia::render('Knowledge/Form', ['entry' => new KnowledgeEntryResource($knowledge)]);
    }

    public function update(KnowledgeEntryRequest $request, KnowledgeEntry $knowledge): RedirectResponse
    {
        Gate::authorize('update', $knowledge);

        $knowledge->update($request->validated());

        return redirect()->route('admin.knowledge.index')->with('success', 'บันทึกการแก้ไขเรียบร้อยแล้ว');
    }

    public function destroy(KnowledgeEntry $knowledge): RedirectResponse
    {
        Gate::authorize('delete', $knowledge);

        $knowledge->delete();

        return redirect()->route('admin.knowledge.index')->with('success', 'ลบความรู้เรียบร้อยแล้ว');
    }
}
