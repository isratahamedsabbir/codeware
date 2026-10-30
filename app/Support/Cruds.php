<?php

namespace App\Support;

use App\Models\Crud;
use App\Models\MenuItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The CRUD builder: turns a definition (name + fields) into a real database
 * table, and exposes what the generic records screen and the sidebar need.
 */
class Cruds
{
    public const MENU_GROUP = 'CRUD';

    public const TYPES = [
        'text' => 'Text',
        'textarea' => 'Long text',
        'email' => 'Email',
        'url' => 'URL',
        'number' => 'Number',
        'decimal' => 'Decimal',
        'boolean' => 'Yes / No',
        'date' => 'Date',
        'datetime' => 'Date & time',
        'image' => 'Image',
        'select' => 'Select (dropdown)',
    ];

    /** Icons offered in the picker modal; any other Heroicon name can still be typed. */
    public const ICONS = [
        'rectangle-stack', 'users', 'user-group', 'cube', 'star', 'chat-bubble-left-right', 'briefcase',
        'calendar', 'photo', 'document-text', 'tag', 'map-pin', 'heart', 'shopping-bag', 'gift', 'bell',
        'newspaper', 'academic-cap', 'building-office', 'truck', 'clipboard-document-list', 'trophy',
    ];

    private const RESERVED = ['id', 'created_at', 'updated_at'];

    public static function tableFor(string $name): string
    {
        return 'crud_'.str_replace('-', '_', Str::slug($name));
    }

    public static function dropColumns(Crud $crud, array $names): void
    {
        if ($names !== []) {
            Schema::dropColumns($crud->table_name, $names);
        }
    }

    /** A CSV cell that spreadsheets will not run as a formula. */
    public static function csvCell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    /** Every field gets a `name` (new ones derived from the label — unique, never reserved). */
    public static function nameFields(array $fields, array $taken = []): array
    {
        $used = array_merge(self::RESERVED, $taken);

        return array_map(function (array $field) use (&$used) {
            if (empty($field['name'])) {
                $base = Str::slug((string) $field['label'], '_');
                $base = ($base === '' || ! ctype_alpha($base[0])) ? 'field_'.$base : $base;
                $base = rtrim(Str::limit($base, 50, ''), '_');
                $name = $base;

                for ($i = 2; in_array($name, $used, true); $i++) {
                    $name = $base.'_'.$i;
                }

                $field['name'] = $name;
            }

            $used[] = $field['name'];

            return $field;
        }, $fields);
    }

    public static function createTable(Crud $crud): void
    {
        Schema::create($crud->table_name, function (Blueprint $table) use ($crud) {
            $table->id();
            foreach ($crud->fields as $field) {
                self::column($table, $field);
            }
            $table->timestamps();
        });
    }

    /** @param array<int, array> $fields */
    public static function addColumns(Crud $crud, array $fields): void
    {
        Schema::table($crud->table_name, function (Blueprint $table) use ($fields) {
            foreach ($fields as $field) {
                self::column($table, $field);
            }
        });
    }

    public static function dropTable(Crud $crud): void
    {
        Schema::dropIfExists($crud->table_name);
    }

    private static function column(Blueprint $table, array $field): void
    {
        $name = $field['name'];

        match ($field['type']) {
            'textarea' => $table->text($name)->nullable(),
            'number' => $table->bigInteger($name)->nullable(),
            'decimal' => $table->decimal($name, 15, 2)->nullable(),
            'boolean' => $table->boolean($name)->default(false),
            'date' => $table->date($name)->nullable(),
            'datetime' => $table->dateTime($name)->nullable(),
            default => $table->string($name)->nullable(),
        };
    }

    public static function options(array $field): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ($field['options'] ?? '')))));
    }

    /** Validation rules for the records form, keyed `form.<name>`. */
    public static function rules(Crud $crud): array
    {
        $rules = [];

        foreach ($crud->fields as $field) {
            $r = [($field['required'] ?? false) ? 'required' : 'nullable'];

            $r = array_merge($r, match ($field['type']) {
                'email' => ['email', 'max:255'],
                'url' => ['url', 'max:255'],
                'datetime' => ['date'],
                'image' => ['string', 'max:255'],
                'number' => ['integer'],
                'decimal' => ['numeric'],
                'boolean' => ['boolean'],
                'date' => ['date'],
                'textarea' => ['string', 'max:65000'],
                'select' => ['in:'.implode(',', self::options($field))],
                default => ['string', 'max:255'],
            });

            $rules['form.'.$field['name']] = $r;
        }

        return $rules;
    }

    /** Adds the CRUD group (builder link + one link per CRUD) to the sidebar. */
    public static function extendMenu(Collection $menu): Collection
    {
        if (! Gate::allows('access-admin-system') || ! Schema::hasTable('cruds')) {
            return $menu;
        }

        $children = collect([new MenuItem([
            'label' => 'CRUD Builder',
            'icon' => 'wrench-screwdriver',
            'route_name' => 'admin.cruds',
        ])]);

        foreach (Crud::query()->orderBy('name')->get() as $crud) {
            $children->push(new MenuItem([
                'label' => $crud->name,
                'icon' => MenuItem::iconExists($crud->icon) ? $crud->icon : 'rectangle-stack',
                'url' => route('admin.cruds.records', $crud->slug),
            ]));
        }

        $group = new MenuItem(['label' => self::MENU_GROUP, 'icon' => 'table-cells', 'is_group' => true]);
        $group->id = -2;
        $group->setRelation('children', $children);

        // Goes above the Plugins dropdown, which in turn sits directly above About.
        $anchor = $menu->search(fn (MenuItem $item) => $item->is_group && $item->label === Plugins::MENU_GROUP);
        $anchor = $anchor === false ? $menu->search(fn (MenuItem $item) => $item->route_name === 'admin.about') : $anchor;

        $anchor === false ? $menu->push($group) : $menu->splice($anchor, 0, [$group]);

        return $menu->values();
    }
}
