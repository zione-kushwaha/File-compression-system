@echo off
title Secure File Compression System
echo ========================================================
echo        SECURE FILE COMPRESSION SYSTEM (PHP 8.3)
echo ========================================================
echo.

set PHP_BIN=tools\php\php.exe
if not exist "%PHP_BIN%" (
    set PHP_BIN=php
)

echo Starting Local Development Server...
echo Server running at: http://127.0.0.1:8000
echo Press Ctrl+C in this terminal window to stop the server.
echo.

start http://127.0.0.1:8000
"%PHP_BIN%" -S 127.0.0.1:8000 -t public
pause
