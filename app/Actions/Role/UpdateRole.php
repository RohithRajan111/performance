<?php

namespace App\Actions\Role;

use Spatie\Permission\Models\Role;

class UpdateRole
{
    public function handle(array $data, Role $role)
    {
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions']);
    }
}
