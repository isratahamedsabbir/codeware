<div class="max-w-[1600px] space-y-6">
    <flux:text class="text-zinc-500">
        Enter your payment gateway credentials. Credentials are stored privately and never exposed via the public API.
    </flux:text>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

    @if (isset($gateways['paypal']))
        {{-- PayPal --}}
        <x-admin-section-card icon="credit-card" title="PayPal">
            <x-slot:actions>
                <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                    <input type="checkbox" wire:model="gateways.paypal.is_enabled" class="rounded border-zinc-300 text-primary" />
                    Enable
                </label>
            </x-slot:actions>

            <flux:field>
                <flux:label>Mode</flux:label>
                <select wire:model="gateways.paypal.mode"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                    <option value="sandbox">Sandbox</option>
                    <option value="live">Live</option>
                </select>
            </flux:field>
            <flux:field>
                <flux:label>Client ID</flux:label>
                <flux:input wire:model="gateways.paypal.credentials.client_id" />
            </flux:field>
            <flux:field>
                <flux:label>Client Secret</flux:label>
                <flux:input type="password" wire:model="gateways.paypal.credentials.client_secret" />
            </flux:field>
        </x-admin-section-card>
    @endif

    @if (isset($gateways['stripe']))
        {{-- Stripe --}}
        <x-admin-section-card icon="credit-card" title="Stripe" icon-color="bg-indigo-500/10 text-indigo-600">
            <x-slot:actions>
                <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                    <input type="checkbox" wire:model="gateways.stripe.is_enabled" class="rounded border-zinc-300 text-primary" />
                    Enable
                </label>
            </x-slot:actions>

            <flux:field>
                <flux:label>Mode</flux:label>
                <select wire:model="gateways.stripe.mode"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                    <option value="test">Test</option>
                    <option value="live">Live</option>
                </select>
            </flux:field>
            <flux:field>
                <flux:label>Publishable Key</flux:label>
                <flux:input wire:model="gateways.stripe.credentials.publishable_key" />
            </flux:field>
            <flux:field>
                <flux:label>Secret Key</flux:label>
                <flux:input type="password" wire:model="gateways.stripe.credentials.secret_key" />
            </flux:field>
            <flux:field>
                <flux:label>Webhook Secret</flux:label>
                <flux:input type="password" wire:model="gateways.stripe.credentials.webhook_secret" />
            </flux:field>
        </x-admin-section-card>
    @endif

    @if (isset($gateways['bkash']))
        {{-- bKash --}}
        <x-admin-section-card icon="credit-card" title="bKash" icon-color="bg-rose-500/10 text-rose-600">
            <x-slot:actions>
                <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                    <input type="checkbox" wire:model="gateways.bkash.is_enabled" class="rounded border-zinc-300 text-primary" />
                    Enable
                </label>
            </x-slot:actions>

            <flux:field>
                <flux:label>Mode</flux:label>
                <select wire:model="gateways.bkash.mode"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                    <option value="sandbox">Sandbox</option>
                    <option value="live">Live</option>
                </select>
            </flux:field>
            <flux:field>
                <flux:label>Username</flux:label>
                <flux:input wire:model="gateways.bkash.credentials.username" />
            </flux:field>
            <flux:field>
                <flux:label>Password</flux:label>
                <flux:input type="password" wire:model="gateways.bkash.credentials.password" />
            </flux:field>
            <flux:field>
                <flux:label>App Key</flux:label>
                <flux:input wire:model="gateways.bkash.credentials.app_key" />
            </flux:field>
            <flux:field>
                <flux:label>App Secret</flux:label>
                <flux:input type="password" wire:model="gateways.bkash.credentials.app_secret" />
            </flux:field>
        </x-admin-section-card>
    @endif

    @if (isset($gateways['sslcommerz']))
        {{-- SSLCommerz --}}
        <x-admin-section-card icon="credit-card" title="SSLCommerz" icon-color="bg-amber-500/10 text-amber-600">
            <x-slot:actions>
                <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                    <input type="checkbox" wire:model="gateways.sslcommerz.is_enabled" class="rounded border-zinc-300 text-primary" />
                    Enable
                </label>
            </x-slot:actions>

            <flux:field>
                <flux:label>Mode</flux:label>
                <select wire:model="gateways.sslcommerz.mode"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                    <option value="sandbox">Sandbox</option>
                    <option value="live">Live</option>
                </select>
            </flux:field>
            <flux:field>
                <flux:label>Store ID</flux:label>
                <flux:input wire:model="gateways.sslcommerz.credentials.store_id" />
            </flux:field>
            <flux:field>
                <flux:label>Store Password</flux:label>
                <flux:input type="password" wire:model="gateways.sslcommerz.credentials.store_password" />
            </flux:field>
        </x-admin-section-card>
    @endif

    @if (isset($gateways['applepay']))
        {{-- Apple Pay --}}
        <x-admin-section-card icon="credit-card" title="Apple Pay" icon-color="bg-zinc-500/10 text-zinc-600">
            <x-slot:actions>
                <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                    <input type="checkbox" wire:model="gateways.applepay.is_enabled" class="rounded border-zinc-300 text-primary" />
                    Enable
                </label>
            </x-slot:actions>

            <flux:field>
                <flux:label>Merchant ID</flux:label>
                <flux:input wire:model="gateways.applepay.credentials.merchant_id" placeholder="merchant.com.example" />
            </flux:field>
            <flux:field>
                <flux:label>Merchant Name</flux:label>
                <flux:input wire:model="gateways.applepay.credentials.merchant_name" />
            </flux:field>
            <flux:field>
                <flux:label>Domain</flux:label>
                <flux:input wire:model="gateways.applepay.credentials.domain" placeholder="example.com" />
            </flux:field>
        </x-admin-section-card>
    @endif

    </div>

    <div>
        <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled">
            Save Settings
        </flux:button>
    </div>
</div>
