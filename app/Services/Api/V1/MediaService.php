<?php

namespace App\Services\Api\V1;

use Illuminate\Support\Str;

class MediaService
{
    // abrham comment 1
    // the //images// collection is the default collection name, if mediaCollection is NOT provided


    // abrham comment 2
    // if you can, 
    // you should use different storeImage methods for different Models, to customize default images for models added with no image - or -
    // handle different media collections and such
    public static function storeImage($object, $image, $clearMedia = false, $mediaCollection = 'images')
    {
        if ($clearMedia) {
            $object->clearMediaCollection($mediaCollection);
        }

        $extension = $image->getClientOriginalExtension();

        $object->addMedia($image)->usingFileName(Str::random(12) . '.' . $extension)->toMediaCollection($mediaCollection);

        return $object;
    }

    public static function storeImages($object, $images, $clearMedia = false, $mediaCollection = 'images')
    {
        if ($clearMedia) {
            $object->clearMediaCollection($mediaCollection);
        }

        foreach ($images as $image) {
            $extension = $image->getClientOriginalExtension();

            $object->addMedia($image)->usingFileName(Str::random(12) . '.' . $extension)->toMediaCollection($mediaCollection);
        }

        return $object;
    }

    // CLEAR IMAGES
    
    public static function clearImage($object, $mediaCollection, $clearMedia = false)
    {
        if ($clearMedia) {
            $object->clearMediaCollection($mediaCollection);
        }
    }







    // Other FILEs and PDF files upload Code Section below
    // this uploads any files but we are currently uploading PDFs only
    
    public static function storeFile($object, $file, $clearMedia = false, $mediaCollection = 'pdfs')
    {
        if ($clearMedia) {
            $object->clearMediaCollection($mediaCollection);
        }

        $extension = $file->getClientOriginalExtension();
        $fileName = Str::random(12) . '.' . $extension;

        $object->addMedia($file)
            ->usingFileName($fileName)
            ->toMediaCollection($mediaCollection);

        return $object;
    }


    // CLEAR FILES // PDFs  // NOT Tested
    
    public static function clearFile($object, $mediaCollection, $clearMedia = false)
    {
        if ($clearMedia) {
            $object->clearMediaCollection($mediaCollection);
        }
    }



}
