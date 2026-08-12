<?php

namespace App\Models;

use Database\Factories\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'subject_template',
        'body_template',
        'variables',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'active' => 'boolean',
        ];
    }
}
