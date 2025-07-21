<?php
namespace App\Http\Controllers;

use App\Actions\User\CreateUsers;
use App\Actions\User\DeleteUser;
use App\Actions\User\EditUser;
use App\Actions\User\UpdateUser;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;
use App\Actions\User\GetUsers;
use App\Actions\User\StoreUsers;
use App\Http\Requests\User\StoreUserRequest;
use Inertia\Inertia;


class UserController extends Controller
{
   public function index(GetUsers $getUsers)
{
    return Inertia::render('Users/Index', $getUsers->handle());
}

public function create(CreateUsers $createUsers)
{
    $data = $createUsers->handle();

    return Inertia::render('Users/Create', $data);
}

    public function store(StoreUserRequest $request,StoreUsers $storeUsers)
    {
        $storeUsers->handle($request->validated());

        return redirect()->route('users.index')->with('success', 'Employee added successfully.');
    }
    public function edit(User $user, EditUser $editUser)
    {
        return Inertia::render('Users/Edit', $editUser->handle($user));
    }

    
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser)
    {
        $updateUser->handle($user, $request->validated());
        return Redirect::route('users.index')->with('success', 'Employee details updated successfully.');
    }

    
    public function destroy(User $user, DeleteUser $deleteUser)
    {
        $deleteUser->handle($user);
        return Redirect::route('users.index')->with('success', 'Employee deleted successfully.');
    }



}
