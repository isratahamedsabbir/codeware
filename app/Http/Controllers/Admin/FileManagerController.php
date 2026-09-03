<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FileManagerPath;
use FilesystemIterator;
use Illuminate\Http\Request;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use ZipArchive;

class FileManagerController extends Controller
{
    /**
     * Stream a file's raw bytes (images/video previews, or a forced download
     * when `?download=1` is passed). Uses Symfony's BinaryFileResponse so
     * Range requests work, which video playback needs for seeking.
     */
    public function raw(Request $request): BinaryFileResponse
    {
        $absolute = FileManagerPath::resolve((string) $request->query('path', ''));

        if (! is_file($absolute)) {
            throw new NotFoundHttpException('Not a file.');
        }

        return $request->boolean('download')
            ? response()->download($absolute, basename($absolute))
            : response()->file($absolute);
    }

    /**
     * Zip a set of selected entries on the fly and stream the download,
     * without leaving a copy behind in the folder (unlike the "Zip
     * Selected" action, which saves the archive there deliberately).
     */
    public function downloadZip(Request $request): BinaryFileResponse
    {
        $path = (string) $request->query('path', '');
        $names = (array) $request->query('names', []);

        $entries = [];

        foreach ($names as $name) {
            if (! is_string($name) || $name === '') {
                continue;
            }

            $relative = $path === '' ? $name : $path.'/'.$name;

            try {
                $entries[$name] = FileManagerPath::resolve($relative);
            } catch (NotFoundHttpException) {
                continue;
            }
        }

        if (empty($entries)) {
            throw new NotFoundHttpException('No valid items selected.');
        }

        $zipName = 'download-'.now()->format('YmdHis').'.zip';
        $tmpDir = storage_path('app/file-manager-tmp');

        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $zipPath = $tmpDir.DIRECTORY_SEPARATOR.$zipName;

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $name => $absolute) {
            if (is_dir($absolute)) {
                $this->addDirectoryToZip($zip, $absolute, $name);
            } else {
                $zip->addFile($absolute, $name);
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    private function addDirectoryToZip(ZipArchive $zip, string $absoluteDir, string $localPrefix): void
    {
        $zip->addEmptyDir($localPrefix);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absoluteDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $localPath = $localPrefix.'/'.ltrim(
                str_replace('\\', '/', substr($item->getPathname(), strlen($absoluteDir))),
                '/'
            );

            if ($item->isDir()) {
                $zip->addEmptyDir($localPath);
            } else {
                $zip->addFile($item->getPathname(), $localPath);
            }
        }
    }
}
