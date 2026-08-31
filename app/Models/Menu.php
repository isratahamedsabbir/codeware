<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A named menu (e.g. "Admin Menu", "Frontend Menu") — exists independently of
 * whether it has any MenuItem rows yet, so a freshly-created empty menu isn't
 * lost the moment the admin navigates away before adding its first item.
 */
class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'group', 'slug');
    }
}
