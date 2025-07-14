<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use App\Models\UserAvatar;
use App\Models\UserActivity;
use App\Models\UserInvitation;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Services\UserInvitationService;
use App\Services\AvatarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class UserManagementTest
 * 
 * Unit tests for user management functionality including user CRUD operations,
 * profile management, avatar handling, and invitation system.
 * 
 * @package Tests\Unit
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;
    protected User $adminUser;
    protected Role $adminRole;
    protected Role $userRole;
    protected UserController $userController;
    protected ProfileController $profileController;
    protected UserInvitationService $invitationService;
    protected AvatarService $avatarService;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpRoles();
        $this->setUpUsers();
        $this->setUpServices();
        
        Storage::fake('public');
    }

    /**
     * Set up test roles
     */
    private function setUpRoles(): void
    {
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System Administrator'
        ]);
        
        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => 'Regular User',
            'description' => 'Regular system user'
        ]);
    }

    /**
     * Set up test users
     */
    private function setUpUsers(): void
    {
        $this->adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'status' => 'active',
            'is_first_login' => false,
            'terms_accepted' => true
        ]);
        
        $this->testUser = User::factory()->create([
            'email' => 'user@example.com',
            'status' => 'active',
            'is_first_login' => false,
            'terms_accepted' => true
        ]);
        
        UserRole::create([
            'user_id' => $this->adminUser->id,
            'role_id' => $this->adminRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
        
        UserRole::create([
            'user_id' => $this->testUser->id,
            'role_id' => $this->userRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
    }

    /**
     * Set up services
     */
    private function setUpServices(): void
    {
        $this->avatarService = new AvatarService();
        $this->invitationService = new UserInvitationService();
        // Note: Controllers are not instantiated in unit tests due to middleware dependencies
        // $this->userController = new UserController($this->invitationService);
        // $this->profileController = new ProfileController($this->avatarService);
    }

    /**
     * Test user creation
     */
    public function test_user_creation(): void
    {
        $userData = [
            'first_name' => 'New',
            'last_name' => 'User',
            'email' => 'newuser@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'is_first_login' => true,
            'terms_accepted' => false
        ];
        
        $user = User::create($userData);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('New', $user->first_name);
        $this->assertEquals('User', $user->last_name);
        $this->assertEquals('newuser@example.com', $user->email);
        $this->assertEquals('active', $user->status);
        $this->assertTrue($user->is_first_login);
        $this->assertFalse($user->terms_accepted);
    }

    /**
     * Test user update
     */
    public function test_user_update(): void
    {
        $originalFirstName = $this->testUser->first_name;
        $newFirstName = 'Updated';
        $newLastName = 'Name';
        
        $this->testUser->update([
            'first_name' => $newFirstName,
            'last_name' => $newLastName
        ]);
        
        $this->assertEquals($newFirstName, $this->testUser->fresh()->first_name);
        $this->assertEquals($newLastName, $this->testUser->fresh()->last_name);
        $this->assertNotEquals($originalFirstName, $this->testUser->fresh()->first_name);
    }

    /**
     * Test user soft delete
     */
    public function test_user_soft_delete(): void
    {
        $userId = $this->testUser->id;
        
        $this->testUser->delete();
        
        // User should be soft deleted
        $this->assertSoftDeleted('idbi_users', ['id' => $userId]);
        
        // User should not be found in normal queries
        $this->assertNull(User::find($userId));
        
        // User should be found with trashed
        $this->assertNotNull(User::withTrashed()->find($userId));
    }

    /**
     * Test user status management
     */
    public function test_user_status_management(): void
    {
        // Test active status
        $this->testUser->update(['status' => 'active']);
        $this->assertEquals('active', $this->testUser->fresh()->status);
        $this->assertTrue($this->testUser->fresh()->isActive());
        
        // Test suspended status
        $this->testUser->update(['status' => 'suspended']);
        $this->assertEquals('suspended', $this->testUser->fresh()->status);
        $this->assertFalse($this->testUser->fresh()->isActive());
        
        // Test pending status
        $this->testUser->update(['status' => 'pending']);
        $this->assertEquals('pending', $this->testUser->fresh()->status);
        $this->assertFalse($this->testUser->fresh()->isActive());
    }

    /**
     * Test temporary password generation
     */
    public function test_temporary_password_generation(): void
    {
        $tempPassword = $this->invitationService->generateTemporaryPassword();
        
        // Should be 8 characters
        $this->assertEquals(8, strlen($tempPassword));
        
        // Should contain uppercase, lowercase, and numbers
        $this->assertMatchesRegularExpression('/[A-Z]/', $tempPassword);
        $this->assertMatchesRegularExpression('/[a-z]/', $tempPassword);
        $this->assertMatchesRegularExpression('/[0-9]/', $tempPassword);
    }

    /**
     * Test user invitation process
     */
    public function test_user_invitation_process(): void
    {
        $invitationData = [
            'first_name' => 'Invited',
            'last_name' => 'User',
            'email' => 'invited@example.com',
            'roles' => [$this->userRole->id]
        ];
        
        $result = $this->invitationService->createInvitation($invitationData);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('temp_password', $result);
        
        $invitedUser = $result['user'];
        $this->assertEquals('Invited User', $invitedUser->name);
        $this->assertEquals('invited@example.com', $invitedUser->email);
        $this->assertTrue($invitedUser->is_first_login);
        $this->assertFalse($invitedUser->terms_accepted);
    }

    /**
     * Test avatar upload
     */
    public function test_avatar_upload(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);
        
        $result = $this->avatarService->uploadAvatar($this->testUser->id, $file);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('avatar', $result);
        
        $avatar = $result['avatar'];
        $this->assertEquals($this->testUser->id, $avatar->user_id);
        $this->assertNotNull($avatar->file_path);
        $this->assertEquals('jpg', $avatar->file_extension);
        
        // Check if file was stored
        Storage::disk('public')->assertExists($avatar->file_path);
    }

    /**
     * Test avatar validation
     */
    public function test_avatar_validation(): void
    {
        // Test invalid file type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);
        $result = $this->avatarService->uploadAvatar($this->testUser->id, $invalidFile);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        
        // Test oversized file
        $oversizedFile = UploadedFile::fake()->image('large.jpg', 1000, 1000)->size(3000); // 3MB
        $result = $this->avatarService->uploadAvatar($this->testUser->id, $oversizedFile);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test avatar cropping
     */
    public function test_avatar_cropping(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 800, 600);
        
        $result = $this->avatarService->uploadAvatar($this->testUser->id, $file);
        
        $this->assertTrue($result['success']);
        
        $avatar = $result['avatar'];
        
        // Check if image was resized to 400x400
        $imagePath = Storage::disk('public')->path($avatar->file_path);
        if (file_exists($imagePath)) {
            $imageSize = getimagesize($imagePath);
            $this->assertEquals(400, $imageSize[0]); // width
            $this->assertEquals(400, $imageSize[1]); // height
        }
    }

    /**
     * Test profile update
     */
    public function test_profile_update(): void
    {
        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Profile',
            'email_notifications' => false
        ];
        
        $this->testUser->update($updateData);
        
        $updatedUser = $this->testUser->fresh();
        $this->assertEquals('Updated', $updatedUser->first_name);
        $this->assertEquals('Profile', $updatedUser->last_name);
        $this->assertFalse($updatedUser->email_notifications);
    }

    /**
     * Test password change in profile
     */
    public function test_profile_password_change(): void
    {
        $oldPassword = 'OldPassword123!';
        $newPassword = 'NewPassword123!';
        
        // Set old password
        $this->testUser->update(['password' => Hash::make($oldPassword)]);
        
        // Verify old password works
        $this->assertTrue(Hash::check($oldPassword, $this->testUser->password));
        
        // Change password
        $this->testUser->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now()
        ]);
        
        // Verify new password works and old doesn't
        $updatedUser = $this->testUser->fresh();
        $this->assertTrue(Hash::check($newPassword, $updatedUser->password));
        $this->assertFalse(Hash::check($oldPassword, $updatedUser->password));
    }

    /**
     * Test user activity logging
     */
    public function test_user_activity_logging(): void
    {
        $activityData = [
            'user_id' => $this->testUser->id,
            'action' => 'profile_updated',
            'description' => 'User updated their profile',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser'
        ];
        
        $activity = UserActivity::create($activityData);
        
        $this->assertInstanceOf(UserActivity::class, $activity);
        $this->assertEquals($this->testUser->id, $activity->user_id);
        $this->assertEquals('profile_updated', $activity->action);
    }

    /**
     * Test user search functionality
     */
    public function test_user_search(): void
    {
        // Create additional test users
        User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);
        User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com']);
        
        // Search by first name
        $results = User::where('first_name', 'LIKE', '%John%')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('John', $results->first()->first_name);
        $this->assertEquals('Doe', $results->first()->last_name);
        
        // Search by email
        $results = User::where('email', 'LIKE', '%jane%')->get();
        $this->assertEquals(1, $results->count());
        $this->assertEquals('jane@example.com', $results->first()->email);
    }

    /**
     * Test user filtering by status
     */
    public function test_user_filtering_by_status(): void
    {
        // Create users with different statuses
        User::factory()->create(['status' => 'active']);
        User::factory()->create(['status' => 'suspended']);
        User::factory()->create(['status' => 'pending']);
        
        // Filter active users
        $activeUsers = User::where('status', 'active')->get();
        $this->assertGreaterThan(0, $activeUsers->count());
        
        // Filter suspended users
        $suspendedUsers = User::where('status', 'suspended')->get();
        $this->assertEquals(1, $suspendedUsers->count());
        
        // Filter pending users
        $pendingUsers = User::where('status', 'pending')->get();
        $this->assertEquals(1, $pendingUsers->count());
    }

    /**
     * Test user role assignment
     */
    public function test_user_role_assignment(): void
    {
        $newRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Manager role'
        ]);
        
        // Assign new role
        UserRole::create([
            'user_id' => $this->testUser->id,
            'role_id' => $newRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
        
        $this->assertTrue($this->testUser->hasRole('manager'));
        $this->assertTrue($this->testUser->hasRole('user'));
        
        // Remove role
        $this->testUser->roles()->detach($newRole);
        
        $this->assertFalse($this->testUser->fresh()->hasRole('manager'));
        $this->assertTrue($this->testUser->fresh()->hasRole('user'));
    }

    /**
     * Test bulk user operations
     */
    public function test_bulk_user_operations(): void
    {
        // Create multiple test users
        $users = User::factory()->count(5)->create(['status' => 'active']);
        $userIds = $users->pluck('id')->toArray();
        
        // Bulk suspend users
        User::whereIn('id', $userIds)->update(['status' => 'suspended']);
        
        $suspendedUsers = User::whereIn('id', $userIds)->get();
        foreach ($suspendedUsers as $user) {
            $this->assertEquals('suspended', $user->status);
        }
        
        // Bulk activate users
        User::whereIn('id', $userIds)->update(['status' => 'active']);
        
        $activeUsers = User::whereIn('id', $userIds)->get();
        foreach ($activeUsers as $user) {
            $this->assertEquals('active', $user->status);
        }
    }

    /**
     * Test user email uniqueness
     */
    public function test_user_email_uniqueness(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // Try to create user with existing email
        User::create([
            'first_name' => 'Duplicate',
            'last_name' => 'User',
            'email' => $this->testUser->email, // Same email as existing user
            'password' => Hash::make('password')
        ]);
    }

    /**
     * Test user UUID generation
     */
    public function test_user_uuid_generation(): void
    {
        $user = User::factory()->create();
        
        $this->assertNotNull($user->id);
        $this->assertTrue(Str::isUuid($user->id));
    }

    /**
     * Test user timestamps
     */
    public function test_user_timestamps(): void
    {
        $user = User::factory()->create();
        
        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
        $this->assertInstanceOf(Carbon::class, $user->created_at);
        $this->assertInstanceOf(Carbon::class, $user->updated_at);
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        Storage::fake('public');
        parent::tearDown();
    }
}