<?php

// Purpose: Password validation service for enforcing password policies
// Related Feature: Password Management - Password Validation Rules
// Dependencies: PasswordHistory model, User model

namespace App\Services;

use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * PasswordValidationService
 * 
 * Handles password validation, policy enforcement, and history tracking
 * for the Analytics Hub authentication system.
 */
class PasswordValidationService
{
    /**
     * Password policy constants as per requirements
     */
    const MIN_LENGTH = 8;
    const HISTORY_LIMIT = 5;
    const EXPIRY_DAYS = 90;
    
    /**
     * Validate password against all policy requirements
     * 
     * @param string $password Plain text password
     * @param string|null $userId User UUID (for history check)
     * @return array Validation result
     */
    public function validatePassword(string $password, ?string $userId = null): array
    {
        $errors = [];
        
        // Basic length requirement
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . self::MIN_LENGTH . ' characters long.';
        }
        
        // Character requirements
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        
        // Check password history if user ID provided
        if ($userId && PasswordHistory::isPasswordReused($userId, $password)) {
            $errors[] = 'Password has been used recently. Please choose a different password.';
        }
        
        // Get strength analysis
        $strengthAnalysis = PasswordHistory::analyzePasswordStrength($password);
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $strengthAnalysis,
        ];
    }
    
    /**
     * Create Laravel validation rules for password
     * 
     * @param string|null $userId User UUID for history validation
     * @return array Laravel validation rules
     */
    public function getValidationRules(?string $userId = null): array
    {
        $rules = [
            'required',
            'string',
            'min:' . self::MIN_LENGTH,
            'regex:/[a-z]/',      // lowercase
            'regex:/[A-Z]/',      // uppercase
            'regex:/[0-9]/',      // numbers
            'regex:/[^a-zA-Z0-9]/', // special characters
        ];
        
        // Add custom rule for password history if user ID provided
        if ($userId) {
            $rules[] = function ($attribute, $value, $fail) use ($userId) {
                if (PasswordHistory::isPasswordReused($userId, $value)) {
                    $fail('Password has been used recently. Please choose a different password.');
                }
            };
        }
        
        return $rules;
    }
    
    /**
     * Get validation messages for password rules
     * 
     * @return array Validation messages
     */
    public function getValidationMessages(): array
    {
        return [
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a string.',
            'password.min' => 'Password must be at least ' . self::MIN_LENGTH . ' characters long.',
            'password.regex' => 'Password must contain uppercase letters, lowercase letters, numbers, and special characters.',
        ];
    }
    
    /**
     * Change user password with validation and history tracking
     * 
     * @param User $user User model instance
     * @param string $newPassword New plain text password
     * @param array $options Additional options for password change
     * @return array Result of password change operation
     */
    public function changePassword(User $user, string $newPassword, array $options = []): array
    {
        // Validate new password
        $validation = $this->validatePassword($newPassword, $user->id);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
                'strength' => $validation['strength'],
            ];
        }
        
        try {
            // Update user password
            $user->password = Hash::make($newPassword);
            $user->password_changed_at = now();
            $user->password_expires_at = now()->addDays(self::EXPIRY_DAYS);
            $user->must_change_password = false;
            $user->save();
            
            // Add to password history
            PasswordHistory::addPasswordToHistory($user->id, $newPassword, array_merge([
                'reason' => PasswordHistory::CHANGE_REASON_USER_REQUESTED,
                'method' => PasswordHistory::CHANGE_METHOD_FORM,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ], $options));
            
            return [
                'success' => true,
                'message' => 'Password changed successfully.',
                'strength' => $validation['strength'],
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to change password. Please try again.'],
                'exception' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Check if user password is expired
     * 
     * @param User $user User model instance
     * @return bool True if password is expired
     */
    public function isPasswordExpired(User $user): bool
    {
        if (!$user->password_expires_at) {
            return false;
        }
        
        return $user->password_expires_at->isPast();
    }
    
    /**
     * Get days until password expires
     * 
     * @param User $user User model instance
     * @return int|null Days until expiry, null if no expiry set
     */
    public function getDaysUntilExpiry(User $user): ?int
    {
        if (!$user->password_expires_at) {
            return null;
        }
        
        return now()->diffInDays($user->password_expires_at, false);
    }
    
    /**
     * Generate temporary password for new users
     * 
     * @param int $length Password length (default 12)
     * @return string Generated temporary password
     */
    public function generateTemporaryPassword(int $length = 12): string
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        // Ensure at least one character from each required set
        $password = '';
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Fill remaining length with random characters from all sets
        $allChars = $lowercase . $uppercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password to randomize character positions
        return str_shuffle($password);
    }
    
    /**
     * Set password as expired for user
     * 
     * @param User $user User model instance
     * @param string $reason Reason for expiry
     * @return bool Success status
     */
    public function expirePassword(User $user, string $reason = 'admin_forced'): bool
    {
        try {
            $user->password_expires_at = now();
            $user->must_change_password = true;
            $user->save();
            
            // Update current password history record
            PasswordHistory::where('user_id', $user->id)
                ->where('is_current', true)
                ->update([
                    'requires_change' => true,
                    'change_reason' => $reason,
                    'expires_at' => now(),
                ]);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}