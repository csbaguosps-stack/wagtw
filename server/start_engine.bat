@echo off
title WAGTW Node Engine
:loop
echo Mengaktifkan WAGTW Node Engine...
node server.js
echo Engine crash atau berhenti, mengulang dalam 2 detik...
timeout /t 2 /nobreak >nul
goto loop
