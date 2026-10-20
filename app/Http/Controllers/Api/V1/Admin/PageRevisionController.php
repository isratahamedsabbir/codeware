<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageRevision;
use App\Support\AdminActivity;
use Illuminate\Http\JsonResponse;

/**
 * A page's publish history (see Page::booted()): newest first, at most
 * PageRevision::KEEP per page. Admin-only, like the rest of /admin/pages.
 */
class PageRevisionController extends Controller
{
    public function index(int $id): JsonResponse
    {
        $page = Page::findOrFail($id);

        $revisions = $page->revisions()->with('user:id,name')->get();

        return response()->json([
            'data' => $revisions->map(fn (PageRevision $revision) => $this->summary($revision))->values(),
            'meta' => ['total' => $revisions->count(), 'max' => PageRevision::KEEP],
        ]);
    }

    public function show(int $id, int $revisionId): JsonResponse
    {
        $revision = Page::findOrFail($id)->revisions()->with('user:id,name')->findOrFail($revisionId);

        return response()->json([
            'data' => $this->summary($revision) + [
                'content' => $revision->contentData(),
                'puck_data' => $revision->puckDataArray(),
            ],
        ]);
    }

    /**
     * Writes the revision's body back to the page. That save is recorded as a new
     * revision on top, so the history stays linear and a restore can be undone.
     */
    public function restore(int $id, int $revisionId): JsonResponse
    {
        $page = Page::findOrFail($id);
        $revision = $page->revisions()->findOrFail($revisionId);

        if (! $page->restoreRevision($revision)) {
            return response()->json(['message' => 'This revision has nothing to restore.'], 422);
        }

        AdminActivity::log('updated', "Page #{$page->id}: restored revision #{$revision->id}");

        return response()->json(['data' => ['id' => $page->id, 'restored_revision_id' => $revision->id]]);
    }

    /** @return array<string, mixed> */
    private function summary(PageRevision $revision): array
    {
        return [
            'id' => $revision->id,
            'page_id' => $revision->page_id,
            'user' => $revision->user ? ['id' => $revision->user->id, 'name' => $revision->user->name] : null,
            'has_content' => $revision->content !== null,
            'has_puck_data' => $revision->puck_data !== null,
            'created_at' => $revision->created_at->toIso8601String(),
            'created_at_display' => $revision->created_at->toDisplay(),
        ];
    }
}
