@echo off
rem Crée la tâche planifiée Windows « JY Solutions - Sauvegarde » : backup.bat chaque jour à 12:00
rem et 2 minutes après l'ouverture de session, sans fenêtre. Pas besoin d'être administrateur.
rem   install-backup-task.bat            crée (ou remplace) la tâche
rem   install-backup-task.bat /supprimer retire la tâche
chcp 65001 >nul <nul
setlocal
if /i "%~1"=="/supprimer" (
    powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0outils\tache-sauvegarde.ps1" -Supprimer <nul
) else (
    powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0outils\tache-sauvegarde.ps1" <nul
)
set "CODE=%errorlevel%"
if not "%CODE%"=="0" echo [ERREUR] La tâche n'a pas pu être créée.
echo.
pause
exit /b %CODE%
