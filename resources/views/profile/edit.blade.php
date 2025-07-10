@extends('layouts.app')

@section('title', 'Edit Profile')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('profile.show') }}">My Profile</a></li>
    <li class="breadcrumb-item active">Edit Profile</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<style>
    .profile-edit-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        color: white;
    }
    
    .edit-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
    }
    
    .edit-card h5 {
        color: #495057;
        margin-bottom: 1.5rem;
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.5rem;
    }
    
    .avatar-upload-section {
        text-align: center;
        margin-bottom: 2rem;
    }
    
    .current-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 4px solid #e9ecef;
        overflow: hidden;
        margin: 0 auto 1rem;
        position: relative;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .current-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .avatar-placeholder {
        font-size: 3rem;
        font-weight: bold;
        color: #6c757d;
    }
    
    .avatar-upload-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-upload {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
        font-weight: 500;
    }
    
    .btn-remove {
        background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%);
        border: none;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
        font-weight: 500;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .form-control {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        padding: 0.75rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    
    .password-section {
        border-top: 2px solid #e9ecef;
        padding-top: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .password-requirements {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.25rem;
        padding: 1rem;
        margin-top: 0.5rem;
    }
    
    .password-requirements ul {
        margin: 0;
        padding-left: 1.5rem;
    }
    
    .password-requirements li {
        margin-bottom: 0.25rem;
        font-size: 0.875rem;
    }
    
    .requirement-met {
        color: #28a745;
    }
    
    .requirement-unmet {
        color: #dc3545;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 0.25rem;
        font-weight: 500;
        font-size: 1rem;
    }
    
    .btn-cancel {
        background: #6c757d;
        border: none;
        color: white;
        padding: 0.75rem 2rem;
        border-radius: 0.25rem;
        font-weight: 500;
        font-size: 1rem;
    }
    
    /* Cropper Modal Styles */
    .cropper-container {
        max-height: 400px;
    }
    
    .crop-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #dee2e6;
        margin: 0 auto;
    }
    
    .character-count {
        font-size: 0.875rem;
        color: #6c757d;
        text-align: right;
        margin-top: 0.25rem;
    }
    
    .character-count.warning {
        color: #ffc107;
    }
    
    .character-count.danger {
        color: #dc3545;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="profile-edit-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile</h3>
                <p class="mb-0 mt-1 opacity-75">Update your personal information and preferences</p>
            </div>
            <div class="col-auto">
                <a href="{{ route('profile.show') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Profile
                </a>
            </div>
        </div>
    </div>

    <form id="profileForm" method="POST" action="{{ route('profile.update') }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- Avatar Upload Section -->
            <div class="col-md-4">
                <div class="edit-card">
                    <h5><i class="fas fa-camera me-2"></i>Profile Picture</h5>
                    
                    <div class="avatar-upload-section">
                        <div class="current-avatar">
                            @if($user->avatar && $user->avatar->file_url)
                                <img src="{{ $user->avatar->file_url }}" alt="{{ $user->full_name }}" id="currentAvatar">
                            @else
                                <div class="avatar-placeholder">
                                    {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="avatar-upload-buttons">
                            <button type="button" class="btn btn-upload" id="uploadAvatarBtn">
                                <i class="fas fa-upload me-2"></i>Upload New
                            </button>
                            @if($user->avatar)
                                <button type="button" class="btn btn-remove" id="removeAvatarBtn">
                                    <i class="fas fa-trash me-2"></i>Remove
                                </button>
                            @endif
                        </div>
                        
                        <input type="file" id="avatarInput" accept="image/jpeg,image/jpg,image/png" style="display: none;">
                        
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                JPG or PNG, max 2MB, recommended 400x400px
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Personal Information -->
            <div class="col-md-8">
                <div class="edit-card">
                    <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" 
                                       class="form-control @error('first_name') is-invalid @enderror" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="{{ old('first_name', $user->first_name) }}" 
                                       required 
                                       maxlength="50">
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" 
                                       class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="{{ old('last_name', $user->last_name) }}" 
                                       required 
                                       maxlength="50">
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone', $user->phone) }}" 
                                       maxlength="20"
                                       placeholder="+1 (555) 123-4567">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" 
                                       class="form-control @error('department') is-invalid @enderror" 
                                       id="department" 
                                       name="department" 
                                       value="{{ old('department', $user->department) }}" 
                                       maxlength="100"
                                       placeholder="e.g., Marketing, IT, Sales">
                                @error('department')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="position" class="form-label">Position/Title</label>
                        <input type="text" 
                               class="form-control @error('position') is-invalid @enderror" 
                               id="position" 
                               name="position" 
                               value="{{ old('position', $user->position) }}" 
                               maxlength="100"
                               placeholder="e.g., Senior Developer, Marketing Manager">
                        @error('position')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="form-group">
                        <label for="bio" class="form-label">Biography</label>
                        <textarea class="form-control @error('bio') is-invalid @enderror" 
                                  id="bio" 
                                  name="bio" 
                                  rows="4" 
                                  maxlength="1000"
                                  placeholder="Tell us about yourself...">{{ old('bio', $user->bio) }}</textarea>
                        @error('bio')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="character-count" id="bioCount">0/1000 characters</div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="email_notifications" 
                                   name="email_notifications" 
                                   value="1"
                                   {{ old('email_notifications', $user->email_notifications) ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_notifications">
                                <strong>Email Notifications</strong>
                                <br>
                                <small class="text-muted">Receive email notifications for important updates and activities</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Password Change Section -->
        <div class="row">
            <div class="col-12">
                <div class="edit-card">
                    <h5><i class="fas fa-lock me-2"></i>Change Password</h5>
                    <p class="text-muted">Leave blank if you don't want to change your password</p>
                    
                    <div class="password-section">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" 
                                           class="form-control @error('current_password') is-invalid @enderror" 
                                           id="current_password" 
                                           name="current_password"
                                           placeholder="Enter current password">
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password"
                                           placeholder="Enter new password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation"
                                           placeholder="Confirm new password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="password-requirements" id="passwordRequirements" style="display: none;">
                            <strong>Password Requirements:</strong>
                            <ul id="requirementsList">
                                <li id="req-length" class="requirement-unmet">At least 8 characters long</li>
                                <li id="req-uppercase" class="requirement-unmet">Contains uppercase letter (A-Z)</li>
                                <li id="req-lowercase" class="requirement-unmet">Contains lowercase letter (a-z)</li>
                                <li id="req-number" class="requirement-unmet">Contains number (0-9)</li>
                                <li id="req-special" class="requirement-unmet">Contains special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="edit-card">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('profile.show') }}" class="btn btn-cancel">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-save">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Avatar Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropModalLabel">
                    <i class="fas fa-crop me-2"></i>Crop Profile Picture
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="cropper-container">
                            <img id="cropImage" style="max-width: 100%;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h6>Preview</h6>
                            <div class="crop-preview" id="cropPreview"></div>
                            <small class="text-muted mt-2 d-block">
                                Drag to reposition<br>
                                Scroll to zoom
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="cropAndUpload">
                    <i class="fas fa-check me-2"></i>Crop & Upload
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
<script>
$(document).ready(function() {
    let cropper;
    let selectedFile;
    
    // Character count for bio
    function updateBioCount() {
        const bioText = $('#bio').val();
        const count = bioText.length;
        const countElement = $('#bioCount');
        
        countElement.text(count + '/1000 characters');
        
        if (count > 900) {
            countElement.removeClass('warning').addClass('danger');
        } else if (count > 800) {
            countElement.removeClass('danger').addClass('warning');
        } else {
            countElement.removeClass('warning danger');
        }
    }
    
    $('#bio').on('input', updateBioCount);
    updateBioCount();
    
    // Password requirements validation
    $('#password').on('input', function() {
        const password = $(this).val();
        
        if (password.length > 0) {
            $('#passwordRequirements').show();
            
            // Check length
            if (password.length >= 8) {
                $('#req-length').removeClass('requirement-unmet').addClass('requirement-met');
            } else {
                $('#req-length').removeClass('requirement-met').addClass('requirement-unmet');
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                $('#req-uppercase').removeClass('requirement-unmet').addClass('requirement-met');
            } else {
                $('#req-uppercase').removeClass('requirement-met').addClass('requirement-unmet');
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                $('#req-lowercase').removeClass('requirement-unmet').addClass('requirement-met');
            } else {
                $('#req-lowercase').removeClass('requirement-met').addClass('requirement-unmet');
            }
            
            // Check number
            if (/[0-9]/.test(password)) {
                $('#req-number').removeClass('requirement-unmet').addClass('requirement-met');
            } else {
                $('#req-number').removeClass('requirement-met').addClass('requirement-unmet');
            }
            
            // Check special character
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                $('#req-special').removeClass('requirement-unmet').addClass('requirement-met');
            } else {
                $('#req-special').removeClass('requirement-met').addClass('requirement-unmet');
            }
        } else {
            $('#passwordRequirements').hide();
        }
    });
    
    // Avatar upload
    $('#uploadAvatarBtn').click(function() {
        $('#avatarInput').click();
    });
    
    $('#avatarInput').change(function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validate file
        if (!file.type.match(/^image\/(jpeg|jpg|png)$/)) {
            toastr.error('Please select a JPG or PNG image.');
            return;
        }
        
        if (file.size > 2097152) { // 2MB
            toastr.error('File size must be less than 2MB.');
            return;
        }
        
        selectedFile = file;
        
        // Show crop modal
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#cropImage').attr('src', e.target.result);
            $('#cropModal').modal('show');
        };
        reader.readAsDataURL(file);
    });
    
    // Initialize cropper when modal is shown
    $('#cropModal').on('shown.bs.modal', function() {
        const image = document.getElementById('cropImage');
        cropper = new Cropper(image, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.8,
            restore: false,
            guides: false,
            center: false,
            highlight: false,
            cropBoxMovable: false,
            cropBoxResizable: false,
            toggleDragModeOnDblclick: false,
            preview: '#cropPreview'
        });
    });
    
    // Destroy cropper when modal is hidden
    $('#cropModal').on('hidden.bs.modal', function() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        $('#avatarInput').val('');
    });
    
    // Crop and upload
    $('#cropAndUpload').click(function() {
        if (!cropper || !selectedFile) return;
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<i class="fas fa-spinner fa-spin me-2"></i>Uploading...');
        button.prop('disabled', true);
        
        // Get crop data
        const cropData = cropper.getData();
        
        // Create form data
        const formData = new FormData();
        formData.append('avatar', selectedFile);
        formData.append('crop_data', JSON.stringify(cropData));
        formData.append('_token', '{{ csrf_token() }}');
        
        $.ajax({
            url: '{{ route("profile.avatar.upload") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Update avatar display
                    $('#currentAvatar').attr('src', response.avatar_url);
                    $('.avatar-placeholder').hide();
                    
                    // Add remove button if not exists
                    if (!$('#removeAvatarBtn').length) {
                        $('#uploadAvatarBtn').after(`
                            <button type="button" class="btn btn-remove" id="removeAvatarBtn">
                                <i class="fas fa-trash me-2"></i>Remove
                            </button>
                        `);
                    }
                    
                    $('#cropModal').modal('hide');
                } else {
                    toastr.error(response.message || 'Failed to upload avatar.');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response && response.message) {
                    toastr.error(response.message);
                } else {
                    toastr.error('Failed to upload avatar.');
                }
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
    
    // Remove avatar
    $(document).on('click', '#removeAvatarBtn', function() {
        if (!confirm('Are you sure you want to remove your profile picture?')) {
            return;
        }
        
        const button = $(this);
        const originalText = button.html();
        
        button.html('<i class="fas fa-spinner fa-spin me-2"></i>Removing...');
        button.prop('disabled', true);
        
        $.ajax({
            url: '{{ route("profile.avatar.remove") }}',
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Reset avatar display
                    $('#currentAvatar').remove();
                    $('.current-avatar').html(`
                        <div class="avatar-placeholder">
                            {{ strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1)) }}
                        </div>
                    `);
                    
                    button.remove();
                } else {
                    toastr.error(response.message || 'Failed to remove avatar.');
                }
            },
            error: function() {
                toastr.error('Failed to remove avatar.');
            },
            complete: function() {
                button.html(originalText);
                button.prop('disabled', false);
            }
        });
    });
    
    // Form submission
    $('#profileForm').submit(function(e) {
        // Check if password fields are filled
        const currentPassword = $('#current_password').val();
        const newPassword = $('#password').val();
        const confirmPassword = $('#password_confirmation').val();
        
        if (newPassword || confirmPassword) {
            if (!currentPassword) {
                e.preventDefault();
                toastr.error('Current password is required to change password.');
                $('#current_password').focus();
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                toastr.error('New password and confirmation do not match.');
                $('#password_confirmation').focus();
                return false;
            }
        }
    });
});
</script>
@endpush