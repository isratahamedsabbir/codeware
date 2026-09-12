<?php

namespace App\Livewire\Admin\Users;

use App\Models\ProductVendor;
use App\Models\User;
use App\Models\UserDocument;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    use WithFileUploads;

    public ?int $userId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|min:8')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $isAdmin = false;

    public array $selectedRoles = [];

    /**
     * Vendor ids this user can log into the Vendor Portal for — a user can be
     * assigned to more than one vendor, see User::vendors().
     *
     * @var array<int, int>
     */
    public array $vendor_ids = [];

    /** A freshly-chosen upload, previewed via ->temporaryUrl() until saved. */
    public $photo = null;

    /** The currently stored photo path (editing only), for preview/removal/deletion. */
    public ?string $existingPhotoPath = null;

    public bool $removePhoto = false;

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
            $user = User::findOrFail($id);
            $this->userId = $id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->isAdmin = (bool) $user->is_admin;
            $this->selectedRoles = $user->roles->pluck('name')->toArray();
            $this->vendor_ids = $user->vendors->pluck('id')->all();
            $this->signature = $user->signature;
            $this->existingPhotoPath = $user->photo;
        }
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
        $this->removePhoto = false;
    }

    /**
     * Uploads any pending documents against an already-saved user — a new user
     * has no id to attach them to, so the Documents section only appears once
     * editing an existing one (see the Blade view).
     */
    public function uploadDocuments(): void
    {
        $this->validate([
            'newDocuments.*' => 'file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,webp',
        ]);

        foreach ($this->newDocuments as $file) {
            $path = $file->storeAs('user-documents', Str::uuid().'.'.$file->getClientOriginalExtension(), 'public');

            UserDocument::create([
                'user_id' => $this->userId,
                'name' => $file->getClientOriginalName(),
                'file' => $path,
            ]);
        }

        $this->newDocuments = [];
        $this->dispatch('notify', message: 'Document(s) uploaded successfully');
    }

    public function deleteDocument(int $documentId): void
    {
        $document = UserDocument::where('user_id', $this->userId)->findOrFail($documentId);

        if (Storage::disk('public')->exists($document->file)) {
            Storage::disk('public')->delete($document->file);
        }

        $document->delete();

        $this->dispatch('notify', message: 'Document deleted');
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => ['nullable', 'string', 'min:8'],
            'isAdmin' => ['boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'vendor_ids' => ['array'],
            'vendor_ids.*' => ['integer', 'exists:product_vendors,id'],
        ]);

        $user = $this->userId
            ? User::findOrFail($this->userId)
            : new User;

        $creating = ! $this->userId;

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->is_admin = $this->isAdmin;
        $user->signature = $this->persistSignature($user);
        $user->photo = $this->persistPhoto();

        if ($this->password) {
            $user->password = $this->password;
        }

        $user->save();

        AdminActivity::log(
            $creating ? 'created' : 'updated',
            "User: {$user->email}",
        );

        $user->syncRoles($this->selectedRoles);

        // Only a user with the 'vendor' role may hold vendor assignments — sync
        // an empty set instead of $this->vendor_ids when the role isn't selected,
        // so removing the role also revokes any access-vendor-portal already
        // granted through a prior assignment (see the gate in AppServiceProvider).
        $vendorIds = in_array('vendor', $this->selectedRoles, true) ? $this->vendor_ids : [];
        $user->vendors()->sync($vendorIds);

        $this->dispatch('notify', message: $this->userId ? 'User updated successfully' : 'User created successfully');

        $this->redirect(route('admin.users'), navigate: true);
    }

    /**
     * Resolves $this->signature into the path that should be stored on the
     * user: decodes and saves a freshly-drawn "data:image/..." string (deleting
     * the old file first), deletes the old file and returns null when cleared,
     * or passes an already-stored path through untouched.
     */
    private function persistSignature(User $user): ?string
    {
        if ($this->signature === $user->signature) {
            return $this->signature;
        }

        if ($user->signature && Storage::disk('public')->exists($user->signature)) {
            Storage::disk('public')->delete($user->signature);
        }

        if ($this->signature === null) {
            return null;
        }

        [, $encoded] = explode(',', $this->signature, 2);
        $path = 'signatures/'.Str::uuid().'.png';
        Storage::disk('public')->put($path, base64_decode($encoded));

        return $path;
    }

    /**
     * Resolves the profile photo to store: a freshly-chosen upload replaces
     * (and deletes) any existing one, an explicit removal deletes it and
     * returns null, otherwise the existing path passes through untouched.
     */
    private function persistPhoto(): ?string
    {
        if ($this->photo) {
            if ($this->existingPhotoPath && Storage::disk('public')->exists($this->existingPhotoPath)) {
                Storage::disk('public')->delete($this->existingPhotoPath);
            }

            return $this->photo->storeAs(
                'profiles',
                Str::uuid()->toString().'.'.$this->photo->getClientOriginalExtension(),
                'public',
            );
        }

        if ($this->removePhoto) {
            if ($this->existingPhotoPath && Storage::disk('public')->exists($this->existingPhotoPath)) {
                Storage::disk('public')->delete($this->existingPhotoPath);
            }

            return null;
        }

        return $this->existingPhotoPath;
    }

    public function render()
    {
        return view('livewire.admin.users.form', [
            'roles' => Role::withCount('permissions')->orderBy('name')->get(),
            'vendors' => ProductVendor::orderBy('name')->get(['id', 'name']),
            'documents' => $this->userId
                ? UserDocument::where('user_id', $this->userId)->latest()->get()
                : collect(),
        ])->layout('layouts.admin', ['title' => $this->userId ? 'Edit User' : 'New User']);
    }
}
