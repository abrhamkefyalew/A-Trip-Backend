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
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->nonQueued();

        // \Log::info('Media conversions registered successfully.');
    }
}