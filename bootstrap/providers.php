<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\PluginServiceProvider;
use App\Providers\ThemeServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    PluginServiceProvider::class,
    ThemeServiceProvider::class,
];
