<?php

namespace App\Models;

use App\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasCreator, HasFactory;

    protected $fillable = ['name', 'values'];

    protected function casts(): array
    {
        return [
            'values' => 'array',
        ];
    }
}
