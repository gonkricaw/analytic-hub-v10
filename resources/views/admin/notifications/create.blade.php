@extends('layouts.admin')

@section('title', 'Create Notification')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-plus"></i> Create Notification
            </h1>
            <p class="mb-0 text-muted">Create a new system notification or announcement</p>
        </div>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Notifications
        </a>
    </div>

    <form action="{{ route('admin.notifications.store') }}" method="POST" id="notificationForm">
        @csrf
        
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                       id="title" name="title" value="{{ old('title') }}" 
                                       placeholder="Enter notification title" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="system" {{ old('type') == 'system' ? 'selected' : '' }}>System</option>
                                    <option value="announcement" {{ old('type') == 'announcement' ? 'selected' : '' }}>Announcement</option>
                                    <option value="alert" {{ old('type') == 'alert' ? 'selected' : '' }}>Alert</option>
                                    <option value="info" {{ old('type') == 'info' ? 'selected' : '' }}>Information</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }} selected>Normal</option>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" class="form-control @error('category') is-invalid @enderror" 
                                       id="category" name="category" value="{{ old('category') }}" 
                                       placeholder="Optional category">
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('message') is-invalid @enderror" 
                                      id="message" name="message" rows="6" 
                                      placeholder="Enter notification message" required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">HTML tags are allowed for rich formatting.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="action_url" class="form-label">Action URL</label>
                                <input type="url" class="form-control @error('action_url') is-invalid @enderror" 
                                       id="action_url" name="action_url" value="{{ old('action_url') }}" 
                                       placeholder="https://example.com/action">
                                @error('action_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Optional URL for notification action button.</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="action_text" class="form-label">Action Text</label>
                                <input type="text" class="form-control @error('action_text') is-invalid @enderror" 
                                       id="action_text" name="action_text" value="{{ old('action_text') }}" 
                                       placeholder="View Details">
                                @error('action_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Text for the action button (if URL provided).</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Targeting -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-users"></i> Targeting
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="target_type" class="form-label">Target Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('target_type') is-invalid @enderror" 
                                    id="target_type" name="target_type" required onchange="toggleTargetOptions()">
                                <option value="">Select Target Type</option>
                                <option value="all_users" {{ old('target_type') == 'all_users' ? 'selected' : '' }}>All Users</option>
                                <option value="specific_users" {{ old('target_type') == 'specific_users' ? 'selected' : '' }}>Specific Users</option>
                                <option value="role_based" {{ old('target_type') == 'role_based' ? 'selected' : '' }}>Role Based</option>
                                <option value="active_users" {{ old('target_type') == 'active_users' ? 'selected' : '' }}>Active Users Only</option>
                                <option value="inactive_users" {{ old('target_type') == 'inactive_users' ? 'selected' : '' }}>Inactive Users Only</option>
                            </select>
                            @error('target_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Specific Users Selection -->
                        <div id="specific_users_section" class="mb-3" style="display: none;">
                            <label for="target_users" class="form-label">Select Users</label>
                            <select class="form-select @error('target_users') is-invalid @enderror" 
                                    id="target_users" name="target_users[]" multiple>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" 
                                            {{ in_array($user->id, old('target_users', [])) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('target_users')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Role Based Selection -->
                        <div id="role_based_section" class="mb-3" style="display: none;">
                            <label for="target_roles" class="form-label">Select Roles</label>
                            <select class="form-select @error('target_roles') is-invalid @enderror" 
                                    id="target_roles" name="target_roles[]" multiple>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" 
                                            {{ in_array($role->id, old('target_roles', [])) ? 'selected' : '' }}>
                                        {{ $role->name }} ({{ $role->users_count ?? 0 }} users)
                                    </option>
                                @endforeach
                            </select>
                            @error('target_roles')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Scheduling -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-clock"></i> Scheduling
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="schedule_notification" 
                                       name="schedule_notification" value="1" 
                                       {{ old('schedule_notification') ? 'checked' : '' }}
                                       onchange="toggleScheduling()">
                                <label class="form-check-label" for="schedule_notification">
                                    Schedule for later
                                </label>
                            </div>
                        </div>
                        
                        <div id="scheduling_options" style="display: none;">
                            <div class="mb-3">
                                <label for="scheduled_at" class="form-label">Schedule Date & Time</label>
                                <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                       id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}">
                                @error('scheduled_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Expiry -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-hourglass-end"></i> Expiry
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="set_expiry" 
                                       name="set_expiry" value="1" 
                                       {{ old('set_expiry') ? 'checked' : '' }}
                                       onchange="toggleExpiry()">
                                <label class="form-check-label" for="set_expiry">
                                    Set expiry date
                                </label>
                            </div>
                        </div>
                        
                        <div id="expiry_options" style="display: none;">
                            <div class="mb-3">
                                <label for="expires_at" class="form-label">Expiry Date & Time</label>
                                <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror" 
                                       id="expires_at" name="expires_at" value="{{ old('expires_at') }}">
                                @error('expires_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-cog"></i> Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="save_draft" class="btn btn-secondary">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <button type="submit" name="action" value="send_now" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i> Send Now
                            </button>
                            <button type="submit" name="action" value="schedule" class="btn btn-info" id="schedule_btn" style="display: none;">
                                <i class="fas fa-clock"></i> Schedule
                            </button>
                            <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Drafts can be edited later. Scheduled notifications will be sent automatically.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for multi-select dropdowns
    $('#target_users').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select users...',
        allowClear: true
    });
    
    $('#target_roles').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select roles...',
        allowClear: true
    });
    
    // Initialize based on old values
    toggleTargetOptions();
    toggleScheduling();
    toggleExpiry();
    
    // Form validation
    $('#notificationForm').on('submit', function(e) {
        const action = $(document.activeElement).val();
        
        if (action === 'schedule' && !$('#scheduled_at').val()) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please set a schedule date and time.'
            });
            return false;
        }
        
        if ($('#set_expiry').is(':checked') && $('#expires_at').val()) {
            const expiryDate = new Date($('#expires_at').val());
            const now = new Date();
            
            if (expiryDate <= now) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Expiry date must be in the future.'
                });
                return false;
            }
        }
        
        if ($('#schedule_notification').is(':checked') && $('#scheduled_at').val()) {
            const scheduleDate = new Date($('#scheduled_at').val());
            const now = new Date();
            
            if (scheduleDate <= now) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Schedule date must be in the future.'
                });
                return false;
            }
        }
    });
});

function toggleTargetOptions() {
    const targetType = $('#target_type').val();
    
    // Hide all sections first
    $('#specific_users_section').hide();
    $('#role_based_section').hide();
    
    // Show relevant section
    if (targetType === 'specific_users') {
        $('#specific_users_section').show();
    } else if (targetType === 'role_based') {
        $('#role_based_section').show();
    }
}

function toggleScheduling() {
    const isChecked = $('#schedule_notification').is(':checked');
    
    if (isChecked) {
        $('#scheduling_options').show();
        $('#schedule_btn').show();
        
        // Set minimum date to current time
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        $('#scheduled_at').attr('min', now.toISOString().slice(0, 16));
    } else {
        $('#scheduling_options').hide();
        $('#schedule_btn').hide();
        $('#scheduled_at').val('');
    }
}

function toggleExpiry() {
    const isChecked = $('#set_expiry').is(':checked');
    
    if (isChecked) {
        $('#expiry_options').show();
        
        // Set minimum date to current time
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        $('#expires_at').attr('min', now.toISOString().slice(0, 16));
    } else {
        $('#expiry_options').hide();
        $('#expires_at').val('');
    }
}
</script>
@endpush