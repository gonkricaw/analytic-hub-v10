@echo off
REM Analytics Hub - Broadcast Queue Worker Starter
REM This batch file starts the queue worker for processing broadcast events
REM Author: Analytics Hub Team
REM Version: 1.0.0

echo ========================================
echo Analytics Hub - Broadcast Queue Worker
echo ========================================
echo.

REM Check if PHP is available
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not available in PATH
    echo Please ensure PHP is installed and added to your system PATH
    pause
    exit /b 1
)

REM Check if we're in the correct directory
if not exist "artisan" (
    echo ERROR: artisan file not found
    echo Please run this script from the Laravel project root directory
    pause
    exit /b 1
)

echo Starting broadcast queue worker...
echo.
echo Configuration:
echo - Queue: default
echo - Timeout: 60 seconds
echo - Memory Limit: 128 MB
echo - Max Tries: 3
echo - Delay on Failure: 5 seconds
echo.
echo Press Ctrl+C to stop the worker
echo ========================================
echo.

REM Start the broadcast queue worker
php artisan broadcast:start --queue=default --timeout=60 --memory=128 --tries=3 --delay=5

echo.
echo ========================================
echo Broadcast queue worker stopped
echo ========================================
pause