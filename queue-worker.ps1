<#
.SYNOPSIS
    Analytics Hub Queue Worker Management Script for Windows

.DESCRIPTION
    This PowerShell script manages the Laravel queue worker for the Analytics Hub application.
    It provides functions to start, stop, restart, and monitor the queue worker process.

.PARAMETER Action
    The action to perform: start, stop, restart, status, or monitor

.EXAMPLE
    .\queue-worker.ps1 -Action start
    .\queue-worker.ps1 -Action status
    .\queue-worker.ps1 -Action monitor

.NOTES
    Author: Analytics Hub Team
    Version: 1.0.0
    Requires: PowerShell 5.0+, PHP, Laravel Application
#>

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("start", "stop", "restart", "status", "monitor")]
    [string]$Action
)

# Configuration - Update these paths as needed
$AppDir = "d:\XAMPP\htdocs\analytic-hub-v10"
$PhpPath = "d:\XAMPP\php\php.exe"
$ProcessName = "analytics-hub-queue"
$LogFile = "$AppDir\storage\logs\queue-worker.log"

# Function to write log messages
function Write-Log {
    param([string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] $Message"
    Write-Host $logMessage
    Add-Content -Path $LogFile -Value $logMessage
}

# Function to check if queue worker is running
function Test-QueueWorker {
    $processes = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -like "*queue:work*"
    }
    return $processes.Count -gt 0
}

# Function to start queue worker
function Start-QueueWorker {
    if (Test-QueueWorker) {
        Write-Log "Queue worker is already running"
        return
    }

    Write-Log "Starting Analytics Hub Queue Worker..."
    
    # Change to application directory
    Set-Location $AppDir
    
    # Start the queue worker process
    $arguments = @(
        "artisan",
        "queue:work",
        "--sleep=3",
        "--tries=3",
        "--max-time=3600",
        "--timeout=60",
        "--queue=emails"
    )
    
    Start-Process -FilePath $PhpPath -ArgumentList $arguments -WindowStyle Minimized
    
    Start-Sleep -Seconds 2
    
    if (Test-QueueWorker) {
        Write-Log "Queue worker started successfully"
    } else {
        Write-Log "Failed to start queue worker"
    }
}

# Function to stop queue worker
function Stop-QueueWorker {
    $processes = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -like "*queue:work*"
    }
    
    if ($processes.Count -eq 0) {
        Write-Log "No queue worker processes found"
        return
    }
    
    Write-Log "Stopping $($processes.Count) queue worker process(es)..."
    
    foreach ($process in $processes) {
        try {
            $process.CloseMainWindow()
            if (!$process.WaitForExit(5000)) {
                $process.Kill()
            }
            Write-Log "Stopped process ID: $($process.Id)"
        } catch {
            Write-Log "Error stopping process ID $($process.Id): $($_.Exception.Message)"
        }
    }
}

# Function to show queue worker status
function Show-QueueWorkerStatus {
    $processes = Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -like "*queue:work*"
    }
    
    if ($processes.Count -eq 0) {
        Write-Host "Queue Worker Status: STOPPED" -ForegroundColor Red
    } else {
        Write-Host "Queue Worker Status: RUNNING" -ForegroundColor Green
        Write-Host "Active Processes: $($processes.Count)"
        
        foreach ($process in $processes) {
            $startTime = $process.StartTime
            $runTime = (Get-Date) - $startTime
            Write-Host "  PID: $($process.Id), Started: $($startTime.ToString('yyyy-MM-dd HH:mm:ss')), Runtime: $($runTime.ToString('hh\:mm\:ss'))"
        }
    }
    
    # Show recent queue statistics
    Write-Host "`nRecent Queue Statistics:"
    Set-Location $AppDir
    & $PhpPath artisan email:monitor --stats
}

# Function to monitor queue worker
function Start-QueueWorkerMonitor {
    Write-Host "Starting Queue Worker Monitor (Press Ctrl+C to stop)..." -ForegroundColor Yellow
    
    while ($true) {
        Clear-Host
        Write-Host "=== Analytics Hub Queue Worker Monitor ===" -ForegroundColor Cyan
        Write-Host "Time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray
        Write-Host ""
        
        Show-QueueWorkerStatus
        
        # Auto-restart if not running
        if (!(Test-QueueWorker)) {
            Write-Host "`nQueue worker not running. Auto-restarting..." -ForegroundColor Yellow
            Start-QueueWorker
        }
        
        Start-Sleep -Seconds 30
    }
}

# Main script execution
try {
    # Ensure log directory exists
    $logDir = Split-Path $LogFile -Parent
    if (!(Test-Path $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    }
    
    # Execute requested action
    switch ($Action.ToLower()) {
        "start" {
            Start-QueueWorker
        }
        "stop" {
            Stop-QueueWorker
        }
        "restart" {
            Stop-QueueWorker
            Start-Sleep -Seconds 3
            Start-QueueWorker
        }
        "status" {
            Show-QueueWorkerStatus
        }
        "monitor" {
            Start-QueueWorkerMonitor
        }
    }
} catch {
    Write-Log "Error executing action '$Action': $($_.Exception.Message)"
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}