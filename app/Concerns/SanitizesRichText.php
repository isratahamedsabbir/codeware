<?php

namespace App\Concerns;

use App\Support\Html;

/**
 * Purifies the model's $richTextFields once, on save, so stored HTML is already
 * safe. Fields may be plain strings or translatable (per-locale) values.
 * Existing rows are cleaned by `php artisan richtext:sanitize`.
 */
trait SanitizesRichText
{
    protected static function bootSanitizesRichText(): void
    {
        static::saving(function ($model) {
            $model->sanitizeRichText();
        });
    }

    public function sanitizeRichText(bool $force = false): void
    {
        foreach ($this->richTextFields as $field) {
            if (! $force && ! $this->isDirty($field)) {
                continue;
            }

            if (in_array($field, $this->translatable ?? [], true)) {
                foreach ($this->getTranslations($field) as $locale => $value) {
                    if (is_string($value)) {
                        $this->setTranslation($field, $locale, Html::clean($value));
                    }
                }
            } elseif (is_string($this->getAttribute($field))) {
                $this->setAttribute($field, Html::clean($this->getAttribute($field)));
            }
        }
    }
}
