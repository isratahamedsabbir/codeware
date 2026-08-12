<?php

namespace App\Services;

use Illuminate\Support\Arr;

class EmailTemplateRenderer
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function renderSubject(string $template, array $variables): string
    {
        return $this->render($template, $variables, false);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function renderBody(string $template, array $variables): string
    {
        return nl2br($this->render($template, $variables, true));
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function render(string $template, array $variables, bool $escapeOutput): string
    {
        return (string) preg_replace_callback(
            '/{{\s*([a-zA-Z0-9_.-]+)\s*}}/',
            function (array $matches) use ($variables, $escapeOutput): string {
                $value = Arr::get($variables, $matches[1], '');

                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                $stringValue = (string) $value;

                return $escapeOutput ? e($stringValue) : $stringValue;
            },
            $template
        );
    }
}
