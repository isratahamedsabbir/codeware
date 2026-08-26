<?php

namespace App\Livewire\Admin\Coupons;

use App\Models\Coupon;
use App\Support\AdminActivity;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $couponId = null;

    #[Validate('required|string|max:50')]
    public string $code = '';

    #[Validate('required|in:percentage,fixed')]
    public string $type = 'percentage';

    #[Validate('required|numeric|min:0')]
    public string $value = '';

    #[Validate('nullable|numeric|min:0')]
    public string $min_order_amount = '';

    #[Validate('nullable|integer|min:1')]
    public string $max_uses = '';

    #[Validate('nullable|date')]
    public string $expires_at = '';

    public function mount(?int $id = null): void
    {
        if ($id) {
            $coupon = Coupon::findOrFail($id);
            $this->couponId = $id;
            $this->code = $coupon->code;
            $this->type = $coupon->type;
            $this->value = (string) $coupon->value;
            $this->min_order_amount = $coupon->min_order_amount !== null ? (string) $coupon->min_order_amount : '';
            $this->max_uses = $coupon->max_uses !== null ? (string) $coupon->max_uses : '';
            $this->expires_at = $coupon->expires_at?->format('Y-m-d') ?? '';
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['code'] = $this->couponId
            ? 'required|string|max:50|unique:coupons,code,'.$this->couponId
            : 'required|string|max:50|unique:coupons,code';

        if ($this->type === 'percentage') {
            $rules['value'] = 'required|numeric|min:0|max:100';
        }

        $this->validate($rules);

        $data = [
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'value' => $this->value,
            'min_order_amount' => $this->min_order_amount !== '' ? $this->min_order_amount : null,
            'max_uses' => $this->max_uses !== '' ? $this->max_uses : null,
            'expires_at' => $this->expires_at !== '' ? $this->expires_at : null,
        ];

        $creating = $this->couponId === null;

        if ($this->couponId) {
            Coupon::findOrFail($this->couponId)->update($data);
            $this->dispatch('notify', message: 'Coupon updated successfully');
        } else {
            // New coupons stay inactive until switched on from the list — status is
            // no longer editable from this form, see Index::toggleStatus().
            $data['status'] = 'inactive';
            Coupon::create($data);
            $this->dispatch('notify', message: 'Coupon created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Coupon: {$data['code']}",
        );

        $this->redirect(route('admin.coupons'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.coupons.form')
            ->layout('layouts.admin', ['title' => $this->couponId ? 'Edit Coupon' : 'New Coupon']);
    }
}
