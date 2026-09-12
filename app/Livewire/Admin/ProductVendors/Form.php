<?php

namespace App\Livewire\Admin\ProductVendors;

use App\Models\ProductVendor;
use App\Models\VendorDocument;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?int $vendorId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public string $logo = '';

    #[Validate('nullable|string')]
    public string $address = '';

    /**
     * Either the existing stored signature path (unchanged), a fresh
     * "data:image/png;base64,..." string just drawn on the signature pad, or
     * null (no signature / explicitly cleared).
     */
    public ?string $signature = null;

    /** Freshly-chosen document uploads, pending until uploadDocuments() persists them. */
    public array $newDocuments = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $vendor = ProductVendor::findOrFail($id);
            $this->vendorId = $id;
            $this->name = $vendor->name;
            $this->logo = $vendor->logo ?? '';
            $this->address = $vendor->address ?? '';
            $this->signature = $vendor->signature;
        }
    }

    public function save(): void
    {
        $rules = $this->getRules();
        $rules['name'] = $this->vendorId
            ? 'required|string|max:255|unique:product_vendors,name,'.$this->vendorId
            : 'required|string|max:255|unique:product_vendors,name';

        $this->validate($rules);

        $existingSignature = $this->vendorId ? ProductVendor::find($this->vendorId)?->signature : null;

        $data = [
            'name' => $this->name,
            'logo' => $this->logo ?: null,
            'signature' => $this->persistSignature($existingSignature),
            'address' => $this->address ?: null,
        ];

        $creating = $this->vendorId === null;

        if ($this->vendorId) {
            // Status is toggled from the index list (see Index::toggleStatus()),
            // not this form — omit it here so saving never resets an already
            // deactivated vendor back to active.
            $vendor = ProductVendor::findOrFail($this->vendorId);
            $vendor->update($data);
            $this->dispatch('notify', message: 'Vendor updated successfully');
        } else {
            $data['status'] = 'active';
            $vendor = ProductVendor::create($data);
            $this->vendorId = $vendor->id;
            $this->dispatch('notify', message: 'Vendor created successfully');
        }

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "Product Vendor: {$this->name}",
        );

        $this->redirect(route('admin.product-vendors'), navigate: true);
    }

    /**
     * Uploads any pending documents against an already-saved vendor — new
     * vendors have no id to attach them to, so the Documents section only
     * appears once editing an existing one (see the Blade view).
     */
    public function uploadDocuments(): void
    {
        $this->validate([
            'newDocuments.*' => 'file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,webp',
        ]);

        foreach ($this->newDocuments as $file) {
            $path = $file->storeAs('vendor-documents', Str::uuid().'.'.$file->getClientOriginalExtension(), 'public');

            VendorDocument::create([
                'vendor_id' => $this->vendorId,
                'name' => $file->getClientOriginalName(),
                'file' => $path,
            ]);
        }

        $this->newDocuments = [];
        $this->dispatch('notify', message: 'Document(s) uploaded successfully');
    }

    public function deleteDocument(int $documentId): void
    {
        $document = VendorDocument::where('vendor_id', $this->vendorId)->findOrFail($documentId);

        if (Storage::disk('public')->exists($document->file)) {
            Storage::disk('public')->delete($document->file);
        }

        $document->delete();

        $this->dispatch('notify', message: 'Document deleted');
    }

    /**
     * Resolves $this->signature into the path that should be stored on the
     * vendor: decodes and saves a freshly-drawn "data:image/..." string
     * (deleting the old file first), deletes the old file and returns null
     * when cleared, or passes an already-stored path through untouched.
     */
    private function persistSignature(?string $existing): ?string
    {
        if ($this->signature === $existing) {
            return $this->signature;
        }

        if ($existing && Storage::disk('public')->exists($existing)) {
            Storage::disk('public')->delete($existing);
        }

        if ($this->signature === null) {
            return null;
        }

        [, $encoded] = explode(',', $this->signature, 2);
        $path = 'signatures/'.Str::uuid().'.png';
        Storage::disk('public')->put($path, base64_decode($encoded));

        return $path;
    }

    public function render()
    {
        return view('livewire.admin.product-vendors.form', [
            'documents' => $this->vendorId
                ? VendorDocument::where('vendor_id', $this->vendorId)->latest()->get()
                : collect(),
        ])->layout('layouts.admin', ['title' => $this->vendorId ? 'Edit Vendor' : 'New Vendor']);
    }
}
