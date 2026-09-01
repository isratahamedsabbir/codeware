<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class UserCardController extends Controller
{
    public function download(User $user): Response
    {
        $pdf = Pdf::loadView('admin.users.card', [
            'user' => $user,
            'tier' => $this->tierLabel($user),
            'photo' => $this->imageDataUri($user->photo),
            'signature' => $this->imageDataUri($user->signature),
        ])->setPaper([0, 0, 240.94, 155.91]); // 85mm x 55mm in points, landscape

        return $pdf->download('user-card-'.$user->id.'.pdf');
    }

    private function tierLabel(User $user): string
    {
        if ($user->is_admin) {
            return 'Super Admin';
        }

        $role = $user->roles->pluck('name')->first();

        return $role ? ucfirst($role) : 'User';
    }

    /**
     * Embedded as a data URI rather than an <img src="..."> URL so it renders
     * for dompdf, which has remote/local image fetching disabled by default —
     * same convention as InvoiceController's logo handling.
     */
    private function imageDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);
        $mime = mime_content_type($fullPath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
    }
}
