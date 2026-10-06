<?php

use App\Models\Post;
use App\Models\Product;
use App\Support\Html;

it('strips scripts, event handlers and javascript urls', function () {
    $out = Html::clean('<p onclick="x()">Hi<script>alert(1)</script><a href="javascript:alert(1)">a</a><img src="x" onerror="alert(1)"><iframe src="//e"></iframe></p>');

    expect($out)->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->not->toContain('javascript:')
        ->not->toContain('iframe')
        ->toContain('Hi');
});

it('keeps allowlisted markup and safe links', function () {
    $out = Html::clean('<h2>T</h2><a href="https://x.com">l</a><a href="mailto:a@b.co">m</a><ul><li>i</li></ul>');

    expect($out)->toContain('<h2>T</h2>')->toContain('href="https://x.com"')->toContain('mailto:a@b.co')->toContain('<li>i</li>');
});

it('sanitizes translatable rich text when a product is saved', function () {
    $product = Product::factory()->create([
        'description' => ['en' => '<p>ok</p><script>alert(1)</script>', 'bn' => '<img src=x onerror=alert(1)>'],
    ]);

    $stored = json_encode($product->fresh()->getTranslations('description'));

    expect($stored)->toContain('ok')->not->toContain('script')->not->toContain('onerror');
});

it('sanitizes post descriptions on save', function () {
    $post = Post::factory()->create(['description' => ['en' => '<b onmouseover=1>x</b><script>1</script>']]);

    expect(json_encode($post->fresh()->getTranslations('description')))->not->toContain('script')->not->toContain('onmouseover');
});
