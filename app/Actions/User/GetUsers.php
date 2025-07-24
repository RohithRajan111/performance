<?php

namespace App\Actions\User;

use App\Models\User;
use App\Actions\User\CreateUsers;
use Illuminate\Http\Request; // <-- Add this import

class GetUsers
{
	protected $createUsers;

	public function __construct(CreateUsers $createUsers)
	{
		$this->createUsers = $createUsers;
	}

	public function handle(Request $request) // <-- Accept the Request object
	{
		// Reuse additional data from CreateUsers
		$createUsersData = $this->createUsers->handle();

        // --- THE FIX IS HERE ---
        // Start the query and conditionally apply the search filter
		$users = User::with('roles:id,name')
            ->when($request->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString(); // Ensures pagination links include the search query

		return array_merge([
			'users' => $users,
            'filters' => $request->only(['search']), // Pass the current filters back to the view
		], $createUsersData);
	}
}