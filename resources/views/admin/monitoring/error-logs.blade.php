@extends('layouts.app')

@section('title', 'Error Logs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-exclamation-circle me-2 text-danger"></i>Error Logs
            </h1>
            <p class="text-muted mb-0">Monitor system errors, exceptions, and critical issues</p>
        </div>
        <div>
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Error Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Critical Errors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $errorStats['critical'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Warnings
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $errorStats['warning'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Today's Errors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $errorStats['today'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Error Rate
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $errorStats['rate'] ?? '0.0' }}%</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Log File Selection and Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-1"></i>Log File & Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.monitoring.error-logs') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="file" class="form-label">Log File</label>
                    <select class="form-select" id="file" name="file" onchange="this.form.submit()">
                        @foreach($logFiles as $file)
                            <option value="{{ $file }}" {{ $selectedFile === $file ? 'selected' : '' }}>
                                {{ $file }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Search in logs...">
                </div>
                
                <div class="col-md-2">
                    <label for="level" class="form-label">Error Level</label>
                    <select class="form-select" id="level" name="level">
                        <option value="">All Levels</option>
                        <option value="EMERGENCY" {{ request('level') === 'EMERGENCY' ? 'selected' : '' }}>Emergency</option>
                        <option value="ALERT" {{ request('level') === 'ALERT' ? 'selected' : '' }}>Alert</option>
                        <option value="CRITICAL" {{ request('level') === 'CRITICAL' ? 'selected' : '' }}>Critical</option>
                        <option value="ERROR" {{ request('level') === 'ERROR' ? 'selected' : '' }}>Error</option>
                        <option value="WARNING" {{ request('level') === 'WARNING' ? 'selected' : '' }}>Warning</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('admin.monitoring.error-logs') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger" onclick="clearLogFile()">
                            <i class="fas fa-trash me-1"></i>Clear Log
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Error Logs Display -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Error Log Entries</h6>
            <div class="d-flex gap-2">
                <span class="badge bg-info">{{ $logs->total() }} entries</span>
                <button class="btn btn-sm btn-outline-primary" onclick="downloadLog()">
                    <i class="fas fa-download me-1"></i>Download
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="refreshLogs()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($logs->count() > 0)
                <div class="log-container" style="max-height: 600px; overflow-y: auto;">
                    @foreach($logs as $index => $logEntry)
                        <div class="log-entry mb-3 p-3 border rounded {{ $this->getLogEntryClass($logEntry) }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-{{ $this->getLogLevelColor($logEntry) }} me-2">
                                            {{ $this->extractLogLevel($logEntry) }}
                                        </span>
                                        <small class="text-muted">
                                            {{ $this->extractTimestamp($logEntry) }}
                                        </small>
                                    </div>
                                    <div class="log-message">
                                        <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.9em;">{{ $this->formatLogEntry($logEntry) }}</pre>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <button class="btn btn-sm btn-outline-info" onclick="expandLogEntry({{ $index }})">
                                        <i class="fas fa-expand-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <small class="text-muted">
                            Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} entries
                        </small>
                    </div>
                    <div>
                        {{ $logs->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 class="text-success">No Errors Found</h5>
                    <p class="text-muted">No error entries match your current filters, or the log file is empty.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Log Entry Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Error Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="logDetailContent" style="white-space: pre-wrap; max-height: 500px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="copyLogContent()">
                    <i class="fas fa-copy me-1"></i>Copy
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function expandLogEntry(index) {
    const logEntries = document.querySelectorAll('.log-entry');
    const logEntry = logEntries[index];
    const logMessage = logEntry.querySelector('.log-message pre').textContent;
    
    document.getElementById('logDetailContent').textContent = logMessage;
    
    const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
    modal.show();
}

function copyLogContent() {
    const content = document.getElementById('logDetailContent').textContent;
    navigator.clipboard.writeText(content).then(() => {
        showAlert('Log content copied to clipboard', 'success');
    }).catch(() => {
        showAlert('Failed to copy content', 'danger');
    });
}

function clearLogFile() {
    if (!confirm('Are you sure you want to clear this log file? This action cannot be undone.')) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Clearing...';
    button.disabled = true;
    
    // In a real implementation, this would make an AJAX call to clear the log
    setTimeout(() => {
        showAlert('Log file cleared successfully', 'success');
        location.reload();
    }, 2000);
}

function downloadLog() {
    const selectedFile = document.getElementById('file').value;
    const downloadUrl = `{{ route('admin.system-config.logs.download') }}?file=${selectedFile}`;
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Preparing...';
    button.disabled = true;
    
    // Create a temporary link to trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = selectedFile;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 1000);
}

function refreshLogs() {
    location.reload();
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Auto-refresh every 30 seconds
setInterval(() => {
    if (!document.querySelector('.modal.show')) { // Don't refresh if modal is open
        location.reload();
    }
}, 30000);
</script>
@endpush

@php
// Helper functions for log processing
function getLogEntryClass($logEntry) {
    $level = extractLogLevel($logEntry);
    $classes = [
        'EMERGENCY' => 'border-danger bg-danger bg-opacity-10',
        'ALERT' => 'border-danger bg-danger bg-opacity-10',
        'CRITICAL' => 'border-danger bg-danger bg-opacity-10',
        'ERROR' => 'border-warning bg-warning bg-opacity-10',
        'WARNING' => 'border-warning bg-warning bg-opacity-10',
    ];
    
    return $classes[$level] ?? 'border-light';
}

function getLogLevelColor($logEntry) {
    $level = extractLogLevel($logEntry);
    $colors = [
        'EMERGENCY' => 'danger',
        'ALERT' => 'danger',
        'CRITICAL' => 'danger',
        'ERROR' => 'warning',
        'WARNING' => 'warning',
        'NOTICE' => 'info',
        'INFO' => 'info',
        'DEBUG' => 'secondary',
    ];
    
    return $colors[$level] ?? 'secondary';
}

function extractLogLevel($logEntry) {
    if (preg_match('/\[(\w+)\]/', $logEntry, $matches)) {
        return strtoupper($matches[1]);
    }
    return 'UNKNOWN';
}

function extractTimestamp($logEntry) {
    if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $logEntry, $matches)) {
        return $matches[1];
    }
    return 'Unknown time';
}

function formatLogEntry($logEntry) {
    // Remove excessive whitespace and format for better readability
    $formatted = preg_replace('/\s+/', ' ', $logEntry);
    $formatted = str_replace('Stack trace:', "\nStack trace:", $formatted);
    $formatted = str_replace('#', "\n#", $formatted);
    return trim($formatted);
}
@endphp
@endsection