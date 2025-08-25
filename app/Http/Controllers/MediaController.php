<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Repositories\Interfaces\MediaRepositoryInterface;

class MediaController
{
    private MediaRepositoryInterface $mediaRepository;
    public function __construct(MediaRepositoryInterface $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public function publicImage(Request $request, string $path): StreamedResponse
    {
        return $this->mediaRepository->getImage($path, $request->except('s'));
    }

    public function image(Request $request, string $path): StreamedResponse
    {
        return $this->mediaRepository->getImage($path, $request->input());
    }
}
