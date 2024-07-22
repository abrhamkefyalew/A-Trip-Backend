<?php

namespace App\Services\Api\V1;

use Illuminate\Support\Str;

class MediaService
{
    // abrham comment 1
    // you can set some default value for $image, for those who do not upload image, you can set the default value to CONSTANT, 
    // the CONSTANT could be a path of an image in the public directory

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

        $object->addMedia($image)->usingFileName(Str::random(12).$extension)->toMediaCollection($mediaCollection);

        return $object;
    }

    public static function storeImages($object, $images, $clearMedia = false, $mediaCollection = 'images')
    {
        if ($clearMedia) {
            $object->clearMediaCollection($mediaCollection);
        }

        foreach ($images as $image) {
            $extension = $image->getClientOriginalExtension();

            $object->addMedia($image)->usingFileName(Str::random(12).$extension)->toMediaCollection($mediaCollection);
        }

        return $object;
    }

    // CLEAR IMAGES
    public static function clearImage($object, $clearMedia = false, $mediaCollection)
    {
        if ($clearMedia) {
            $object->clearMediaCollection($mediaCollection);
        }
    }
}
