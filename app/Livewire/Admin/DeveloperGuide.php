<?php

namespace App\Livewire\Admin;

use App\Support\Plugins;
use App\Support\Themes;
use Livewire\Component;

/**
 * Developer Guide — the panel's own documentation, rendered inside the admin
 * layout.
 *
 * Everything on this screen that can be read out of the app is: the plugin list
 * is Plugins::all(), the theme list is Themes::all(), the feature list is
 * Features::ALL. Only the prose explaining how those subsystems work is static,
 * which is the point — a page that documented its own behaviour by paraphrase
 * would be the first thing to go stale.
 *
 * sections() is the table of contents. It lives here rather than inline in the
 * view so the sticky sidebar and the twelve <x-admin-doc-section> blocks can be
 * checked against each other by a test: if a section is renamed, moved or
 * dropped without the list following it, the test fails rather than the link
 * quietly going nowhere.
 */
class DeveloperGuide extends Component
{
    /**
     * Section anchors and labels, in reading order. Keyed by the same id the
     * section blocks pass to <x-admin-doc-section id="...">, which is where the
     * rendered anchor comes from ("doc-" . $id).
     *
     * @return array<string, array{icon: string, label: string}>
     */
    public function sections(): array
    {
        return [
            'plugins' => ['icon' => 'puzzle-piece', 'label' => 'Plugins'],
            'themes' => ['icon' => 'swatch', 'label' => 'Themes'],
            'features' => ['icon' => 'power', 'label' => 'Features'],
            'settings' => ['icon' => 'cog-6-tooth', 'label' => 'Settings'],
            'pages' => ['icon' => 'document-text', 'label' => 'Pages'],
            'page-content' => ['icon' => 'squares-2x2', 'label' => 'What a page contains'],
            'cms-content' => ['icon' => 'tag', 'label' => 'CMS content'],
            'menus' => ['icon' => 'bars-3', 'label' => 'Menus'],
            'media' => ['icon' => 'photo', 'label' => 'Media Library'],
            'moderation' => ['icon' => 'chat-bubble-left-right', 'label' => 'Comments & Reviews'],
            'api' => ['icon' => 'globe-alt', 'label' => 'REST API'],
            'reference' => ['icon' => 'table-cells', 'label' => 'Reference tables'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.developer-guide', [
            'sections' => $this->sections(),
            'plugins' => Plugins::all(),
            'activePluginSlugs' => array_keys(Plugins::active()),
            'themes' => Themes::all(),
            'activeTheme' => Themes::active(),
        ])->layout('layouts.admin', ['title' => 'Developer Guide']);
    }
}
