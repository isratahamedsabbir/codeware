<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row of any generated `crud_*` table. Always obtained through
 * Crud::recordModel(), which points it at the right table.
 */
class CrudRecord extends Model
{
    protected $guarded = [];
}
