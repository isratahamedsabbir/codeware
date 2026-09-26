<?php

use App\Livewire\Admin\Portfolio\Experiences\Form as ExperienceForm;
use App\Livewire\Admin\Portfolio\Experiences\Index as ExperiencesIndex;
use App\Livewire\Admin\Portfolio\Projects\Form as ProjectForm;
use App\Livewire\Admin\Portfolio\Projects\Index as ProjectsIndex;
use App\Livewire\Admin\Portfolio\Skills\Form as SkillForm;
use App\Livewire\Admin\Portfolio\Skills\Index as SkillsIndex;
use App\Models\Feature;
use App\Models\MenuItem;
use App\Models\PortfolioExperience;
use App\Models\PortfolioProject;
use App\Models\PortfolioSkill;
use App\Models\User;
use App\Support\ContentCache;
use App\Support\Features;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('guests and non-admins are blocked from the portfolio screens', function () {
    auth()->logout();
    $this->get(route('admin.portfolio-projects'))->assertRedirect('/login');

    $this->actingAs(User::factory()->create());
    $this->get(route('admin.portfolio-projects'))->assertForbidden();
});

it('renders each portfolio index with its rows', function () {
    PortfolioProject::factory()->create(['title' => ['en' => 'SaaS Starter Platform', 'bn' => '']]);
    PortfolioExperience::factory()->create(['role' => ['en' => 'Backend Developer', 'bn' => '']]);
    PortfolioSkill::factory()->create(['name' => ['en' => 'Laravel', 'bn' => ''], 'group' => 'Backend']);

    Livewire::test(ProjectsIndex::class)->assertOk()->assertSee('SaaS Starter Platform');
    Livewire::test(ExperiencesIndex::class)->assertOk()->assertSee('Backend Developer');
    Livewire::test(SkillsIndex::class)->assertOk()->assertSee('Laravel')->assertSee('Backend');
});

it('filters each portfolio list by search', function () {
    PortfolioProject::factory()->create(['title' => ['en' => 'Consulting', 'bn' => '']]);
    PortfolioProject::factory()->create(['title' => ['en' => 'Installation', 'bn' => '']]);

    Livewire::test(ProjectsIndex::class)
        ->set('search', 'Consulting')
        ->assertSee('Consulting')
        ->assertDontSee('Installation');
});

it('creates a project, inactive by default, keeping the admin out of the theme until it is switched on', function () {
    Livewire::test(ProjectForm::class)
        ->set('title.en', 'Hotel Booking System')
        ->set('description.en', 'Multi-property booking platform.')
        ->set('icon', '🏨')
        ->set('tech', ['Laravel', 'MySQL'])
        ->set('stats', 'In Production')
        ->set('link', 'https://example.com/hotel')
        ->call('save');

    $project = PortfolioProject::sole();

    expect($project->getTranslation('title', 'en', false))->toBe('Hotel Booking System')
        ->and($project->getTranslation('description', 'en', false))->toBe('Multi-property booking platform.')
        ->and($project->tech)->toBe(['Laravel', 'MySQL'])
        ->and($project->stats)->toBe('In Production')
        ->and($project->link)->toBe('https://example.com/hotel')
        ->and($project->status)->toBe('inactive');
});

it('drops blank technology rows instead of storing empty chips', function () {
    Livewire::test(ProjectForm::class)
        ->set('title.en', 'Inventory Automation')
        ->set('tech', ['PHP', '', '  '])
        ->call('save');

    expect(PortfolioProject::sole()->tech)->toBe(['PHP']);
});

it('requires a primary-locale project title', function () {
    Livewire::test(ProjectForm::class)
        ->set('title.en', '')
        ->call('save')
        ->assertHasErrors(['title.en']);
});

it('updates an existing project', function () {
    $project = PortfolioProject::factory()->published()->create(['title' => ['en' => 'Consulting', 'bn' => '']]);

    Livewire::test(ProjectForm::class, ['id' => $project->id])
        ->assertSet('title.en', 'Consulting')
        ->set('title.en', 'Premium Consulting')
        ->call('save');

    expect($project->fresh()->getTranslation('title', 'en', false))->toBe('Premium Consulting');
});

it('does not reset an inactive project back to active when saved from the form', function () {
    $project = PortfolioProject::factory()->draft()->create();

    Livewire::test(ProjectForm::class, ['id' => $project->id])
        ->set('title.en', 'Consulting Plus')
        ->call('save');

    expect($project->fresh()->status)->toBe('inactive');
});

it('creates an experience entry with its period', function () {
    Livewire::test(ExperienceForm::class)
        ->set('role.en', 'Full Stack Developer')
        ->set('company.en', 'Your Company')
        ->set('period', '2024 - Present')
        ->set('description.en', 'Owning features end-to-end.')
        ->call('save');

    $experience = PortfolioExperience::sole();

    expect($experience->getTranslation('role', 'en', false))->toBe('Full Stack Developer')
        ->and($experience->getTranslation('company', 'en', false))->toBe('Your Company')
        ->and($experience->period)->toBe('2024 - Present')
        ->and($experience->status)->toBe('inactive');
});

it('requires a primary-locale experience role', function () {
    Livewire::test(ExperienceForm::class)
        ->set('role.en', '')
        ->call('save')
        ->assertHasErrors(['role.en']);
});

it('creates a skill in a group, the free-text column the theme builds its columns from', function () {
    Livewire::test(SkillForm::class)
        ->set('name.en', 'Redis')
        ->set('group', 'Backend')
        ->set('icon', '⚡')
        ->set('description.en', 'Caching & Queues')
        ->call('save');

    $skill = PortfolioSkill::sole();

    expect($skill->getTranslation('name', 'en', false))->toBe('Redis')
        ->and($skill->group)->toBe('Backend')
        ->and($skill->status)->toBe('inactive');
});

it('requires a group on a skill, since the theme has no column to file it under otherwise', function () {
    Livewire::test(SkillForm::class)
        ->set('name.en', 'Redis')
        ->set('group', '')
        ->call('save')
        ->assertHasErrors(['group']);
});

it('appends a new skill to the end of its own group, not the whole list', function () {
    PortfolioSkill::factory()->create(['name' => ['en' => 'Laravel', 'bn' => ''], 'group' => 'Backend', 'sort_order' => 1]);
    PortfolioSkill::factory()->create(['name' => ['en' => 'Livewire', 'bn' => ''], 'group' => 'Frontend & Tools', 'sort_order' => 1]);

    Livewire::test(SkillForm::class)
        ->set('name.en', 'Redis')
        ->set('group', 'Backend')
        ->call('save');

    expect(PortfolioSkill::where('group', 'Backend')->max('sort_order'))->toBe(2)
        ->and(PortfolioSkill::where('group', 'Frontend & Tools')->max('sort_order'))->toBe(1);
});

it('toggles a row\'s status from the index', function () {
    $project = PortfolioProject::factory()->published()->create();
    $skill = PortfolioSkill::factory()->published()->create();

    Livewire::test(ProjectsIndex::class)->call('toggleStatus', $project->id);
    Livewire::test(SkillsIndex::class)->call('toggleStatus', $skill->id);

    expect($project->refresh()->status)->toBe('inactive')
        ->and($skill->refresh()->status)->toBe('inactive');
});

it('deletes a row, and bulk-deletes a selection', function () {
    $project = PortfolioProject::factory()->create();
    $first = PortfolioExperience::factory()->create();
    $second = PortfolioExperience::factory()->create();

    Livewire::test(ProjectsIndex::class)
        ->call('confirmDelete', $project->id)
        ->call('delete');

    expect(PortfolioProject::find($project->id))->toBeNull();

    Livewire::test(ExperiencesIndex::class)
        ->set('selectedIds', [$first->id, $second->id])
        ->call('confirmBulkDelete')
        ->call('bulkDelete');

    expect(PortfolioExperience::count())->toBe(0);
});

it('reorders rows by drag-and-drop, which is the only order the theme prints them in', function () {
    $first = PortfolioProject::factory()->create(['sort_order' => 1]);
    $second = PortfolioProject::factory()->create(['sort_order' => 2]);

    Livewire::test(ProjectsIndex::class)
        ->call('reorder', [$second->id, $first->id]);

    expect($first->refresh()->sort_order)->toBe(1)
        ->and($second->refresh()->sort_order)->toBe(0);
});

it('reordering busts the shared content cache, so the public theme sees it immediately', function () {
    $project = PortfolioProject::factory()->create(['sort_order' => 1]);
    $other = PortfolioProject::factory()->create(['sort_order' => 2]);

    $before = ContentCache::version();

    Livewire::test(ProjectsIndex::class)->call('reorder', [$other->id, $project->id]);

    expect(ContentCache::version())->toBeGreaterThan($before);
});

it('groups the three screens under one Portfolio sidebar entry', function () {
    $this->seed(AdminMenuSeeder::class);

    $group = MenuItem::where('group', MenuItem::GROUP_ADMIN_SIDEBAR)->where('label', 'Portfolio')->sole();

    expect($group->is_group)->toBeTrue()
        ->and($group->children->pluck('route_name')->all())->toBe([
            'admin.portfolio-projects',
            'admin.portfolio-experiences',
            'admin.portfolio-skills',
        ]);
});

it('404s all three screens and hides the group once the portfolio feature is off', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Projects');

    Feature::updateOrCreate(
        ['key' => 'portfolio'],
        ['label' => Features::ALL['portfolio'], 'is_enabled' => false],
    );

    $this->get(route('admin.portfolio-projects'))->assertNotFound();
    $this->get(route('admin.portfolio-experiences'))->assertNotFound();
    $this->get(route('admin.portfolio-skills'))->assertNotFound();

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Portfolio');
});
