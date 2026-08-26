<?php

use App\Models\Setting;

if (! function_exists('display_timezone')) {
    /**
     * Timezone used to display dates to users. Dates are always stored and
     * computed internally in UTC (config('app.timezone')) — this only controls
     * how they're rendered, and is set via the "timezone" setting in Settings.
     */
    function display_timezone(): string
    {
        return Setting::get('timezone', config('app.display_timezone', 'UTC'));
    }
}
