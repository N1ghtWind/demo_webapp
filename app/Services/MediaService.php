<?php

namespace App\Services;

use App\Repositories\Interfaces\MediaRepositoryInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaService
{
    private MediaRepositoryInterface $mediaRepository;

    public function __construct(MediaRepositoryInterface $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    /**
     * Get image with options
     */
    public function getImage(string $path, array $options): StreamedResponse
    {
        return $this->mediaRepository->getImage($path, $options);
    }
}