<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class PublicFileController extends Controller
{
    public function show(string $path)
    {
        // Hardening: cegah traversal
        $path = ltrim($path, '/');
        if (str_contains($path, '..')) {
            abort(403);
        }

        // Pastikan file ada di disk 'public'
        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $full = Storage::disk('public')->path($path);
        $mime = @mime_content_type($full) ?: 'application/octet-stream';

        // Tampilkan inline
        return response()->file($full, [
            'Content-Type'  => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
