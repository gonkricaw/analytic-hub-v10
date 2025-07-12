<form id="generalSettingsForm">
    @csrf
    <div class="row">
        <!-- Application Information -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-info-circle text-primary"></i> Application Information
                </h5>
                
                <!-- Application Name -->
                <div class="mb-3">
                    <label for="app_name" class="form-label text-white">Application Name</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" 
                           id="app_name" name="app.name" 
                           value="{{ $configs['app.name']->value ?? 'Analytics Hub' }}"
                           onchange="SystemConfig.updateConfig('app.name', this.value, 'Updated application name')">
                    <small class="text-muted">The name displayed in the application header and title</small>
                </div>

                <!-- Application Description -->
                <div class="mb-3">
                    <label for="app_description" class="form-label text-white">Application Description</label>
                    <textarea class="form-control bg-dark text-white border-secondary" 
                              id="app_description" name="app.description" rows="3"
                              onchange="SystemConfig.updateConfig('app.description', this.value, 'Updated application description')">{{ $configs['app.description']->value ?? 'Advanced Analytics and Business Intelligence Platform' }}</textarea>
                    <small class="text-muted">Brief description of the application</small>
                </div>

                <!-- Application Version -->
                <div class="mb-3">
                    <label for="app_version" class="form-label text-white">Application Version</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" 
                           id="app_version" name="app.version" 
                           value="{{ $configs['app.version']->value ?? '1.0.0' }}"
                           onchange="SystemConfig.updateConfig('app.version', this.value, 'Updated application version')">
                    <small class="text-muted">Current version of the application</small>
                </div>

                <!-- Application URL -->
                <div class="mb-3">
                    <label for="app_url" class="form-label text-white">Application URL</label>
                    <input type="url" class="form-control bg-dark text-white border-secondary" 
                           id="app_url" name="app.url" 
                           value="{{ $configs['app.url']->value ?? config('app.url') }}"
                           onchange="SystemConfig.updateConfig('app.url', this.value, 'Updated application URL')">
                    <small class="text-muted">Base URL of the application</small>
                </div>
            </div>
        </div>

        <!-- System Preferences -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-cogs text-success"></i> System Preferences
                </h5>
                
                <!-- Timezone -->
                <div class="mb-3">
                    <label for="app_timezone" class="form-label text-white">Default Timezone</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="app_timezone" name="app.timezone"
                            onchange="SystemConfig.updateConfig('app.timezone', this.value, 'Updated application timezone')">
                        @php
                            $currentTimezone = $configs['app.timezone']->value ?? config('app.timezone', 'UTC');
                            $timezones = [
                                'UTC' => 'UTC',
                                'America/New_York' => 'Eastern Time (US & Canada)',
                                'America/Chicago' => 'Central Time (US & Canada)',
                                'America/Denver' => 'Mountain Time (US & Canada)',
                                'America/Los_Angeles' => 'Pacific Time (US & Canada)',
                                'Europe/London' => 'London',
                                'Europe/Paris' => 'Paris',
                                'Europe/Berlin' => 'Berlin',
                                'Asia/Tokyo' => 'Tokyo',
                                'Asia/Shanghai' => 'Shanghai',
                                'Asia/Kolkata' => 'Kolkata',
                                'Australia/Sydney' => 'Sydney',
                            ];
                        @endphp
                        @foreach($timezones as $value => $label)
                            <option value="{{ $value }}" {{ $currentTimezone === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Default timezone for the application</small>
                </div>

                <!-- Date Format -->
                <div class="mb-3">
                    <label for="app_date_format" class="form-label text-white">Date Format</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="app_date_format" name="app.date_format"
                            onchange="SystemConfig.updateConfig('app.date_format', this.value, 'Updated date format')">
                        @php
                            $currentDateFormat = $configs['app.date_format']->value ?? 'Y-m-d';
                            $dateFormats = [
                                'Y-m-d' => date('Y-m-d') . ' (YYYY-MM-DD)',
                                'm/d/Y' => date('m/d/Y') . ' (MM/DD/YYYY)',
                                'd/m/Y' => date('d/m/Y') . ' (DD/MM/YYYY)',
                                'M d, Y' => date('M d, Y') . ' (Mon DD, YYYY)',
                                'F j, Y' => date('F j, Y') . ' (Month DD, YYYY)',
                            ];
                        @endphp
                        @foreach($dateFormats as $value => $label)
                            <option value="{{ $value }}" {{ $currentDateFormat === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Default date display format</small>
                </div>

                <!-- Time Format -->
                <div class="mb-3">
                    <label for="app_time_format" class="form-label text-white">Time Format</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="app_time_format" name="app.time_format"
                            onchange="SystemConfig.updateConfig('app.time_format', this.value, 'Updated time format')">
                        @php
                            $currentTimeFormat = $configs['app.time_format']->value ?? 'H:i:s';
                            $timeFormats = [
                                'H:i:s' => date('H:i:s') . ' (24-hour)',
                                'h:i:s A' => date('h:i:s A') . ' (12-hour with AM/PM)',
                                'H:i' => date('H:i') . ' (24-hour without seconds)',
                                'h:i A' => date('h:i A') . ' (12-hour without seconds)',
                            ];
                        @endphp
                        @foreach($timeFormats as $value => $label)
                            <option value="{{ $value }}" {{ $currentTimeFormat === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Default time display format</small>
                </div>

                <!-- Default Language -->
                <div class="mb-3">
                    <label for="app_locale" class="form-label text-white">Default Language</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="app_locale" name="app.locale"
                            onchange="SystemConfig.updateConfig('app.locale', this.value, 'Updated application locale')">
                        @php
                            $currentLocale = $configs['app.locale']->value ?? config('app.locale', 'en');
                            $locales = [
                                'en' => 'English',
                                'es' => 'Español',
                                'fr' => 'Français',
                                'de' => 'Deutsch',
                                'it' => 'Italiano',
                                'pt' => 'Português',
                                'zh' => '中文',
                                'ja' => '日本語',
                                'ko' => '한국어',
                                'ar' => 'العربية',
                            ];
                        @endphp
                        @foreach($locales as $value => $label)
                            <option value="{{ $value }}" {{ $currentLocale === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Default language for the application</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Performance Settings -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-tachometer-alt text-warning"></i> Performance Settings
                </h5>
                
                <!-- Cache Duration -->
                <div class="mb-3">
                    <label for="cache_duration" class="form-label text-white">Default Cache Duration (minutes)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="cache_duration" name="cache.duration" min="1" max="1440"
                           value="{{ $configs['cache.duration']->value ?? 60 }}"
                           onchange="SystemConfig.updateConfig('cache.duration', this.value, 'Updated cache duration')">
                    <small class="text-muted">How long to cache data (1-1440 minutes)</small>
                </div>

                <!-- Session Timeout -->
                <div class="mb-3">
                    <label for="session_timeout" class="form-label text-white">Session Timeout (minutes)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="session_timeout" name="session.timeout" min="5" max="480"
                           value="{{ $configs['session.timeout']->value ?? 120 }}"
                           onchange="SystemConfig.updateConfig('session.timeout', this.value, 'Updated session timeout')">
                    <small class="text-muted">User session timeout duration (5-480 minutes)</small>
                </div>

                <!-- Max Upload Size -->
                <div class="mb-3">
                    <label for="max_upload_size" class="form-label text-white">Max Upload Size (MB)</label>
                    <input type="number" class="form-control bg-dark text-white border-secondary" 
                           id="max_upload_size" name="upload.max_size" min="1" max="100"
                           value="{{ $configs['upload.max_size']->value ?? 10 }}"
                           onchange="SystemConfig.updateConfig('upload.max_size', this.value, 'Updated max upload size')">
                    <small class="text-muted">Maximum file upload size (1-100 MB)</small>
                </div>

                <!-- Records Per Page -->
                <div class="mb-3">
                    <label for="records_per_page" class="form-label text-white">Records Per Page</label>
                    <select class="form-select bg-dark text-white border-secondary" 
                            id="records_per_page" name="pagination.per_page"
                            onchange="SystemConfig.updateConfig('pagination.per_page', this.value, 'Updated pagination size')">
                        @php
                            $currentPerPage = $configs['pagination.per_page']->value ?? 25;
                            $perPageOptions = [10, 25, 50, 100];
                        @endphp
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}" {{ $currentPerPage == $option ? 'selected' : '' }}>
                                {{ $option }} records
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Default number of records per page</small>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-address-book text-info"></i> Contact Information
                </h5>
                
                <!-- Support Email -->
                <div class="mb-3">
                    <label for="support_email" class="form-label text-white">Support Email</label>
                    <input type="email" class="form-control bg-dark text-white border-secondary" 
                           id="support_email" name="contact.support_email" 
                           value="{{ $configs['contact.support_email']->value ?? 'support@example.com' }}"
                           onchange="SystemConfig.updateConfig('contact.support_email', this.value, 'Updated support email')">
                    <small class="text-muted">Email address for user support</small>
                </div>

                <!-- Admin Email -->
                <div class="mb-3">
                    <label for="admin_email" class="form-label text-white">Admin Email</label>
                    <input type="email" class="form-control bg-dark text-white border-secondary" 
                           id="admin_email" name="contact.admin_email" 
                           value="{{ $configs['contact.admin_email']->value ?? 'admin@example.com' }}"
                           onchange="SystemConfig.updateConfig('contact.admin_email', this.value, 'Updated admin email')">
                    <small class="text-muted">Email address for system administrators</small>
                </div>

                <!-- Company Name -->
                <div class="mb-3">
                    <label for="company_name" class="form-label text-white">Company Name</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" 
                           id="company_name" name="contact.company_name" 
                           value="{{ $configs['contact.company_name']->value ?? 'Your Company' }}"
                           onchange="SystemConfig.updateConfig('contact.company_name', this.value, 'Updated company name')">
                    <small class="text-muted">Name of your organization</small>
                </div>

                <!-- Company Address -->
                <div class="mb-3">
                    <label for="company_address" class="form-label text-white">Company Address</label>
                    <textarea class="form-control bg-dark text-white border-secondary" 
                              id="company_address" name="contact.company_address" rows="3"
                              onchange="SystemConfig.updateConfig('contact.company_address', this.value, 'Updated company address')">{{ $configs['contact.company_address']->value ?? '' }}</textarea>
                    <small class="text-muted">Physical address of your organization</small>
                </div>

                <!-- Phone Number -->
                <div class="mb-3">
                    <label for="company_phone" class="form-label text-white">Phone Number</label>
                    <input type="tel" class="form-control bg-dark text-white border-secondary" 
                           id="company_phone" name="contact.company_phone" 
                           value="{{ $configs['contact.company_phone']->value ?? '' }}"
                           onchange="SystemConfig.updateConfig('contact.company_phone', this.value, 'Updated company phone')">
                    <small class="text-muted">Contact phone number</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Debug and Development -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-bug text-danger"></i> Debug and Development
                </h5>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="debug_mode" 
                                   {{ ($configs['app.debug']->value ?? false) ? 'checked' : '' }}
                                   onchange="SystemConfig.updateConfig('app.debug', this.checked, 'Toggled debug mode')">
                            <label class="form-check-label text-white" for="debug_mode">
                                Debug Mode
                            </label>
                            <small class="d-block text-muted">Enable detailed error reporting</small>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="log_queries" 
                                   {{ ($configs['database.log_queries']->value ?? false) ? 'checked' : '' }}
                                   onchange="SystemConfig.updateConfig('database.log_queries', this.checked, 'Toggled query logging')">
                            <label class="form-check-label text-white" for="log_queries">
                                Log Database Queries
                            </label>
                            <small class="d-block text-muted">Log all database queries for debugging</small>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="cache_enabled" 
                                   {{ ($configs['cache.enabled']->value ?? true) ? 'checked' : '' }}
                                   onchange="SystemConfig.updateConfig('cache.enabled', this.checked, 'Toggled caching')">
                            <label class="form-check-label text-white" for="cache_enabled">
                                Enable Caching
                            </label>
                            <small class="d-block text-muted">Enable application-wide caching</small>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="api_enabled" 
                                   {{ ($configs['api.enabled']->value ?? true) ? 'checked' : '' }}
                                   onchange="SystemConfig.updateConfig('api.enabled', this.checked, 'Toggled API access')">
                            <label class="form-check-label text-white" for="api_enabled">
                                Enable API
                            </label>
                            <small class="d-block text-muted">Enable REST API endpoints</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Auto-save functionality for general settings
document.addEventListener('DOMContentLoaded', function() {
    // Add change listeners to all form elements
    const form = document.getElementById('generalSettingsForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        if (!input.hasAttribute('onchange')) {
            input.addEventListener('change', function() {
                const key = this.name;
                const value = this.type === 'checkbox' ? this.checked : this.value;
                SystemConfig.updateConfig(key, value, `Updated ${key}`);
            });
        }
    });
});
</script>