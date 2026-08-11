<?php

namespace App\Livewire\Admin\Testimonials;

use App\Models\Product;
use App\Models\Testimonial;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Form extends Component
{
    public ?int $testimonialId = null;

    #[Validate('nullable|string')]
    public ?string $image = null;

    public string $imagePickerId = '';

    #[Validate('required|string|max:255')]
    public string $name_en = '';

    #[Validate('nullable|string|max:255')]
    public string $name_bn = '';

    #[Validate('required|string')]
    public string $comment_en = '';

    #[Validate('nullable|string')]
    public string $comment_bn = '';

    #[Validate('nullable|string|max:255')]
    public string $location = '';

    #[Validate('nullable|string|max:100')]
    public string $type = '';

    #[Validate('nullable|integer|exists:products,id')]
    public ?int $product_id = null;

    #[Validate('in:active,inactive')]
    public string $status = 'active';

    #[Validate('nullable|integer|min:0')]
    public int $sort_order = 0;

    public function mount(?int $id = null): void
    {
        $this->imagePickerId = 'image-picker-' . Str::uuid()->toString();

        if ($id) {
            $testimonial = Testimonial::findOrFail($id);
            $this->testimonialId = $id;
            $this->image         = $testimonial->image ?? null;
            $this->name_en       = $testimonial->getTranslation('name', 'en', false) ?? '';
            $this->name_bn       = $testimonial->getTranslation('name', 'bn', false) ?? '';
            $this->comment_en    = $testimonial->getTranslation('comment', 'en', false) ?? '';
            $this->comment_bn    = $testimonial->getTranslation('comment', 'bn', false) ?? '';
            $this->location      = $testimonial->location ?? '';
            $this->type          = $testimonial->type ?? '';
            $this->product_id    = $testimonial->product_id;
            $this->status        = $testimonial->status;
            $this->sort_order    = $testimonial->sort_order;
        }
    }

    #[Computed]
    public function products()
    {
        return Product::orderBy('sort_order')->orderBy('id')->get();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'image'      => $this->image ?: null,
            'name'       => array_filter(['en' => $this->name_en, 'bn' => $this->name_bn]),
            'comment'    => array_filter(['en' => $this->comment_en, 'bn' => $this->comment_bn]),
            'location'   => $this->location ?: null,
            'type'       => $this->type ?: null,
            'product_id' => $this->product_id,
            'status'     => $this->status,
            'sort_order' => $this->sort_order,
        ];

        if ($this->testimonialId) {
            Testimonial::findOrFail($this->testimonialId)->update($data);
            $this->dispatch('notify', message: 'Testimonial updated successfully');
        } else {
            Testimonial::create($data);
            $this->dispatch('notify', message: 'Testimonial created successfully');
        }

        $this->redirect(route('admin.testimonials'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.testimonials.form')
            ->layout('layouts.admin', ['title' => $this->testimonialId ? 'Edit Testimonial' : 'New Testimonial']);
    }
}
