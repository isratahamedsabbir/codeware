<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Console\Command;

class SanitizeRichText extends Command
{
    protected $signature = 'richtext:sanitize {--dry-run : Report rows that would change without saving}';

    protected $description = 'Purify stored product and post rich text (descriptions, excerpts, specifications)';

    public function handle(): int
    {
        $changed = 0;

        foreach ([Post::class, Product::class] as $class) {
            $class::withTrashed()->each(function ($model) use (&$changed) {
                $model->sanitizeRichText(force: true);

                if ($model->isDirty()) {
                    $changed++;
                    $this->line(class_basename($model).' #'.$model->getKey());

                    if (! $this->option('dry-run')) {
                        $model->saveQuietly();
                    }
                }
            });
        }

        $this->info("{$changed} row(s) ".($this->option('dry-run') ? 'would be ' : '').'sanitized.');

        return self::SUCCESS;
    }
}
