<?php

namespace App\Livewire\Admin\Types;

use App\Concerns\HasTranslatableFields;
use App\Concerns\StaysOnForm;
use App\Models\Type;
use App\Support\AdminActivity;
use App\Support\Slug;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use HasTranslatableFields;
    use StaysOnForm;

    public ?int $typeId = null;

    public array $name = [];

    public string $slug = '';

    /**
     * The last auto-generated slug value, so we know whether the admin has
     * manually diverged from it — see updated().
     */
    public string $autoSlug = '';

    public function mount(?int $id = null): void
    {
        if (! $id) {
            return;
        }

        $type = Type::findOrFail($id);
        $this->typeId = $id;
        $this->hydrateTranslatable($type, ['name']);
        $this->slug = $type->slug;
    }

    /**
     * Live slug-as-you-type — regenerates from the primary locale's name only
     * while the slug is still empty or still what we last generated (i.e. the
     * admin hasn't typed their own). An existing record's loaded slug never
     * matches autoSlug, so editing its name leaves the slug alone. Livewire's
     * updated{Field}() hooks don't fire for array keys like "name.en", hence
     * the catch-all.
     */
    public function updated(string $name, mixed $value): void
    {
        if (! $this->isPrimaryLocaleUpdate($name, 'name')) {
            return;
        }

        if ($this->slug === '' || $this->slug === $this->autoSlug) {
            $this->autoSlug = $this->slugFrom((string) $value);
            $this->slug = $this->autoSlug;
        }
    }

    public function updatedSlug(): void
    {
        $this->slug = Slug::lower($this->slug);
    }

    /**
     * The slug column holds 30 characters of lowercase kebab-case, so a long
     * name is cut there (without leaving a dangling hyphen).
     */
    private function slugFrom(string $name): string
    {
        return rtrim(Str::limit(Slug::make($name), 30, ''), '-');
    }

    public function save(): void
    {
        // The model derives a blank slug from the primary name on save, so
        // resolve it here first: `nullable` makes a blank field skip the unique
        // check (Laravel compares against NULL, which matches nothing), and the
        // derived value would then trip the unique index at INSERT time as a raw
        // 500 instead of a validation message. Guarded on having a name so an
        // empty form still fails on `name`, not on a confusing empty slug.
        if (blank($this->slug) && filled($this->primaryValue('name'))) {
            $this->slug = $this->slugFrom($this->primaryValue('name'));
        }

        $rules = array_merge($this->getRules(), $this->translatableRules([
            'name' => 'required|string|max:255',
        ]), [
            'slug' => ['nullable', 'string', 'max:30', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $rules['name.'.$this->primaryLocale][] = Rule::unique('types', 'name->'.$this->primaryLocale)
            ->ignore($this->typeId);

        // Slugs are the stable key the rest of the system keys off (Type::idFor,
        // pages.type, the pool subqueries), so a rename must not collide.
        $rules['slug'][] = Rule::unique('types', 'slug')->ignore($this->typeId);

        $this->validate($rules);

        $data = [
            'name' => $this->translatablePayload('name'),
            'slug' => $this->slug ?: null,
        ];

        $creating = $this->typeId === null;

        if ($this->typeId) {
            // Status is toggled from the index list (see Index::toggleStatus()),
            // not this form — omit it here so saving never silently reactivates a
            // type an admin had pulled out of circulation.
            Type::findOrFail($this->typeId)->update($data);
            $this->dispatch('notify', message: 'Type updated successfully');
        } else {
            $data['status'] = 'active';
            $this->typeId = Type::create($data)->id;
            $this->dispatch('notify', message: 'Type created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Type: {$this->primaryValue('name')}",
        );

        $this->stayOnForm($creating, 'admin.types.edit', $this->typeId);
    }

    public function render()
    {
        return view('livewire.admin.types.form')
            ->layout('layouts.admin', ['title' => $this->typeId ? 'Edit Type' : 'New Type']);
    }
}
