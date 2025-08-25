<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;
use App\Repositories\Interfaces\UserAuthenticationInterface;
use App\Traits\HandleJsonResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AdminAuthController extends Controller
{
    use HandleJsonResponse;

    protected UserAuthenticationInterface $authRepository;
    public function __construct(UserAuthenticationInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        try {
            $response = $this->authRepository->login($credentials, 1);
            return response()->json($response, 201);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e);
        } catch (UnprocessableEntityHttpException $e) {
            return $this->errorResponse($e);
        }
    }
}
