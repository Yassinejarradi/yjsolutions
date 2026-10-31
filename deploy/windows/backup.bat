@echo off
rem Sauvegarde de JY Solutions Gestion dans un fichier sauvegarde-AAAA-MM-JJ-HHMMSS.zip :
rem la base (copie sûre faite par SQLite avec VACUUM INTO, même si l'application est ouverte),
rem storage\app\private (logos) et storage\app\public. Les plus anciennes au-delà de GARDER sont supprimées.
rem   backup.bat           sauvegarde, puis attend une touche
rem   backup.bat /auto     sans attendre, messages dans storage\logs\sauvegardes.log (tâche planifiée, update.bat)
rem   backup.bat /dossier  affiche seulement le dossier des sauvegardes (install.bat)
chcp 65001 >nul <nul
setlocal EnableExtensions

rem ===== Réglages ===========================================================================
rem Dossier des sauvegardes. Vide = automatique : dossier OneDrive de l'utilisateur s'il existe,
rem sinon Documents. Exemple : set "DOSSIER=D:\Sauvegardes JY Solutions"
set "DOSSIER="
rem Nombre de sauvegardes gardées.
set "GARDER=30"
rem ===========================================================================================

cd /d "%~dp0"
if not defined DOSSIER if defined OneDrive if exist "%OneDrive%\" set "DOSSIER=%OneDrive%\JY Solutions Backups"
if not defined DOSSIER for /f "usebackq delims=" %%D in (`powershell -NoProfile -Command "[Environment]::GetFolderPath('MyDocuments')" ^<nul`) do set "DOSSIER=%%D\JY Solutions Backups"
rem Sans « \ » final : sinon \" ferait partie du nom pour PHP.
if "%DOSSIER:~-1%"=="\" set "DOSSIER=%DOSSIER:~0,-1%"

if /i "%~1"=="/dossier" goto :afficher_dossier
if /i "%~1"=="/auto" goto :auto

echo Sauvegarde de JY Solutions vers :
echo   %DOSSIER%
echo.
"%~dp0php\php.exe" artisan backup:run --to="%DOSSIER%" --keep=%GARDER%
set "CODE=%errorlevel%"
echo.
pause
exit /b %CODE%

:auto
if not exist "storage\logs" mkdir "storage\logs"
>>"storage\logs\sauvegardes.log" echo [%date% %time%] Sauvegarde vers %DOSSIER%
"%~dp0php\php.exe" artisan backup:run --to="%DOSSIER%" --keep=%GARDER% >>"storage\logs\sauvegardes.log" 2>&1
exit /b %errorlevel%

:afficher_dossier
echo %DOSSIER%
exit /b 0
