<?php

namespace App\Livewire\Vendor;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\UserDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use PasswordValidationRules, ProfileValidationRules, WithFileUploads;

    public string $name = '';

    public string $email = '';

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    /**
     * Either the existing stored signature path (unchanged), a fresh
     * "data:image/png;base64,..." string just drawn/uploaded on the
     * signature pad, or null (no signature / explicitly cleared) — same
     * shape as Admin\Users\Form's $signature.
     */
    public ?string $signature = null;

    /** Freshly-chosen document uploads, pending until uploadDocuments() persists them. */
    public array $newDocuments = [];

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->signature = $user->signature;
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
    }

    public function updateProfile(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'name' => $this->nameRules(),
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = ['name' => $validated['name']];

        if (! empty($this->photo)) {
            $path = $this->photo->storeAs(
                'profiles',
                Str::uuid()->toString().'.'.$this->photo->getClientOriginalExtension(),
                'public',
            );

            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            $data['photo'] = $path;
        }

        $data['signature'] = $this->persistSignature($user);

        $user->update($data);

        $this->reset('photo');
        $this->signature = $user->fresh()->signature;

        $this->dispatch('notify', message: 'Profile updated successfully');
        $this->dispatch('profile-updated', name: $user->fresh()->name);
    }

    public function removePhoto(): void
    {
        $user = auth()->user();

        if ($user->photo && Storage::disk('public')->exists($user->photo)) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->update(['photo' => null]);

        $this->dispatch('notify', message: 'Photo removed');
    }

    /**
     * Resolves $this->signature into the path that should be stored: decodes
     * and saves a freshly-drawn/uploaded "data:image/..." string (deleting
     * the old file first), deletes the old file and returns null when
     * cleared, or passes an already-stored path through untouched — same
     * logic as Admin\Users\Form::persistSignature().
     */
    private function persistSignature($user): ?string
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

    public function uploadDocuments(): void
    {
        $this->validate([
            'newDocuments.*' => 'file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,webp',
        ]);

        foreach ($this->newDocuments as $file) {
            $path = $file->storeAs('user-documents', Str::uuid().'.'.$file->getClientOriginalExtension(), 'public');

            UserDocument::create([
                'user_id' => auth()->id(),
                'name' => $file->getClientOriginalName(),
                'file' => $path,
            ]);
        }

        $this->newDocuments = [];
        $this->dispatch('notify', message: 'Document(s) uploaded successfully');
    }

    public function deleteDocument(int $documentId): void
    {
        $document = UserDocument::where('user_id', auth()->id())->findOrFail($documentId);

        if (Storage::disk('public')->exists($document->file)) {
            Storage::disk('public')->delete($document->file);
        }

        $document->delete();

        $this->dispatch('notify', message: 'Document deleted');
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        auth()->user()->update(['password' => $validated['password']]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('notify', message: 'Password updated successfully');
    }

    public function render()
    {
        return view('livewire.vendor.profile', [
            'user' => auth()->user(),
            'documents' => UserDocument::where('user_id', auth()->id())->latest()->get(),
        ])->layout('layouts.vendor', ['title' => 'My Profile']);
    }
}
