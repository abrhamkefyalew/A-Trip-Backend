<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException; // FIX: added to catch model not found
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



    // /**
    //  * ADDED by ME / ABRHAM: Override the render method to catch ModelNotFoundException directly
    //  * 
    //  * OPTIONAL
    //  */
    // public function render($request, Throwable $exception)
    // {
    //     // FIX: Handle Eloquent model not found exception
    //     if ($exception instanceof ModelNotFoundException) {
    //         if ($request->is('api/*')) {
    //             return response()->json(['message' => 'Record not found!'], 404);
    //         }
    //     }

    //     // FIX: Optionally handle 404 route not found explicitly here too
    //     if ($exception instanceof NotFoundHttpException) {
    //         if ($request->is('api/*')) {
    //             return response()->json(['message' => 'Route not found!'], 404);
    //         }
    //     }

    //     // OPTIONAL: Authentication failure
    //     if ($exception instanceof AuthenticationException) {
    //         if ($request->is('api/*')) {
    //             return response()->json(['message' => 'Unauthenticated.'], 401);
    //         }
    //     }

    //     // OPTIONAL: Access denied
    //     if ($exception instanceof AuthorizationException) {
    //         if ($request->is('api/*')) {
    //             return response()->json(['message' => 'You are not authorized to access this resource.'], 403);
    //         }
    //     }



    //     return parent::render($request, $exception);
    // }



    /**
     * ADDED by ME / ABRHAM: Log all unhandled exceptions with class, message, and trace.
     */
    public function report(Throwable $exception): void
    {
        Log::error("Unhandled exception: " . get_class($exception), [
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        parent::report($exception);
    }



}
