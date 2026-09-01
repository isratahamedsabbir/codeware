@props(['text', 'limit' => 15])

<span @if ($text && mb_strlen($text) > $limit) title="{{ $text }}" @endif>{{ \Illuminate\Support\Str::limit($text, $limit) }}</span>
