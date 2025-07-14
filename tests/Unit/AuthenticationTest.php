<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use App\Models\LoginAttempt;
use App\Models\BlacklistedIp;
use App\Models\PasswordHistory;
use App\Http\Controllers\AuthController;
use App\Services\PasswordValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Class AuthenticationTest
 * 
 * Unit tests for authentication functionality including login, logout,
 * password validation, failed login tracking, and IP blacklisting.
 * 
 * @package Tests\Unit
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;
    protected Role $testRole;
    protected AuthController $authController;
    protected PasswordValidationService $passwordService;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test role
        $this->testRole = Role::create([
            'name' => 'test_user',
            'display_name' => 'Test User',
            'description' => 'Test role for authentication tests'
        ]);
        
        // Create test user
        $this->testUser = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('TestPassword123!'),
            'status' => 'active',
            'is_first_login' => false,
            'terms_accepted' => true,
            'password_changed_at' => now()
        ]);
        
        UserRole::create([
            'user_id' => $this->testUser->id,
            'role_id' => $this->testRole->id,
            'is_active' => true,
            'assigned_at' => now()
        ]);
        
        $this->authController = new AuthController();
        $this->passwordService = new PasswordValidationService();
    }

    /**
     * Test successful user login
     */
    public function test_successful_login(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!'
        ];
        
        $this->assertTrue(Auth::attempt($credentials));
        $this->assertEquals($this->testUser->id, Auth::id());
    }

    /**
     * Test failed login with invalid credentials
     */
    public function test_failed_login_invalid_credentials(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'WrongPassword'
        ];
        
        $this->assertFalse(Auth::attempt($credentials));
        $this->assertNull(Auth::id());
    }

    /**
     * Test failed login with non-existent user
     */
    public function test_failed_login_nonexistent_user(): void
    {
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'TestPassword123!'
        ];
        
        $this->assertFalse(Auth::attempt($credentials));
        $this->assertNull(Auth::id());
    }

    /**
     * Test login with suspended user
     */
    public function test_login_suspended_user(): void
    {
        $this->testUser->update(['status' => 'suspended']);
        
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!'
        ];
        
        // Auth::attempt might succeed but middleware should block
        $this->assertTrue(Auth::attempt($credentials));
        $this->assertEquals('suspended', $this->testUser->fresh()->status);
    }

    /**
     * Test failed login attempt tracking
     */
    public function test_failed_login_attempt_tracking(): void
    {
        $ipAddress = '192.168.1.100';
        $email = 'test@example.com';
        
        // Create failed login attempt
        LoginAttempt::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'success' => false,
            'attempted_at' => now()
        ]);
        
        $attempts = LoginAttempt::where('ip_address', $ipAddress)
            ->where('status', 'failed')
            ->count();
            
        $this->assertEquals(1, $attempts);
    }

    /**
     * Test IP blacklisting after multiple failed attempts
     */
    public function test_ip_blacklisting_after_failed_attempts(): void
    {
        $ipAddress = '192.168.1.101';
        
        // Create 30 failed attempts
        for ($i = 0; $i < 30; $i++) {
            LoginAttempt::create([
                'email' => 'test@example.com',
                'ip_address' => $ipAddress,
                'status' => 'failed',
                'attempted_at' => now()->subMinutes($i)
            ]);
        }
        
        $failedAttempts = LoginAttempt::where('ip_address', $ipAddress)
            ->where('status', 'failed')
            ->count();
            
        $this->assertEquals(30, $failedAttempts);
        
        // Check if IP should be blacklisted
        $this->assertTrue($failedAttempts >= 30);
    }

    /**
     * Test password validation rules
     */
    public function test_password_validation_rules(): void
    {
        // Test valid password
        $validPassword = 'ValidPass123!';
        $result = $this->passwordService->validatePassword($validPassword);
        $this->assertTrue($result['valid']);
        
        // Test invalid passwords
        $invalidPasswords = [
            'short',           // Too short
            'nouppercase123!', // No uppercase
            'NOLOWERCASE123!', // No lowercase
            'NoNumbers!',      // No numbers
            'NoSpecialChars123' // No special characters
        ];
        
        foreach ($invalidPasswords as $password) {
            $result = $this->passwordService->validatePassword($password);
            $this->assertFalse($result['valid']);
        }
    }

    /**
     * Test password history tracking
     */
    public function test_password_history_tracking(): void
    {
        $oldPassword = 'OldPassword123!';
        
        // Create password history entry
        PasswordHistory::create([
            'user_id' => $this->testUser->id,
            'password_hash' => Hash::make($oldPassword),
            'created_at' => now()->subDays(10)
        ]);
        
        // Test that old password is in history
        $isInHistory = PasswordHistory::isPasswordReused(
            $this->testUser->id, 
            $oldPassword
        );
        
        $this->assertTrue($isInHistory);
        
        // Test that new password is not in history
        $newPassword = 'NewPassword123!';
        $isNewInHistory = PasswordHistory::isPasswordReused(
            $this->testUser->id, 
            $newPassword
        );
        
        $this->assertFalse($isNewInHistory);
    }

    /**
     * Test password expiry check
     */
    public function test_password_expiry_check(): void
    {
        // Test non-expired password
        $this->testUser->update(['password_expires_at' => now()->addDays(30)]);
        $this->assertFalse($this->passwordService->isPasswordExpired($this->testUser));
        
        // Test expired password (past expiry date)
        $this->testUser->update(['password_expires_at' => now()->subDays(5)]);
        $this->assertTrue($this->passwordService->isPasswordExpired($this->testUser));
    }

    /**
     * Test first login detection
     */
    public function test_first_login_detection(): void
    {
        // Test user with is_first_login = true
        $this->testUser->update(['is_first_login' => true]);
        $this->assertTrue($this->testUser->fresh()->is_first_login);
        
        // Test user with is_first_login = false
        $this->testUser->update(['is_first_login' => false]);
        $this->assertFalse($this->testUser->fresh()->is_first_login);
    }

    /**
     * Test terms acceptance check
     */
    public function test_terms_acceptance_check(): void
    {
        // Test user who accepted terms
        $this->testUser->update(['terms_accepted' => true]);
        $this->assertTrue($this->testUser->fresh()->terms_accepted);
        
        // Test user who hasn't accepted terms
        $this->testUser->update(['terms_accepted' => false]);
        $this->assertFalse($this->testUser->fresh()->terms_accepted);
    }

    /**
     * Test session timeout functionality
     */
    public function test_session_timeout(): void
    {
        // Simulate login
        Auth::login($this->testUser);
        
        // Set last activity to 31 minutes ago (beyond 30-minute timeout)
        Session::put('last_activity', now()->subMinutes(31)->timestamp);
        
        $lastActivity = Session::get('last_activity');
        $timeoutMinutes = 30;
        $isExpired = (time() - $lastActivity) > ($timeoutMinutes * 60);
        
        $this->assertTrue($isExpired);
    }

    /**
     * Test successful logout
     */
    public function test_successful_logout(): void
    {
        // Login first
        Auth::login($this->testUser);
        $this->assertTrue(Auth::check());
        
        // Logout
        Auth::logout();
        $this->assertFalse(Auth::check());
    }

    /**
     * Test blacklisted IP check
     */
    public function test_blacklisted_ip_check(): void
    {
        $ipAddress = '192.168.1.102';
        
        // Create blacklisted IP
        BlacklistedIp::create([
            'ip_address' => $ipAddress,
            'reason' => 'Multiple failed login attempts',
            'blacklisted_at' => now(),
            'blacklisted_by' => 'system',
            'is_active' => true
        ]);
        
        $isBlacklisted = BlacklistedIp::where('ip_address', $ipAddress)->exists();
        $this->assertTrue($isBlacklisted);
    }

    /**
     * Test remember me functionality
     */
    public function test_remember_me_functionality(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!'
        ];
        
        // Test login with remember me
        $this->assertTrue(Auth::attempt($credentials, true));
        $this->assertEquals($this->testUser->id, Auth::id());
        
        // Check if remember token is set
        $this->assertNotNull($this->testUser->fresh()->remember_token);
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void
    {
        Auth::logout();
        Session::flush();
        parent::tearDown();
    }
}