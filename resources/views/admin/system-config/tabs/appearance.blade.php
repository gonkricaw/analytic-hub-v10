<form id="appearanceSettingsForm">
    @csrf
    <div class="row">
        <!-- Logo Management -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-image text-primary"></i> Logo Management
                </h5>
                
                <!-- Main Logo -->
                <div class="mb-4">
                    <label class="form-label text-white">Main Logo</label>
                    <div class="file-upload-area" onclick="document.getElementById('main_logo').click()">
                        <div class="text-center">
                            @if(isset($configs['appearance.logo.main']) && $configs['appearance.logo.main']->value)
                                <img src="{{ $configs['appearance.logo.main']->value }}" 
                                     alt="Main Logo" class="preview-image mb-2" 
                                     id="preview-appearance-logo-main">
                                <br>
                            @else
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                <br>
                            @endif
                            <span class="text-white">Click to upload main logo</span>
                            <br>
                            <small class="text-muted">Recommended: 200x60px, PNG/JPG, max 2MB</small>
                        </div>
                    </div>
                    <input type="file" id="main_logo" name="main_logo" class="d-none" 
                           accept="image/*" onchange="SystemConfig.uploadFile(this, 'logo', 'appearance.logo.main')">
                </div>

                <!-- Small Logo -->
                <div class="mb-4">
                    <label class="form-label text-white">Small Logo (Favicon)</label>
                    <div class="file-upload-area" onclick="document.getElementById('small_logo').click()">
                        <div class="text-center">
                            @if(isset($configs['appearance.logo.small']) && $configs['appearance.logo.small']->value)
                                <img src="{{ $configs['appearance.logo.small']->value }}" 
                                     alt="Small Logo" class="preview-image mb-2" 
                                     id="preview-appearance-logo-small">
                                <br>
                            @else
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <br>
                            @endif
                            <span class="text-white">Click to upload small logo</span>
                            <br>
                            <small class="text-muted">Recommended: 32x32px, PNG/ICO, max 1MB</small>
                        </div>
                    </div>
                    <input type="file" id="small_logo" name="small_logo" class="d-none" 
                           accept="image/*" onchange="SystemConfig.uploadFile(this, 'logo', 'appearance.logo.small')">
                </div>

                <!-- Logo Alt Text -->
                <div class="mb-3">
                    <label for="logo_alt" class="form-label text-white">Logo Alt Text</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" 
                           id="logo_alt" name="appearance.logo.alt" 
                           value="{{ $configs['appearance.logo.alt']->value ?? 'Analytics Hub Logo' }}"
                           onchange="SystemConfig.updateConfig('appearance.logo.alt', this.value, 'Updated logo alt text')">
                    <small class="text-muted">Alternative text for accessibility</small>
                </div>
            </div>
        </div>

        <!-- Background Management -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-palette text-success"></i> Background Management
                </h5>
                
                <!-- Login Background -->
                <div class="mb-4">
                    <label class="form-label text-white">Login Page Background</label>
                    <div class="file-upload-area" onclick="document.getElementById('login_background').click()">
                        <div class="text-center">
                            @if(isset($configs['appearance.background.login']) && $configs['appearance.background.login']->value)
                                <img src="{{ $configs['appearance.background.login']->value }}" 
                                     alt="Login Background" class="preview-image mb-2" 
                                     id="preview-appearance-background-login">
                                <br>
                            @else
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                <br>
                            @endif
                            <span class="text-white">Click to upload login background</span>
                            <br>
                            <small class="text-muted">Recommended: 1920x1080px, JPG/PNG, max 5MB</small>
                        </div>
                    </div>
                    <input type="file" id="login_background" name="login_background" class="d-none" 
                           accept="image/*" onchange="SystemConfig.uploadFile(this, 'background', 'appearance.background.login')">
                </div>

                <!-- Dashboard Background -->
                <div class="mb-4">
                    <label class="form-label text-white">Dashboard Background</label>
                    <div class="file-upload-area" onclick="document.getElementById('dashboard_background').click()">
                        <div class="text-center">
                            @if(isset($configs['appearance.background.dashboard']) && $configs['appearance.background.dashboard']->value)
                                <img src="{{ $configs['appearance.background.dashboard']->value }}" 
                                     alt="Dashboard Background" class="preview-image mb-2" 
                                     id="preview-appearance-background-dashboard">
                                <br>
                            @else
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                <br>
                            @endif
                            <span class="text-white">Click to upload dashboard background</span>
                            <br>
                            <small class="text-muted">Recommended: 1920x1080px, JPG/PNG, max 5MB</small>
                        </div>
                    </div>
                    <input type="file" id="dashboard_background" name="dashboard_background" class="d-none" 
                           accept="image/*" onchange="SystemConfig.uploadFile(this, 'background', 'appearance.background.dashboard')">
                </div>

                <!-- Background Opacity -->
                <div class="mb-3">
                    <label for="background_opacity" class="form-label text-white">Background Opacity</label>
                    <input type="range" class="form-range" id="background_opacity" 
                           name="appearance.background.opacity" min="0" max="100" step="5"
                           value="{{ $configs['appearance.background.opacity']->value ?? 80 }}"
                           onchange="updateOpacityValue(this.value); SystemConfig.updateConfig('appearance.background.opacity', this.value, 'Updated background opacity')">
                    <div class="d-flex justify-content-between text-muted small">
                        <span>0% (Transparent)</span>
                        <span id="opacity-value">{{ $configs['appearance.background.opacity']->value ?? 80 }}%</span>
                        <span>100% (Opaque)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Theme Settings -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-paint-brush text-warning"></i> Theme Settings
                </h5>
                
                <!-- Primary Color -->
                <div class="mb-3">
                    <label for="primary_color" class="form-label text-white">Primary Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color bg-dark border-secondary" 
                               id="primary_color" name="appearance.theme.primary_color" 
                               value="{{ $configs['appearance.theme.primary_color']->value ?? '#FF7A00' }}"
                               onchange="updateColorPreview('primary', this.value); SystemConfig.updateConfig('appearance.theme.primary_color', this.value, 'Updated primary color')">
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               value="{{ $configs['appearance.theme.primary_color']->value ?? '#FF7A00' }}" 
                               id="primary_color_text" readonly>
                    </div>
                    <small class="text-muted">Main brand color used throughout the application</small>
                </div>

                <!-- Secondary Color -->
                <div class="mb-3">
                    <label for="secondary_color" class="form-label text-white">Secondary Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color bg-dark border-secondary" 
                               id="secondary_color" name="appearance.theme.secondary_color" 
                               value="{{ $configs['appearance.theme.secondary_color']->value ?? '#6C757D' }}"
                               onchange="updateColorPreview('secondary', this.value); SystemConfig.updateConfig('appearance.theme.secondary_color', this.value, 'Updated secondary color')">
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               value="{{ $configs['appearance.theme.secondary_color']->value ?? '#6C757D' }}" 
                               id="secondary_color_text" readonly>
                    </div>
                    <small class="text-muted">Secondary color for accents and highlights</small>
                </div>

                <!-- Success Color -->
                <div class="mb-3">
                    <label for="success_color" class="form-label text-white">Success Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color bg-dark border-secondary" 
                               id="success_color" name="appearance.theme.success_color" 
                               value="{{ $configs['appearance.theme.success_color']->value ?? '#28A745' }}"
                               onchange="updateColorPreview('success', this.value); SystemConfig.updateConfig('appearance.theme.success_color', this.value, 'Updated success color')">
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               value="{{ $configs['appearance.theme.success_color']->value ?? '#28A745' }}" 
                               id="success_color_text" readonly>
                    </div>
                    <small class="text-muted">Color for success messages and positive actions</small>
                </div>

                <!-- Warning Color -->
                <div class="mb-3">
                    <label for="warning_color" class="form-label text-white">Warning Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color bg-dark border-secondary" 
                               id="warning_color" name="appearance.theme.warning_color" 
                               value="{{ $configs['appearance.theme.warning_color']->value ?? '#FFC107' }}"
                               onchange="updateColorPreview('warning', this.value); SystemConfig.updateConfig('appearance.theme.warning_color', this.value, 'Updated warning color')">
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               value="{{ $configs['appearance.theme.warning_color']->value ?? '#FFC107' }}" 
                               id="warning_color_text" readonly>
                    </div>
                    <small class="text-muted">Color for warning messages and caution actions</small>
                </div>

                <!-- Danger Color -->
                <div class="mb-3">
                    <label for="danger_color" class="form-label text-white">Danger Color</label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color bg-dark border-secondary" 
                               id="danger_color" name="appearance.theme.danger_color" 
                               value="{{ $configs['appearance.theme.danger_color']->value ?? '#DC3545' }}"
                               onchange="updateColorPreview('danger', this.value); SystemConfig.updateConfig('appearance.theme.danger_color', this.value, 'Updated danger color')">
                        <input type="text" class="form-control bg-dark text-white border-secondary" 
                               value="{{ $configs['appearance.theme.danger_color']->value ?? '#DC3545' }}" 
                               id="danger_color_text" readonly>
                    </div>
                    <small class="text-muted">Color for error messages and destructive actions</small>
                </div>
            </div>
        </div>

        <!-- Content Settings -->
        <div class="col-md-6">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-align-left text-info"></i> Content Settings
                </h5>
                
                <!-- Marquee Text -->
                <div class="mb-3">
                    <label for="marquee_text" class="form-label text-white">Marquee Text</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" 
                           id="marquee_text" name="appearance.content.marquee_text" 
                           value="{{ $configs['appearance.content.marquee_text']->value ?? 'Welcome to Analytics Hub - Your Business Intelligence Platform' }}"
                           onchange="SystemConfig.updateConfig('appearance.content.marquee_text', this.value, 'Updated marquee text')">
                    <small class="text-muted">Scrolling text displayed on the homepage</small>
                </div>

                <!-- Enable Marquee -->
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="marquee_enabled" 
                               {{ ($configs['appearance.content.marquee_enabled']->value ?? true) ? 'checked' : '' }}
                               onchange="SystemConfig.updateConfig('appearance.content.marquee_enabled', this.checked, 'Toggled marquee display')">
                        <label class="form-check-label text-white" for="marquee_enabled">
                            Enable Marquee Text
                        </label>
                        <small class="d-block text-muted">Show/hide the marquee text on homepage</small>
                    </div>
                </div>

                <!-- Footer Content -->
                <div class="mb-3">
                    <label for="footer_content" class="form-label text-white">Footer Content</label>
                    <textarea class="form-control bg-dark text-white border-secondary" 
                              id="footer_content" name="appearance.content.footer_content" rows="4"
                              onchange="SystemConfig.updateConfig('appearance.content.footer_content', this.value, 'Updated footer content')">{{ $configs['appearance.content.footer_content']->value ?? '© 2024 Analytics Hub. All rights reserved.' }}</textarea>
                    <small class="text-muted">HTML content for the application footer</small>
                </div>

                <!-- Copyright Text -->
                <div class="mb-3">
                    <label for="copyright_text" class="form-label text-white">Copyright Text</label>
                    <input type="text" class="form-control bg-dark text-white border-secondary" 
                           id="copyright_text" name="appearance.content.copyright_text" 
                           value="{{ $configs['appearance.content.copyright_text']->value ?? '© 2024 Your Company Name' }}"
                           onchange="SystemConfig.updateConfig('appearance.content.copyright_text', this.value, 'Updated copyright text')">
                    <small class="text-muted">Copyright notice displayed in footer</small>
                </div>

                <!-- Custom CSS -->
                <div class="mb-3">
                    <label for="custom_css" class="form-label text-white">Custom CSS</label>
                    <textarea class="form-control bg-dark text-white border-secondary font-monospace" 
                              id="custom_css" name="appearance.custom_css" rows="6"
                              placeholder="/* Add your custom CSS here */"
                              onchange="SystemConfig.updateConfig('appearance.custom_css', this.value, 'Updated custom CSS')">{{ $configs['appearance.custom_css']->value ?? '' }}</textarea>
                    <small class="text-muted">Custom CSS styles to override default theme</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Preview -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="config-item">
                <h5 class="text-white mb-3">
                    <i class="fas fa-eye text-primary"></i> Theme Preview
                </h5>
                
                <div class="theme-preview p-4 border border-secondary rounded">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="preview-card p-3 rounded" style="background-color: var(--bs-dark);">
                                <h6 class="text-white mb-2">Primary Color</h6>
                                <div class="preview-primary p-2 rounded text-white text-center" 
                                     style="background-color: {{ $configs['appearance.theme.primary_color']->value ?? '#FF7A00' }}">
                                    Primary
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="preview-card p-3 rounded" style="background-color: var(--bs-dark);">
                                <h6 class="text-white mb-2">Success Color</h6>
                                <div class="preview-success p-2 rounded text-white text-center" 
                                     style="background-color: {{ $configs['appearance.theme.success_color']->value ?? '#28A745' }}">
                                    Success
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="preview-card p-3 rounded" style="background-color: var(--bs-dark);">
                                <h6 class="text-white mb-2">Warning Color</h6>
                                <div class="preview-warning p-2 rounded text-white text-center" 
                                     style="background-color: {{ $configs['appearance.theme.warning_color']->value ?? '#FFC107' }}">
                                    Warning
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="preview-card p-3 rounded" style="background-color: var(--bs-dark);">
                                <h6 class="text-white mb-2">Danger Color</h6>
                                <div class="preview-danger p-2 rounded text-white text-center" 
                                     style="background-color: {{ $configs['appearance.theme.danger_color']->value ?? '#DC3545' }}">
                                    Danger
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3 text-center">
                    <button type="button" class="btn btn-outline-primary" onclick="resetThemeToDefault()">
                        <i class="fas fa-undo"></i> Reset to Default Theme
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="exportTheme()">
                        <i class="fas fa-download"></i> Export Theme
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="document.getElementById('theme_import').click()">
                        <i class="fas fa-upload"></i> Import Theme
                    </button>
                    <input type="file" id="theme_import" class="d-none" accept=".json" onchange="importTheme(this)">
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Update opacity value display
function updateOpacityValue(value) {
    document.getElementById('opacity-value').textContent = value + '%';
}

// Update color preview and text input
function updateColorPreview(type, color) {
    const textInput = document.getElementById(type + '_color_text');
    const previewElement = document.querySelector('.preview-' + type);
    
    if (textInput) textInput.value = color;
    if (previewElement) previewElement.style.backgroundColor = color;
}

// Reset theme to default colors
function resetThemeToDefault() {
    if (confirm('Are you sure you want to reset all theme colors to default values?')) {
        const defaults = {
            'appearance.theme.primary_color': '#FF7A00',
            'appearance.theme.secondary_color': '#6C757D',
            'appearance.theme.success_color': '#28A745',
            'appearance.theme.warning_color': '#FFC107',
            'appearance.theme.danger_color': '#DC3545'
        };
        
        Object.entries(defaults).forEach(([key, value]) => {
            const input = document.querySelector(`input[name="${key}"]`);
            if (input) {
                input.value = value;
                updateColorPreview(key.split('.').pop().replace('_color', ''), value);
                SystemConfig.updateConfig(key, value, 'Reset to default color');
            }
        });
        
        toastr.success('Theme colors reset to default values');
    }
}

// Export current theme configuration
function exportTheme() {
    const themeConfig = {
        primary_color: document.getElementById('primary_color').value,
        secondary_color: document.getElementById('secondary_color').value,
        success_color: document.getElementById('success_color').value,
        warning_color: document.getElementById('warning_color').value,
        danger_color: document.getElementById('danger_color').value,
        background_opacity: document.getElementById('background_opacity').value,
        marquee_text: document.getElementById('marquee_text').value,
        marquee_enabled: document.getElementById('marquee_enabled').checked,
        footer_content: document.getElementById('footer_content').value,
        copyright_text: document.getElementById('copyright_text').value,
        custom_css: document.getElementById('custom_css').value
    };
    
    const dataStr = JSON.stringify(themeConfig, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'analytics-hub-theme.json';
    link.click();
    
    URL.revokeObjectURL(url);
    toastr.success('Theme configuration exported successfully');
}

// Import theme configuration
function importTheme(input) {
    const file = input.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const themeConfig = JSON.parse(e.target.result);
            
            // Apply imported configuration
            Object.entries(themeConfig).forEach(([key, value]) => {
                const element = document.getElementById(key);
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = value;
                    } else {
                        element.value = value;
                    }
                    
                    // Update color previews
                    if (key.includes('_color')) {
                        const colorType = key.replace('_color', '');
                        updateColorPreview(colorType, value);
                    }
                    
                    // Update configuration
                    const configKey = 'appearance.theme.' + key;
                    SystemConfig.updateConfig(configKey, value, 'Imported from theme file');
                }
            });
            
            toastr.success('Theme configuration imported successfully');
        } catch (error) {
            toastr.error('Invalid theme file format');
        }
    };
    
    reader.readAsText(file);
    input.value = ''; // Reset file input
}

// File upload drag and drop functionality
document.addEventListener('DOMContentLoaded', function() {
    const uploadAreas = document.querySelectorAll('.file-upload-area');
    
    uploadAreas.forEach(area => {
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = this.parentElement.querySelector('input[type="file"]');
                if (input) {
                    input.files = files;
                    input.dispatchEvent(new Event('change'));
                }
            }
        });
    });
    
    // Initialize color text inputs
    const colorInputs = document.querySelectorAll('input[type="color"]');
    colorInputs.forEach(input => {
        input.addEventListener('input', function() {
            const textInput = document.getElementById(this.id + '_text');
            if (textInput) textInput.value = this.value;
        });
    });
});
</script>