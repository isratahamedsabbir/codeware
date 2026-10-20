<?php

use App\Models\Page;
use App\Models\PageRevision;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

function puck(string $title): array
{
    return ['root' => ['props' => ['title' => $title]], 'content' => []];
}

it('keeps one revision per publish from the frontend, newest first', function () {
    Sanctum::actingAs($this->admin);
    $page = Page::factory()->create();
    $page->revisions()->delete(); // the factory's own creation snapshot isn't what these tests are about

    foreach (['One', 'Two', 'Three'] as $title) {
        $this->putJson("/api/v1/admin/pages/{$page->id}", ['puck_data' => puck($title)])->assertOk();
    }

    $revisions = $page->revisions()->get();

    expect($revisions)->toHaveCount(3)
        ->and($revisions->map(fn ($r) => $r->puckDataArray()['root']['props']['title'])->all())->toBe(['Three', 'Two', 'One'])
        ->and($revisions->first()->user_id)->toBe($this->admin->id);
});

it('does not add a revision for a save that leaves the body alone', function () {
    Sanctum::actingAs($this->admin);
    $page = Page::factory()->create();
    $page->revisions()->delete(); // the factory's own creation snapshot isn't what these tests are about

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['puck_data' => puck('Same')])->assertOk();
    $this->putJson("/api/v1/admin/pages/{$page->id}", ['puck_data' => puck('Same')])->assertOk();
    $this->putJson("/api/v1/admin/pages/{$page->id}", ['seo_title' => 'Only SEO'])->assertOk();

    expect($page->revisions()->count())->toBe(1);
});

it('records a revision even when nobody is signed in', function () {
    $page = Page::factory()->create();
    $page->revisions()->delete(); // the factory's own creation snapshot isn't what these tests are about

    $page->update(['puck_data' => puck('Seeded')]);

    expect($page->revisions()->count())->toBe(1)
        ->and($page->revisions()->first()->user_id)->toBeNull();
});

it('prunes the history to the newest revisions', function () {
    $page = Page::factory()->create();
    $page->revisions()->delete(); // the factory's own creation snapshot isn't what these tests are about

    foreach (range(1, PageRevision::KEEP + 5) as $i) {
        $page->update(['puck_data' => puck("v{$i}")]);
    }

    expect($page->revisions()->count())->toBe(PageRevision::KEEP)
        ->and($page->revisions()->first()->puckDataArray()['root']['props']['title'])->toBe('v'.(PageRevision::KEEP + 5));
});
