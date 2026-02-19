@echo off
chcp 65001 >nul
echo ========================================
echo   URL Shortener - Stop Script
echo ========================================
echo.

REM STEP 1: Stop Docker containers
echo [INFO] Stopping Docker containers...
docker-compose down
echo [OK] Docker containers stopped

REM STEP 2: Check for running Node.js processes (Vite)
echo.
echo [INFO] Checking for running Vite dev server...
tasklist /FI "IMAGENAME eq node.exe" 2>nul | find /I "node.exe" >nul
if not errorlevel 1 (
    echo [WARN] Found running Node.js processes:
    tasklist /FI "IMAGENAME eq node.exe" 2>nul | find /I "node.exe"
    echo.
    set /p KILL_NODE="Stop Node.js processes (Vite)? (Y/N): "
    if /i "%KILL_NODE%"=="Y" (
        taskkill /F /IM node.exe >nul 2>&1
        echo [OK] Node.js processes stopped
    ) else (
        echo [INFO] Node.js processes left running
    )
) else (
    echo [OK] No Node.js processes found
)

echo.
echo ========================================
echo   All services stopped
echo ========================================
echo.
pause
