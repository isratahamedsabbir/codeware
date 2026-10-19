<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crud extends Model
{
    protected $fillable = ['name', 'slug', 'table_name', 'icon', 'fields'];

    protected function casts(): array
    {
        return ['fields' => 'array'];
    }

    /** A bare model bound to this CRUD's generated table. */
    public function recordModel(): CrudRecord
    {
        return (new CrudRecord)->setTable($this->table_name);
    }
}
