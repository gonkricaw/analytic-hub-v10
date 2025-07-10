<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

/**
 * Profile Update Request
 * 
 * Handles validation for user profile updates including personal information,
 * password changes, and notification preferences.
 * 
 * @package App\Http\Requests
 * @author Analytics Hub Team
 * @version 1.0.0
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Users can only update their own profile.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Validates personal information, password changes, and preferences.
     * Password validation is conditional - only applied if password fields are provided.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            // Personal Information
            'first_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z\s\'-]+$/', // Only letters, spaces, hyphens, apostrophes
            ],
            'last_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z\s\'-]+$/', // Only letters, spaces, hyphens, apostrophes
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]+$/', // Phone number format
            ],
            'department' => [
                'nullable',
                'string',
                'max:100',
            ],
            'position' => [
                'nullable',
                'string',
                'max:100',
            ],
            'bio' => [
                'nullable',
                'string',
                'max:1000',
            ],
            
            // Notification Preferences
            'email_notifications' => [
                'nullable',
                'boolean',
            ],
        ];
        
        // Password validation - only if password fields are provided
        if ($this->filled('current_password') || $this->filled('password') || $this->filled('password_confirmation')) {
            $rules['current_password'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, auth()->user()->password)) {
                        $fail('The current password is incorrect.');
                    }
                },
            ];
            
            $rules['password'] = [
                'required',
                'string',
                'min:8',
                'max:255',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', // Password complexity
                'different:current_password', // New password must be different from current
            ];
            
            $rules['password_confirmation'] = [
                'required',
                'string',
                'same:password',
            ];
        }
        
        return $rules;
    }
    
    /**
     * Get custom validation messages.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name can only contain letters, spaces, hyphens, and apostrophes.',
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name can only contain letters, spaces, hyphens, and apostrophes.',
            'phone.regex' => 'Please enter a valid phone number.',
            'bio.max' => 'Biography cannot exceed 1000 characters.',
            'current_password.required' => 'Current password is required to change password.',
            'password.required' => 'New password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'password.different' => 'New password must be different from current password.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.same' => 'Password confirmation does not match.',
        ];
    }
    
    /**
     * Get custom attribute names for validation errors.
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'phone' => 'phone number',
            'department' => 'department',
            'position' => 'position',
            'bio' => 'biography',
            'email_notifications' => 'email notifications',
            'current_password' => 'current password',
            'password' => 'new password',
            'password_confirmation' => 'password confirmation',
        ];
    }
    
    /**
     * Prepare the data for validation.
     * 
     * Clean and prepare input data before validation.
     * 
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string fields
        $this->merge([
            'first_name' => $this->filled('first_name') ? trim($this->first_name) : $this->first_name,
            'last_name' => $this->filled('last_name') ? trim($this->last_name) : $this->last_name,
            'phone' => $this->filled('phone') ? trim($this->phone) : $this->phone,
            'department' => $this->filled('department') ? trim($this->department) : $this->department,
            'position' => $this->filled('position') ? trim($this->position) : $this->position,
            'bio' => $this->filled('bio') ? trim($this->bio) : $this->bio,
        ]);
        
        // Convert email_notifications to boolean
        if ($this->has('email_notifications')) {
            $this->merge([
                'email_notifications' => filter_var($this->email_notifications, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
    
    /**
     * Get validated data with only the fields that should be updated.
     * 
     * @return array
     */
    public function getProfileData(): array
    {
        return $this->only([
            'first_name',
            'last_name',
            'phone',
            'department',
            'position',
            'bio',
            'email_notifications',
        ]);
    }
    
    /**
     * Check if password change is requested.
     * 
     * @return bool
     */
    public function isPasswordChangeRequested(): bool
    {
        return $this->filled('current_password') && $this->filled('password');
    }
    
    /**
     * Get the new password if password change is requested.
     * 
     * @return string|null
     */
    public function getNewPassword(): ?string
    {
        return $this->isPasswordChangeRequested() ? $this->password : null;
    }
}
