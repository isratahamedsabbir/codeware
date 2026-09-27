<?php

namespace App\Support;

use App\Models\SocialLink;
use Illuminate\Support\Collection;

/**
 * The portfolio theme's social links, as a list both the shared header/footer
 * partials and the contact section read.
 *
 * This exists as a class rather than as a Blade partial that assigns $socials,
 * because a variable assigned inside an @include stays in the included view -
 * Blade hands the parent scope down, it does not lift assignments back up. The
 * partial version compiled cleanly and still left $socials undefined in the
 * views that included it, which is a 500 on every page using the header.
 *
 * The list also used to be built inline in home.blade.php and again in
 * errors/404.blade.php, and the copy in one drifted from the other. One method
 * is the fix for both problems: four views can call this without any of them
 * owning the platform order.
 */
class PortfolioSocials
{
    /**
     * The platforms this theme has a brand mark for, in display order.
     *
     * A platform the admin panel supports but this theme has no mark for is
     * still rendered - the icon partial falls back to the platform's initial -
     * so an admin adding one is not met with a silently dropped link. The order
     * of this array is the order they appear in, which is why the built list is
     * not re-sorted afterwards.
     */
    public const PLATFORMS = [
        'facebook' => 'Facebook',
        'twitter' => 'X',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'tiktok' => 'TikTok',
        'github' => 'GitHub',
        'gitlab' => 'GitLab',
        'behance' => 'Behance',
        'dribbble' => 'Dribbble',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
    ];

    /**
     * Platforms that have a URL, as ['platform', 'label', 'url'] rows ready for
     * the social-links partial.
     *
     * @return Collection<int, array{platform: string, label: string, url: string}>
     */
    public static function all(): Collection
    {
        return collect(array_keys(self::PLATFORMS))
            ->map(fn (string $platform) => [
                'platform' => $platform,
                'label' => self::PLATFORMS[$platform],
                'url' => SocialLink::url($platform),
            ])
            // SocialLink::url() is the only gate: it returns null for a platform
            // with no URL saved, and a link with an empty href is worse than no
            // link - it looks like it goes somewhere.
            ->filter(fn (array $social) => filled($social['url']))
            ->values();
    }
}
