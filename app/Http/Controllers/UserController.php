<?php

namespace App\Http\Controllers;

use App\Actions\User\DeleteUser;
use App\Actions\User\UpdateUser;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;
use App\Actions\User\GetUsers;
use App\Actions\User\StoreUsers;
use App\Http\Requests\User\StoreUserRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * This method now handles displaying the user list AND providing
     * all necessary data for the create/edit modals.
     */
    public function index(Request $request, GetUsers $getUsers) // <-- Inject the Request
{
    // Pass the incoming request into the action's handle method
    return Inertia::render('Users/Index', $getUsers->handle($request));
}

    /**
     * This method will be called by the modal's create form.
     */
    public function store(StoreUserRequest $request, StoreUsers $storeUsers)
    {
        $storeUsers->handle($request->validated());
        return redirect()->route('users.index')->with('success', 'Employee added successfully.');
    }
    
    /**
     * This method will be called by the modal's edit form.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser)
    {
        $updateUser->handle($user, $request->validated());
        return Redirect::route('users.index')->with('success', 'Employee details updated successfully.');
    }

    /**
     * This method handles user deletion.
     */
    public function destroy(User $user, DeleteUser $deleteUser)
    {
        $deleteUser->handle($user);
        return Redirect::route('users.index')->with('success', 'Employee deleted successfully.');
    }
    

    
}