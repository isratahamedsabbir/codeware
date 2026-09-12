<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasTranslations;

    public array $translatable = ['question', 'answer'];

    protected $fillable = ['faqable_type', 'faqable_id', 'question', 'answer', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function faqable(): MorphTo
    {
        return $this->morphTo();
    }
}
