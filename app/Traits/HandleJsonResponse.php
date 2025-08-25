<?php

namespace App\Traits;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Http\JsonResponse;

trait HandleJsonResponse
{
    protected function successResponse(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    protected function errorResponse(Exception $e, int $defaultStatus = 400): JsonResponse
    {
        $status = match (true) {
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => 404,
            $e instanceof UnprocessableEntityHttpException => 422,
            default => $defaultStatus,
        };

        return response()->json([
            'success' => false,
            'message' => 'An error occurred',
            'errors' => $e->getMessage()
        ], $status);
    }
}
