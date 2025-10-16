<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        // Force JSON response for API routes
        if ($request->is('api/*')) {
            $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
            $message = $exception->getMessage() ?: 'Server Error';

            return response()->json([
                'error' => $message,
                'status' => $status
            ], $status);
        }

        return parent::render($request, $exception);
    }
}
