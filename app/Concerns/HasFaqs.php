<?php

namespace App\Concerns;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared by any model that needs a "Frequently Asked Questions" section
 * (Products today, potentially others later) — all backed by the single
 * polymorphic `faqs` table (faqable_type/faqable_id) rather than a per-model
 * JSON column or a copy of the table for each entity.
 */
trait HasFaqs
{
    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'faqable')->orderBy('sort_order');
    }

    /**
     * Replaces the entire FAQ list for this model in one go — admin save
     * flows submit the whole list every time rather than diffing it.
     *
     * @param  array<int, array{question: array<string, string>, answer: array<string, string>}>  $items
     */
    public function syncFaqs(array $items): void
    {
        $this->faqs()->delete();

        foreach (array_values($items) as $index => $item) {
            $this->faqs()->create([
                'question' => $item['question'] ?? [],
                'answer' => $item['answer'] ?? [],
                'sort_order' => $index,
            ]);
        }
    }
}
