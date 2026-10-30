<?php

namespace Themes\Ecommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersCareers;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "ecommerce" theme's Careers pages: the open vacancies and a single job
 * with its application form.
 */
class CareerController extends ThemeController
{
    use RendersCareers;
}
