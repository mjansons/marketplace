<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductImageHandler
{
    private string $uploadDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $uploadDirectory, SluggerInterface $slugger)
    {
        $this->uploadDirectory = $uploadDirectory;
        $this->slugger = $slugger;
    }

    public function processUploads(array $files, array $existingImages = []): array
    {
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                $file->move($this->uploadDirectory, $newFilename);
                $existingImages[] = $newFilename;
            }
        }
        return $existingImages;
    }

    public function processRemovals(array $removedFilenames, array $existingImages): array
    {
        foreach ($removedFilenames as $removedFilename) {
            $filePath = $this->uploadDirectory . '/' . $removedFilename;
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            if (($key = array_search($removedFilename, $existingImages)) !== false) {
                unset($existingImages[$key]);
            }
        }
        return array_values($existingImages);
    }
}
