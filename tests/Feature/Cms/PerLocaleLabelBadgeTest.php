<?php

use App\Livewire\Admin\Pages\Form;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    // Two active languages, because a single one would render one tab and the
    // point of the badge is telling the per-locale fields apart.
    Language::create(['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    $this->html = Livewire::test(Form::class, ['pageId' => Page::factory()->create()->id])->html();
});

/**
 * The visible text of every rendered <ui-label>, with each nested <span> — the
 * locale badge, the required-field asterisk — flattened to its own " | "
 * segment. A label badged with its locale therefore reads as 'Title | * | en |'.
 */
function renderedLabels(string $html): array
{
    preg_match_all('#<ui-label[^>]*>(.*?)</ui-label>#s', $html, $matches);

    return array_map(function (string $block) {
        $block = preg_replace('#<!--.*?-->#s', '', $block);
        $block = preg_replace('#</?span[^>]*>#', ' | ', $block);
        $block = html_entity_decode(strip_tags($block), ENT_QUOTES);
        $block = preg_replace('/\s+/', ' ', $block);
        $block = preg_replace('/\s*\|\s*/', ' | ', $block);
        $block = preg_replace('/(?: \| )+/', ' | ', $block);

        return trim($block);
    }, $matches[1]);
}

it('badges every per-locale label with the locale it writes', function () {
    $labels = renderedLabels($this->html);

    expect($labels)->toContain('Title | * | en |')
        ->and($labels)->toContain('Title | bn |');
});

it('badges the secondary per-locale fields too, not just the required one', function () {
    $labels = renderedLabels($this->html);

    // Meta/OG/Twitter copy is per-locale, so a translator landing on the
    // Bengali tab can tell it apart from the fields that never change.
    expect($labels)->toContain('Meta Title | en |')
        ->and($labels)->toContain('Meta Description | bn |')
        ->and($labels)->toContain('OG Title | en |')
        ->and($labels)->toContain('Twitter Description | bn |');
});

it('leaves a field that is not translated unbadged', function () {
    $labels = renderedLabels($this->html);

    // The whole reason the badge exists: these sit inside the locale tabs but
    // hold one value for every language, so a code on them would be a lie.
    expect($labels)->toContain('Slug')
        ->and($labels)->toContain('OG Image')
        ->and($labels)->toContain('Twitter Image');
});
