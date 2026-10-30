<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A snapshot of a Page's body as of one save — one row per publish. Both
 * columns hold the raw JSON exactly as the pages table stored it (content is
 * a per-locale translation map, puck_data the visual-editor tree), so
 * restoring is a straight write back. Null means the page had none of that
 * part at the time. user_id is null when the save had no signed-in user.
 */
class PageRevision extends Model
{
    /** Revisions kept per page; older ones are pruned on save. */
    public const KEEP = 50;

    protected $fillable = ['page_id', 'user_id', 'content', 'puck_data'];

    /** @return array<string, mixed>|null */
    public function contentData(): ?array
    {
        return $this->decode($this->content);
    }

    /** @return array<string, mixed>|null */
    public function puckDataArray(): ?array
    {
        return $this->decode($this->puck_data);
    }

    private function decode(?string $json): ?array
    {
        $decoded = $json === null ? null : json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
