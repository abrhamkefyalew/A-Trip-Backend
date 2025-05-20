<?php

namespace App\Traits\Api\V1;

trait NonQueuedMediaConversions
{
    public function customizeMediaConversions(): void
    {
        // \Log::info('Registering media conversions...');

        $this->addMediaConversion('optimized')
            ->width(1000)
            ->height(1000)
            ->nonQueued();  // ->nonQueued() : - this can ALSO be configured in 'config/media-library.php' - BUT IF Only spatie 'config' file is published 
                            // added this ->nonQueued() ,  BECAUSE of a change i made in .env - TO be able to work on ASYNCHRONOUS processing
                                                                                            // I have changed the QUEUE value in .env
                                                                                            //                                      // = > FROM QUEUE_CONNECTION=sync (i.e. Synchronous)    - to -     QUEUE_CONNECTION=database (i.e. Asynchronous)

        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->nonQueued();  // ->nonQueued() : - this can ALSO be configured in 'config/media-library.php' - BUT IF Only spatie 'config' file is published 
                            // added this ->nonQueued() ,  BECAUSE of a change i made in .env - TO be able to work on ASYNCHRONOUS processing
                                                                                            // I have changed the QUEUE value in .env
                                                                                            //                                      // = > FROM QUEUE_CONNECTION=sync (i.e. Synchronous)    - to -     QUEUE_CONNECTION=database (i.e. Asynchronous)
        // \Log::info('Media conversions registered successfully.');
    }
}