<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class SignedMediaUrlGenerator extends DefaultUrlGenerator
{
    public function getUrl($conversion = null, $mediaId = null): string
    {
        $mediaId = $mediaId ?? $this->media->id;
        $token = null;

        if ($this->conversion) {
            $conversion = $this->conversion->getName();
        }

        if (auth()->check()) {
            $accessToken = auth()->user()->currentAccessToken();
            if ($accessToken) {
                $token = $accessToken->token;
            }
        }

        return URL::signedRoute('media', [
            'media' => $mediaId,
            'token' => $token,
            'conversion' => $conversion,
        ], null, false);
    }
}
