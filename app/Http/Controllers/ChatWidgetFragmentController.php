<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Livewire\Livewire;
use Livewire\Mechanisms\FrontendAssets\FrontendAssets;

/**
 * The chat bubble's contents, fetched on demand.
 *
 * The bubble is on every public page of every theme, and it is the only reason
 * both of these reached the browser on page load:
 *
 *   - Livewire's runtime, ~255 KB raw, and
 *   - Flux's, ~131 KB, pulled in by @fluxScripts purely for the widget's own
 *     flux:input / flux:textarea / flux:error.
 *
 * Both were paid for by every visitor on every page to serve a panel that
 * almost none of them ever open. The bubble is now a static button (see
 * resources/views/frontend/partials/_chat-widget.blade.php) and this endpoint
 * hands over the real component — and the two runtimes — the first time
 * somebody actually clicks it.
 *
 * The scripts come back as ready-made <script> tags rather than as URLs, so
 * this endpoint never has to know the route Livewire and Flux happen to serve
 * their bundles from, or which attributes (CSRF token, update URI, module URI)
 * their bundles need in order to work. It also keeps their order — Flux first,
 * then Livewire — which is the order the page used to emit them in, and the
 * one Flux expects since it registers itself with the Alpine that Livewire's
 * bundle carries.
 */
class ChatWidgetFragmentController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // The same switch the component's own render() honours, answered here
        // so a disabled widget costs the visitor an empty placeholder rather
        // than a button that fetches a fragment to learn it is switched off.
        if (! (bool) Setting::get('chat_widget_enabled', true)) {
            return response()->json(['enabled' => false]);
        }

        return response()->json([
            'enabled' => true,
            'html' => Livewire::mount('frontend.chat-widget'),
            'scripts' => [
                app('flux')->scripts(),
                FrontendAssets::js([]),
            ],
        ]);
    }
}
