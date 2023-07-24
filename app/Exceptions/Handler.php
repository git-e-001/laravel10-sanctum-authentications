<?php

namespace App\Exceptions;

use App\Utils\HttpStatusCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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
            return false;
        });
    }

    /**
     * @param Request $request
     * @param Throwable $e
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $exception): Response|JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        if ($request->is('api/*')) {
            if ($exception instanceof \Illuminate\Validation\ValidationException) {
                $errors = [];
                foreach ($exception->errors() as $key => $error) {
                    $errors[$key] = $error[0];
                }
                logger(__METHOD__, [
                    'error' => true,
                    'message' => $exception->getMessage(),
                    'line' => $exception->getLine(),
                    'file' => $exception->getFile(),
                    'data' => $errors
                ]);
                return response()->json([
                    'error' => true,
                    'message' => $errors
                ], HttpStatusCode::UNPROCESSABLE_ENTITY);
            } elseif ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'error' => true,
                    'message' => HttpStatusCode::$statusTexts[HttpStatusCode::NOT_FOUND]
                ], HttpStatusCode::NOT_FOUND);
            } elseif ($exception instanceof AuthenticationException || $exception instanceof OAuthServerException) {
                return response()->json([
                    'error' => true,
                    'message' => HttpStatusCode::$statusTexts[HttpStatusCode::UNAUTHORIZED]
                ], HttpStatusCode::UNAUTHORIZED);
            } elseif ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => true,
                    'message' => HttpStatusCode::$statusTexts[HttpStatusCode::NOT_FOUND] . ' Route'
                ], HttpStatusCode::NOT_FOUND);
            } elseif ($exception instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'error' => true,
                    'message' => HttpStatusCode::$statusTexts[HttpStatusCode::METHOD_NOT_ALLOWED] . ' Route Method Not Allow'
                ], HttpStatusCode::METHOD_NOT_ALLOWED);
            } elseif ($exception instanceof AuthorizationException) {
                return response()->json([
                    'error' => true,
                    'message' => HttpStatusCode::$statusTexts[HttpStatusCode::FORBIDDEN]
                ], HttpStatusCode::FORBIDDEN);
            } elseif ($exception instanceof FileNotFoundException) {
                return response()->json([
                    'error' => true,
                    'message' => HttpStatusCode::$statusTexts[HttpStatusCode::NOT_FOUND]
                ], HttpStatusCode::NOT_FOUND);
            } elseif ($exception instanceof \Exception) {
                return response()->json([
                    'error' => true,
                    'message' => HttpStatusCode::$statusTexts[HttpStatusCode::INTERNAL_SERVER_ERROR]
                ], HttpStatusCode::INTERNAL_SERVER_ERROR);
            } elseif ($exception instanceof \ErrorException) {
                return response()->json([
                    'error' => true,
                    'message' => HttpStatusCode::$statusTexts[HttpStatusCode::INTERNAL_SERVER_ERROR]
                ], HttpStatusCode::INTERNAL_SERVER_ERROR);
            }
        }
        return parent::render($request, $exception);
    }
}
