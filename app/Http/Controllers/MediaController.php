<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\MediaService;

class MediaController
{
    private MediaService $mediaService;
    
    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function publicImage(Request $request, string $path): StreamedResponse
    {
        return $this->mediaService->getImage($path, $request->except('s'));
    }

    public function image(Request $request, string $path): StreamedResponse
    {
        return $this->mediaService->getImage($path, $request->input());
    }
}
