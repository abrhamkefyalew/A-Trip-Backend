<?php

use Illuminate\Database\Eloquent\Model;

if (!function_exists('abort_if_inactive')) { // this if condition is to check = IF a function with the name 'abort_if_inactive' is defined/created/exists elsewhere in the project, THEN do NOT redefine/recreate it here again 
                                                                            //  IF this helper file is loaded multiple times in a code, the function will be redefined again // so do NOT redeclare the function for multiple loads either
                                                                            //
                                                                            // 'if (!function_exists('abort_if_inactive'))' will help us avoid the following error = = = prevents a "cannot redeclare function" fatal error 

    /**
     * Abort if the given model is null or not active.
     *
     * @param  Model|null  $model
     * @param  string      $type
     * @param  int|string  $id
     * @return void
     */
    function abort_if_inactive(?Model $model, string $type, int|string $id): void
    {
        // '! $model' = handles model deletion in during race condition in concurrency environment
        //              i.e. if a model is deleted by another process after this execution is stated
        //              // very low chance of happening
        if (! $model || $model?->is_active !== 1) {
            abort(422, "The {$type} with ID {$id} is NOT active.");
        }
    }
}




if (!function_exists('abort_if_unapproved')) {

    /**
     * Abort if the given model is null or not approved.
     *
     * @param  Model|null  $model
     * @param  string      $type
     * @param  int|string  $id
     * @return void
     */
    function abort_if_unapproved(?Model $model, string $type, int|string $id): void
    {
        // '! $model' = handles model deletion in during race condition in concurrency environment
        //              i.e. if a model is deleted by another process after this execution is stated
        //              // very low chance of happening
        if (! $model || $model?->is_approved !== 1) {
            abort(422, "The {$type} with ID {$id} is NOT approved.");
        }
    }
}
