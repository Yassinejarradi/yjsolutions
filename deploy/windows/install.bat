@echo off
rem Installation de JY Solutions Gestion, une seule fois, sur le PC où l'application sera utilisée.
rem Avant : décompresser PHP 8.4 dans php\ (voir php\README.txt).
chcp 65001 >nul <nul
setlocal EnableExtensions
cd /d "%~dp0"
title JY Solutions - Installation
set "PHP=%~dp0php\php.exe"

echo.
echo  =============================================
echo    Installation de JY Solutions Gestion
echo  =============================================
echo.

if not exist "%PHP%" goto :sans_php
if exist ".env" goto :deja_installe
if not exist ".env.production" goto :sans_modele

echo [1/9] Réglage de PHP
powershell -NoProfile -ExecutionPolicy Bypass -File "outils\installer.ps1" -Etape php <nul
if errorlevel 1 goto :echec
"%PHP%" "outils\verifier-php.php"
set "CODE=%errorlevel%"
if "%CODE%"=="-1073741515" goto :sans_vcredist
if not "%CODE%"=="0" goto :echec

echo.
echo [2/9] Fichier de réglages .env
rem Déplacé, pas copié : avec APP_ENV=production, Laravel lirait .env.production (sans clé) à la place
rem de .env lors de la mise en cache de la configuration, et l'application démarrerait sans clé de chiffrement.
move /y ".env.production" ".env" >nul
if errorlevel 1 goto :echec
for /f "usebackq delims=" %%D in (`call "%~dp0backup.bat" /dossier`) do set "DOSSIER_SAUVEGARDES=%%D"
rem Même dossier pour Paramètres › Sauvegardes que pour backup.bat (guillemets simples : pas d'échappement des \).
>>".env" echo.
>>".env" echo BACKUP_PATH='%DOSSIER_SAUVEGARDES%'
"%PHP%" artisan key:generate --force <nul
if errorlevel 1 goto :echec

echo.
echo [3/9] Base de données database\database.sqlite
if not exist "database\database.sqlite" type nul > "database\database.sqlite"
"%PHP%" artisan migrate --force <nul
if errorlevel 1 goto :echec

echo.
echo [4/9] Informations de la société JY Solutions
"%PHP%" artisan db:seed --class=CompanySettingsSeeder --force <nul
if errorlevel 1 goto :echec

echo.
echo [5/9] Lien vers les fichiers publics
"%PHP%" artisan storage:link <nul
if errorlevel 1 goto :echec

echo.
echo [6/9] Optimisation
"%PHP%" artisan optimize <nul
if errorlevel 1 goto :echec

echo.
echo [7/9] Compte de connexion
set /a ESSAI=0
:utilisateur
set /a ESSAI+=1
if %ESSAI% GTR 3 goto :echec
set "NOM="
set "EMAIL="
set /p "NOM=  Nom complet : "
set /p "EMAIL=  Adresse email (identifiant de connexion) : "
"%PHP%" artisan user:create --name="%NOM%" --email="%EMAIL%"
if errorlevel 1 (
    echo   Recommençons.
    goto :utilisateur
)

echo.
echo [8/9] Raccourci « JY Solutions » sur le bureau
powershell -NoProfile -ExecutionPolicy Bypass -File "outils\installer.ps1" -Etape raccourci <nul
if errorlevel 1 goto :echec

echo.
echo [9/9] Sauvegardes : dossier %DOSSIER_SAUVEGARDES%
echo       Pour une sauvegarde automatique chaque jour : lancer install-backup-task.bat.

echo.
echo  Installation terminée. Double-cliquez sur « JY Solutions » sur le bureau.
echo.
pause
exit /b 0

:sans_php
echo [ERREUR] php\php.exe est introuvable.
echo          Décompressez PHP 8.4 dans le dossier php\ : voir php\README.txt.
goto :echec

:sans_vcredist
echo [ERREUR] PHP ne démarre pas : il manque « Microsoft Visual C++ Redistributable » x64.
echo          Installez https://aka.ms/vs/17/release/vc_redist.x64.exe puis relancez install.bat.
goto :echec

:sans_modele
echo [ERREUR] Le fichier .env.production manque : recopiez le paquet complet.
goto :echec

:deja_installe
echo [ERREUR] L'application est déjà installée dans ce dossier (le fichier .env existe).
echo          Rien n'a été modifié. Pour une nouvelle version : update.bat depuis le nouveau paquet.
goto :echec

:echec
echo.
echo  L'installation n'est pas terminée. Lisez le message ci-dessus, corrigez, puis relancez install.bat.
echo.
pause
exit /b 1
