<?php

namespace Tests\Unit\Actions\User;

use App\Actions\User\StoreUsers;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'employee']);
        Role::create(['name' => 'manager']);
    }

    #[Test]
    public function it_creates_a_user_and_attaches_them_to_a_team_via_the_pivot_table(): void
    {
        // 1. Arrange
        Storage::fake('public');
        $manager = User::factory()->create();
        $team = Team::factory()->create();
        $fakeImage = UploadedFile::fake()->image('avatar.jpg');

        $data = [
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
            'password' => 'password123',
            'work_mode' => 'remote',
            'parent_id' => $manager->id,
            'designation' => 'Software Engineer',
            'image' => $fakeImage,
            'role' => 'employee',
            'team_id' => $team->id, // The ID of the team to attach
        ];

        // 2. Act
        $action = new StoreUsers();
        $user = $action->handle($data);

        // 3. Assert
        $this->assertInstanceOf(User::class, $user);

        // Assert against the user's table (no team_id here)
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'john.smith@example.com',
            'parent_id' => $manager->id,
        ]);

        // Assert the image was stored correctly
        Storage::disk('public')->assertExists($user->image);

        // Assert the role was assigned
        $this->assertTrue($user->hasRole('employee'));

        // --- THE CORRECT PIVOT TABLE ASSERTION ---
        // Assert that a record exists in the 'team_user' pivot table
        // linking our new user to the correct team.
        $this->assertDatabaseHas('team_user', [
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);
    }

    #[Test]
    public function it_creates_a_user_without_attaching_a_team_if_team_id_is_not_provided(): void
    {
        // 1. Arrange: Minimal data set without a team_id.
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'password123',
            'role' => 'manager',
        ];

        // 2. Act
        $action = new StoreUsers();
        $user = $action->handle($data);

        // 3. Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertTrue($user->hasRole('manager'));

        // --- CORRECT PIVOT TABLE ASSERTION (MISSING) ---
        // Assert that NO record was created in the pivot table for this user.
        $this->assertDatabaseMissing('team_user', [
            'user_id' => $user->id,
        ]);
    }
}
