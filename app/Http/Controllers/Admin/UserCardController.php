<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class UserCardController extends Controller
{
    public function show(User $user): View
    {
        return $this->render($user, route('admin.users.card.download', $user));
    }

    public function download(User $user): Response
    {
        return $this->buildPdf($user)->download('user-card-'.$user->id.'.pdf');
    }

    private function render(User $user, string $downloadUrl): View
    {
        return view('admin.users.card', [
            'user' => $user,
            'tier' => $this->tierLabel($user),
            'photo' => $this->imageDataUri($user->photo),
            'signature' => $this->imageDataUri($user->signature),
            'qrCode' => $this->qrCode($user),
            'downloadUrl' => $downloadUrl,
            'forPdf' => false,
        ]);
    }

    private function buildPdf(User $user): PdfDocument
    {
        return Pdf::loadView('admin.users.card', [
            'user' => $user,
            'tier' => $this->tierLabel($user),
            'photo' => $this->imageDataUri($user->photo),
            'signature' => $this->imageDataUri($user->signature),
            'qrCode' => $this->qrCode($user),
            'downloadUrl' => null,
            'forPdf' => true,
        ])->setPaper([0, 0, 240.94, 155.91]); // 85mm x 55mm in points, landscape
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
     * Scanning it opens this user's edit page in the admin panel — whoever
     * scans it still needs an authenticated admin session, same as opening
     * that page any other way.
     */
    private function qrCode(User $user): string
    {
        $url = route('admin.users.edit', $user);

        $result = (new Builder(writer: new PngWriter))->build(
            data: $url,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 160,
            margin: 4,
        );

        return $result->getDataUri();
    }

    /**
     * Embedded as a data URI rather than an <img src="..."> URL so it renders
     * for dompdf, which has remote/local image fetching disabled by default —
     * same convention as InvoiceController's logo handling. Downscaled first —
     * the card only ever displays these at a few dozen points, but an
     * uploaded photo can be several megapixels; embedding it at full
     * resolution bloats the PDF and was tripping dompdf's pagination.
     */
    private function imageDataUri(?string $path, int $maxDimension = 240): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);
        $source = @imagecreatefromstring(Storage::disk('public')->get($path));

        if (! $source) {
            $mime = mime_content_type($fullPath) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path));
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, $maxDimension / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        ob_start();
        imagepng($resized, null, 6);
        $data = ob_get_clean();
        imagedestroy($resized);

        return 'data:image/png;base64,'.base64_encode($data);
    }
}
