<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class UserRepository implements UserRepositoryInterface
{
    /**  @return EloquentCollection<int, User> */
    public function index(): EloquentCollection
    {
        return User::all();
    }

    public function show(int $id): User
    {
        try {
            return User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException('User not found: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Failed to find user: ' . $e->getMessage());
        }
    }
}
