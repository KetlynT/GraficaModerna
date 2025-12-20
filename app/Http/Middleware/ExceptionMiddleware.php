<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Database\ConcurrencyError;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExceptionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (Throwable $ex) {
            $traceId = (string) Str::uuid();

            Log::error('Um erro não tratado ocorreu.', [
                'traceId' => $traceId,
                'exception' => $ex,
            ]);

            $statusCode = match (true) {
                $ex instanceof \InvalidArgumentException => Response::HTTP_BAD_REQUEST,
                $ex instanceof \LogicException => Response::HTTP_BAD_REQUEST,
                $ex instanceof UnauthorizedHttpException => Response::HTTP_UNAUTHORIZED,
                $ex instanceof ModelNotFoundException => Response::HTTP_NOT_FOUND,
                $ex instanceof ConcurrencyError => Response::HTTP_CONFLICT,
                $ex instanceof HttpException => $ex->getStatusCode(),
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            $message = match (true) {
                $ex instanceof ConcurrencyError => 'O item foi modificado por outro processo. Por favor, tente novamente.',
                $statusCode >= 400 && $statusCode < 500 && $statusCode !== Response::HTTP_CONFLICT => $ex->getMessage(),
                app()->environment('local', 'development') => $ex->getMessage(),
                default => 'Ocorreu um erro interno no servidor.',
            };

            return response()->json([
                'statusCode' => $statusCode,
                'message' => $message,
                'traceId' => $traceId,
            ], $statusCode)->withHeaders([
                'X-Trace-Id' => $traceId,
            ]);
        }
    }
}
