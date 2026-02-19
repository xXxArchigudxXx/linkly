@echo off
chcp 65001 >nul
echo ========================================
echo   URL Shortener - Unit Tests
echo ========================================
echo.

echo [INFO] Running PHPUnit tests...
echo.
call composer test

echo.
echo ========================================
echo   Tests completed!
echo ========================================
pause