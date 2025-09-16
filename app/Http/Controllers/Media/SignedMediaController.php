<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SignedMediaController extends Controller
{
    public function media(Request $request, Media $media, string $conversion = ''): BinaryFileResponse
    {
        abort_unless($request->hasValidSignature($absolute = false), 403);

        // Your authentication logic here

        ob_get_clean();

        return response()->file($media->getPath($conversion), [
            'Content-Type' => $media->mime_type,
        ]);
    }
}
