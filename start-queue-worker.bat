@echo off
REM Analytics Hub Queue Worker Startup Script for Windows
REM This script starts the Laravel queue worker for email processing

REM Set the application directory (update this path as needed)
set APP_DIR=d:\XAMPP\htdocs\analytic-hub-v10

REM Set PHP executable path (update if PHP is in a different location)
set PHP_PATH=d:\XAMPP\php\php.exe

REM Change to application directory
cd /d "%APP_DIR%"

echo Starting Analytics Hub Queue Worker...
echo Application Directory: %APP_DIR%
echo PHP Path: %PHP_PATH%
echo.

REM Start the queue worker with appropriate settings
REM --sleep=3: Sleep for 3 seconds when no jobs available
REM --tries=3: Maximum attempts per job
REM --max-time=3600: Restart worker after 1 hour
REM --timeout=60: Maximum time per job
REM --queue=emails: Process emails queue specifically

"%PHP_PATH%" artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=60 --queue=emails

REM If the worker stops, pause to see any error messages
echo.
echo Queue worker has stopped. Check for any error messages above.
pause