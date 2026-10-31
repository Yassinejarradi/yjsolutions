@echo off
rem Arrête JY Solutions : uniquement le php.exe qui écoute sur le port 8765 (les autres PHP ne sont pas touchés).
rem   stop.bat        arrête, puis attend une touche
rem   stop.bat /auto  sans attendre (update.bat)
chcp 65001 >nul <nul
setlocal
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0outils\arreter.ps1" -Port 8765 <nul
set "CODE=%errorlevel%"
if /i not "%~1"=="/auto" pause
exit /b %CODE%
