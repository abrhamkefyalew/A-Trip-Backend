<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });


        // this helps us, if any validation Exception Occurs in a request and
        // if the url happens to be under api/v1/
        // it logs the error and returns the error
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/v1/*')) {
                Log::alert('Validation exception occurred! Url: ' . $request->url() . ' ' . $e->getMessage(), $e->errors());
                return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
            }
        });


        // this helps us if the requested data is not found and the request happens to be under the /api 
        // it returns the specified response
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Record not found or route not found!', /*'ERROR' => $e->getMessage()*/], 404);
            }
        });



    }
}
