<?php

namespace App\Traits\Api\V1;

trait GetMedia
{
    public function getOptimizedImagePaths($mediaCollectionName = 'images')
    {
        $images = [];
        if ($this->getMedia($mediaCollectionName)) {
            foreach ($this->getMedia($mediaCollectionName)->sortByDesc('created_at') as $media) {
                $images[] = $media->getUrl('optimized');
            }
        }

        return $images;
    }

    public function getOptimizedImagePath($mediaCollectionName = 'images')
    {
        $image = null;

        if ($image = $this->getMedia($mediaCollectionName)->sortByDesc('created_at')->first()) {
            // $image = $image->getUrl('optimized');
            $image = url($image->getUrl('optimized'));
        }

        return $image;
    }

    public function getThumbnailImagePath($mediaCollectionName = 'images')
    {
        $image = null;

        if ($image = $this->getMedia($mediaCollectionName)->first()) {
            if ($image->hasGeneratedConversion('thumb')) {
                $image = $image->getUrl('thumb');
            }
        }

        return $image;
    }

    public function getThumbnailImagePaths($mediaCollectionName = 'images')
    {
        $images = [];
        if ($this->getMedia($mediaCollectionName)) {
            foreach ($this->getMedia($mediaCollectionName)->sortByDesc('created_at') as $media) {
                if ($media->hasGeneratedConversion('thumb')) {
                    $images[] = $media->getUrl('thumb');
                }
            }
        }

        return $images;
    }


    
    // get PDF files Code Part  // get PDF files Code Section
    
    public function getPDFPath($mediaCollectionName = 'pdfs')
    {
        $pdfUrl = null;

        if ($pdf = $this->getMedia($mediaCollectionName)->sortByDesc('created_at')->first()) {
            $pdfUrl = url($pdf->getUrl());
        }

        return $pdfUrl;
    }


}
