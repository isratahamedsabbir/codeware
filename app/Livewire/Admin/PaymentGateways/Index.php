<?php

namespace App\Livewire\Admin\PaymentGateways;

use App\Models\PaymentGateway;
use App\Support\AdminActivity;
use Livewire\Component;

class Index extends Component
{
    /** @var array<string, array{id: int, name: string, is_enabled: bool, mode: ?string, credentials: array<string, string>}> */
    public array $gateways = [];

    public function mount(): void
    {
        $this->gateways = PaymentGateway::orderBy('sort_order')->get()
            ->mapWithKeys(fn (PaymentGateway $gateway) => [
                $gateway->code => [
                    'id' => $gateway->id,
                    'name' => $gateway->name,
                    'is_enabled' => $gateway->is_enabled,
                    'mode' => $gateway->mode,
                    'credentials' => $gateway->credentials ?? [],
                ],
            ])
            ->all();
    }

    public function save(): void
    {
        foreach ($this->gateways as $code => $gateway) {
            PaymentGateway::where('code', $code)->update([
                'is_enabled' => (bool) $gateway['is_enabled'],
                'mode' => $gateway['mode'],
                'credentials' => $gateway['credentials'],
            ]);
        }

        AdminActivity::log('updated', 'Payment gateway settings updated');

        $this->dispatch('notify', message: 'Payment gateway settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.payment-gateways.index')->layout('layouts.admin', ['title' => 'Payment Gateways']);
    }
}
