<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Voucher;
use App\Models\VoucherPurchase;
use App\Services\VoucherEmailService;
use App\Support\ContentCache;
use App\Support\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

/**
 * Public gift-voucher endpoints: browse active voucher products and buy one.
 * Buying issues a unique voucher code and immediately emails the customer a
 * designed PDF voucher (see VoucherEmailService) — mirroring how
 * OrderController::store() creates an order and fires the confirmation email.
 */
class VoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $data = ContentCache::remember("api:vouchers:{$locale}", fn () => Voucher::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Voucher $voucher) => $this->formatVoucher($voucher, $locale))
            ->values()
            ->all());

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $voucher = Voucher::query()->active()->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $this->formatVoucher($voucher, $locale, withDetail: true),
        ]);
    }

    public function store(Request $request, VoucherEmailService $email): JsonResponse
    {
        abort_unless((bool) Setting::get('shop_enabled', true), 503, 'The shop is currently closed.');

        $validated = $request->validate([
            'voucher_id' => [
                'required', 'integer',
                Rule::exists('vouchers', 'id')->where('status', 'active'),
            ],
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'recipient_name' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        $voucher = Voucher::query()->active()->findOrFail($validated['voucher_id']);

        $purchase = DB::transaction(fn () => VoucherPurchase::create([
            'voucher_id' => $voucher->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'recipient_name' => $validated['recipient_name'] ?? null,
            'message' => $validated['message'] ?? null,
            // Snapshot the current price/value/currency onto the issued voucher.
            'price_paid' => $voucher->price,
            'value' => $voucher->value,
            'currency' => $voucher->currency,
        ]));

        // Sent immediately, best-effort — a mail failure must not fail the sale.
        $email->sendVoucher($purchase);

        return response()->json([
            'data' => $this->formatPurchase($purchase),
        ], 201);
    }

    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return is_string($locale) && Locale::isSupported($locale) ? $locale : Locale::default();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatVoucher(Voucher $voucher, string $locale, bool $withDetail = false): array
    {
        $data = [
            'id' => $voucher->id,
            'name' => $voucher->getTranslation('name', $locale, useFallbackLocale: true),
            'slug' => $voucher->slug,
            'price' => (float) $voucher->price,
            'value' => (float) $voucher->value,
            'currency' => $voucher->currency,
            'savings' => $voucher->savings(),
            'valid_days' => $voucher->valid_days,
        ];

        if ($withDetail) {
            $data['description'] = $voucher->getTranslation('description', $locale, useFallbackLocale: true);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPurchase(VoucherPurchase $purchase): array
    {
        return [
            'code' => $purchase->code,
            'voucher_name' => $purchase->voucherName(),
            'value' => (float) $purchase->value,
            'price_paid' => (float) $purchase->price_paid,
            'currency' => $purchase->currency,
            'recipient_name' => $purchase->recipient_name,
            'status' => $purchase->status,
            'expires_at' => $purchase->expires_at?->toIso8601String(),
            'expires_at_display' => $purchase->expires_at?->toDisplay(),
            'purchased_at' => $purchase->purchased_at?->toIso8601String(),
            'voucher_url' => URL::signedRoute('vouchers.public.show', ['voucher' => $purchase->code]),
        ];
    }
}
