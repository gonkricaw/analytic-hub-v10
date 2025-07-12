<#
.SYNOPSIS
    Analytics Hub - Broadcast Queue Worker PowerShell Script

.DESCRIPTION
    This PowerShell script manages the broadcast queue worker for Analytics Hub.
    It provides options to start, stop, restart, and monitor the broadcast queue worker.
    
    Features:
    - Start/stop/restart broadcast worker
    - Monitor worker status
    - Automatic restart on failure
    - Logging and error handling
    - Process management

.PARAMETER Action
    The action to perform: start, stop, restart, status, or monitor

.PARAMETER Queue
    The queue name to process (default: default)

.PARAMETER Timeout
    Job timeout in seconds (default: 60)

.PARAMETER Memory
    Memory limit in MB (default: 128)

.PARAMETER Tries
    Maximum number of job attempts (default: 3)

.PARAMETER Delay
    Delay for failed jobs in seconds (default: 5)

.PARAMETER AutoRestart
    Enable automatic restart on worker failure (default: false)

.EXAMPLE
    .\broadcast-worker.ps1 -Action start
    Start the broadcast queue worker

.EXAMPLE
    .\broadcast-worker.ps1 -Action start -AutoRestart
    Start the broadcast queue worker with automatic restart

.EXAMPLE
    .\broadcast-worker.ps1 -Action status
    Check the status of the broadcast queue worker

.NOTES
    Author: Analytics Hub Team
    Version: 1.0.0
    Requires: PowerShell 5.0+, PHP, Laravel
#>

param(
    [Parameter(Mandatory=$true)]
    [ValidateSet("start", "stop", "restart", "status", "monitor")]
    [string]$Action,
    
    [string]$Queue = "default",
    [int]$Timeout = 60,
    [int]$Memory = 128,
    [int]$Tries = 3,
    [int]$Delay = 5,
    [switch]$AutoRestart
)

# Configuration
$ProcessName = "php"
$ArtisanCommand = "broadcast:start"
$LogFile = "storage\logs\broadcast-worker.log"
$PidFile = "storage\framework\broadcast-worker.pid"

# Colors for output
$Colors = @{
    Success = "Green"
    Warning = "Yellow"
    Error = "Red"
    Info = "Cyan"
    Header = "Magenta"
}

function Write-ColorOutput {
    param(
        [string]$Message,
        [string]$Color = "White"
    )
    Write-Host $Message -ForegroundColor $Colors[$Color]
}

function Write-Header {
    param([string]$Title)
    Write-Host "`n" -NoNewline
    Write-ColorOutput "========================================" "Header"
    Write-ColorOutput " $Title" "Header"
    Write-ColorOutput "========================================" "Header"
    Write-Host ""
}

function Test-Prerequisites {
    Write-ColorOutput "Checking prerequisites..." "Info"
    
    # Check if PHP is available
    try {
        $phpVersion = php --version 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-ColorOutput "✓ PHP is available" "Success"
        } else {
            throw "PHP not found"
        }
    } catch {
        Write-ColorOutput "✗ PHP is not available in PATH" "Error"
        Write-ColorOutput "Please ensure PHP is installed and added to your system PATH" "Error"
        return $false
    }
    
    # Check if artisan exists
    if (Test-Path "artisan") {
        Write-ColorOutput "✓ Laravel artisan found" "Success"
    } else {
        Write-ColorOutput "✗ artisan file not found" "Error"
        Write-ColorOutput "Please run this script from the Laravel project root directory" "Error"
        return $false
    }
    
    # Create storage directories if they don't exist
    $storageDirs = @("storage\logs", "storage\framework")
    foreach ($dir in $storageDirs) {
        if (!(Test-Path $dir)) {
            New-Item -ItemType Directory -Path $dir -Force | Out-Null
            Write-ColorOutput "✓ Created directory: $dir" "Success"
        }
    }
    
    return $true
}

function Get-WorkerProcess {
    $processes = Get-Process -Name $ProcessName -ErrorAction SilentlyContinue | Where-Object {
        $_.CommandLine -like "*$ArtisanCommand*"
    }
    return $processes
}

function Start-BroadcastWorker {
    Write-Header "Starting Broadcast Queue Worker"
    
    # Check if worker is already running
    $existingProcess = Get-WorkerProcess
    if ($existingProcess) {
        Write-ColorOutput "Broadcast worker is already running (PID: $($existingProcess.Id))" "Warning"
        return
    }
    
    # Display configuration
    Write-ColorOutput "Configuration:" "Info"
    Write-Host "  Queue: $Queue"
    Write-Host "  Timeout: $Timeout seconds"
    Write-Host "  Memory Limit: $Memory MB"
    Write-Host "  Max Tries: $Tries"
    Write-Host "  Delay on Failure: $Delay seconds"
    Write-Host "  Auto Restart: $AutoRestart"
    Write-Host ""
    
    # Build command arguments
    $arguments = @(
        "artisan",
        $ArtisanCommand,
        "--queue=$Queue",
        "--timeout=$Timeout",
        "--memory=$Memory",
        "--tries=$Tries",
        "--delay=$Delay"
    )
    
    try {
        if ($AutoRestart) {
            Write-ColorOutput "Starting worker with auto-restart enabled..." "Info"
            Start-WorkerWithAutoRestart -Arguments $arguments
        } else {
            Write-ColorOutput "Starting worker..." "Info"
            $process = Start-Process -FilePath "php" -ArgumentList $arguments -NoNewWindow -PassThru
            
            # Save PID
            $process.Id | Out-File -FilePath $PidFile -Encoding UTF8
            
            Write-ColorOutput "✓ Broadcast worker started (PID: $($process.Id))" "Success"
            Write-ColorOutput "Press Ctrl+C to stop the worker" "Info"
        }
    } catch {
        Write-ColorOutput "✗ Failed to start broadcast worker: $($_.Exception.Message)" "Error"
    }
}

function Start-WorkerWithAutoRestart {
    param([array]$Arguments)
    
    $restartCount = 0
    $maxRestarts = 10
    
    while ($restartCount -lt $maxRestarts) {
        try {
            Write-ColorOutput "Starting worker (attempt $($restartCount + 1))..." "Info"
            
            $process = Start-Process -FilePath "php" -ArgumentList $Arguments -NoNewWindow -PassThru -Wait
            
            if ($process.ExitCode -eq 0) {
                Write-ColorOutput "Worker exited normally" "Info"
                break
            } else {
                Write-ColorOutput "Worker exited with code $($process.ExitCode)" "Warning"
                $restartCount++
                
                if ($restartCount -lt $maxRestarts) {
                    $waitTime = [Math]::Min(30, 5 * $restartCount)
                    Write-ColorOutput "Restarting in $waitTime seconds..." "Info"
                    Start-Sleep -Seconds $waitTime
                }
            }
        } catch {
            Write-ColorOutput "Worker crashed: $($_.Exception.Message)" "Error"
            $restartCount++
            
            if ($restartCount -lt $maxRestarts) {
                $waitTime = [Math]::Min(30, 5 * $restartCount)
                Write-ColorOutput "Restarting in $waitTime seconds..." "Info"
                Start-Sleep -Seconds $waitTime
            }
        }
    }
    
    if ($restartCount -ge $maxRestarts) {
        Write-ColorOutput "✗ Maximum restart attempts reached. Worker stopped." "Error"
    }
}

function Stop-BroadcastWorker {
    Write-Header "Stopping Broadcast Queue Worker"
    
    $processes = Get-WorkerProcess
    
    if (!$processes) {
        Write-ColorOutput "No broadcast worker processes found" "Warning"
        return
    }
    
    foreach ($process in $processes) {
        try {
            Write-ColorOutput "Stopping worker process (PID: $($process.Id))..." "Info"
            $process | Stop-Process -Force
            Write-ColorOutput "✓ Worker process stopped" "Success"
        } catch {
            Write-ColorOutput "✗ Failed to stop worker process: $($_.Exception.Message)" "Error"
        }
    }
    
    # Clean up PID file
    if (Test-Path $PidFile) {
        Remove-Item $PidFile -Force
    }
}

function Get-WorkerStatus {
    Write-Header "Broadcast Queue Worker Status"
    
    $processes = Get-WorkerProcess
    
    if ($processes) {
        Write-ColorOutput "✓ Broadcast worker is running" "Success"
        foreach ($process in $processes) {
            Write-Host "  PID: $($process.Id)"
            Write-Host "  Start Time: $($process.StartTime)"
            Write-Host "  CPU Time: $($process.TotalProcessorTime)"
            Write-Host "  Memory: $([Math]::Round($process.WorkingSet64 / 1MB, 2)) MB"
        }
    } else {
        Write-ColorOutput "✗ Broadcast worker is not running" "Error"
    }
    
    # Check log file
    if (Test-Path $LogFile) {
        $logInfo = Get-Item $LogFile
        Write-Host "`nLog file: $LogFile"
        Write-Host "  Size: $([Math]::Round($logInfo.Length / 1KB, 2)) KB"
        Write-Host "  Last Modified: $($logInfo.LastWriteTime)"
    }
}

function Start-WorkerMonitor {
    Write-Header "Monitoring Broadcast Queue Worker"
    Write-ColorOutput "Press Ctrl+C to stop monitoring" "Info"
    Write-Host ""
    
    try {
        while ($true) {
            Clear-Host
            Write-Header "Broadcast Worker Monitor - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
            
            $processes = Get-WorkerProcess
            
            if ($processes) {
                Write-ColorOutput "Status: RUNNING" "Success"
                foreach ($process in $processes) {
                    Write-Host "PID: $($process.Id) | CPU: $($process.TotalProcessorTime) | Memory: $([Math]::Round($process.WorkingSet64 / 1MB, 2)) MB"
                }
            } else {
                Write-ColorOutput "Status: STOPPED" "Error"
            }
            
            Write-Host ""
            Write-ColorOutput "Refreshing in 5 seconds..." "Info"
            Start-Sleep -Seconds 5
        }
    } catch {
        Write-ColorOutput "`nMonitoring stopped" "Info"
    }
}

# Main execution
Write-Header "Analytics Hub - Broadcast Queue Worker Manager"

if (!(Test-Prerequisites)) {
    exit 1
}

switch ($Action) {
    "start" { Start-BroadcastWorker }
    "stop" { Stop-BroadcastWorker }
    "restart" { 
        Stop-BroadcastWorker
        Start-Sleep -Seconds 2
        Start-BroadcastWorker
    }
    "status" { Get-WorkerStatus }
    "monitor" { Start-WorkerMonitor }
}

Write-Host ""