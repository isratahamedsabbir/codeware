<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The ecommerce homepage hero slider (Theme Settings → Banners). Each slide is
 * {image, title, description, link}, stored as a JSON list in the
 * `home_hero_slides` setting. Older data — a list of plain image URLs, or just
 * the single `home_hero_image` — is read as image-only slides.
 */
class HeroSlides
{
    public const MAX = 6;

    /**
     * An empty slide with every field present.
     *
     * @return array{image: string, title: string, description: string, link: string}
     */
    public static function blank(): array
    {
        return ['image' => '', 'title' => '', 'description' => '', 'link' => ''];
    }

    /**
     * Coerces one stored slide (a legacy URL string or a partial array) into
     * the full shape.
     *
     * @return array{image: string, title: string, description: string, link: string}
     */
    public static function normalize(mixed $slide): array
    {
        if (is_string($slide)) {
            return ['image' => trim($slide)] + self::blank();
        }

        $slide = is_array($slide) ? $slide : [];

        return array_map(
            fn ($value) => trim((string) $value),
            array_intersect_key($slide + self::blank(), self::blank()),
        );
    }

    /**
     * Every stored slide, normalized — including ones without an image yet.
     *
     * @return array<int, array{image: string, title: string, description: string, link: string}>
     */
    public static function stored(): array
    {
        $slides = json_decode((string) Setting::get('home_hero_slides', ''), true);

        if (! is_array($slides)) {
            $legacy = trim((string) Setting::get('home_hero_image', ''));

            return $legacy !== '' ? [self::normalize($legacy)] : [];
        }

        return array_values(array_map(self::normalize(...), $slides));
    }

    /**
     * The slides the storefront renders: only those with an image, each with a
     * safe `url` (a site path or http(s) address; anything else → the shop).
     *
     * @return array<int, array{image: string, title: string, description: string, link: string, url: string}>
     */
    public static function forStorefront(): array
    {
        return array_values(array_map(
            fn (array $slide) => $slide + ['url' => self::safeUrl($slide['link'])],
            array_filter(self::stored(), fn (array $slide) => $slide['image'] !== ''),
        ));
    }

    public static function safeUrl(?string $link): string
    {
        $link = trim((string) $link);

        return preg_match('#^(/(?!/)|https?://)#i', $link) ? $link : route('shop');
    }
}
