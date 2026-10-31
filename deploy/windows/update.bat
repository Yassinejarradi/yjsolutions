@echo off
rem Mise à jour d'une installation existante avec CE paquet (nouvelle version).
rem À lancer depuis le dossier du NOUVEAU paquet, pas depuis le dossier installé :
rem   update.bat                   demande le dossier installé (C:\JYSolutions par défaut)
rem   update.bat "C:\JYSolutions"
rem Ne touche pas à : .env, database\database.sqlite, storage\ (dont storage\app), php\.
rem Ne copie pas .env.production : avec APP_ENV=production, Laravel le lirait à la place de .env (sans clé).
rem Fait une sauvegarde AVANT de remplacer le code.
chcp 65001 >nul <nul
setlocal EnableExtensions
title JY Solutions - Mise à jour
set "SOURCE=%~dp0"
set "SOURCE=%SOURCE:~0,-1%"

set "CIBLE=%~1"
if not defined CIBLE set /p "CIBLE=Dossier de l'application installée [C:\JYSolutions] : "
if not defined CIBLE set "CIBLE=C:\JYSolutions"
for %%I in ("%CIBLE%") do set "CIBLE=%%~fI"
if "%CIBLE:~-1%"=="\" set "CIBLE=%CIBLE:~0,-1%"
set "PHP=%CIBLE%\php\php.exe"

if /i "%CIBLE%"=="%SOURCE%" goto :meme_dossier
if not exist "%CIBLE%\.env" goto :pas_installe
if not exist "%PHP%" goto :pas_installe
if not exist "%SOURCE%\artisan" goto :paquet_incomplet
if not exist "%SOURCE%\vendor\autoload.php" goto :paquet_incomplet

echo.
echo  Mise à jour de : %CIBLE%
if exist "%SOURCE%\VERSION.txt" for /f "usebackq delims=" %%V in ("%SOURCE%\VERSION.txt") do echo  Nouvelle version : %%V
echo.

echo [1/5] Arrêt de l'application
call "%CIBLE%\stop.bat" /auto

echo.
echo [2/5] Sauvegarde avant mise à jour
call "%CIBLE%\backup.bat" /auto
if errorlevel 1 goto :sauvegarde_ratee
for /f "usebackq delims=" %%D in (`call "%CIBLE%\backup.bat" /dossier`) do echo       Sauvegarde faite dans %%D

echo.
echo [3/5] Copie de la nouvelle version
rem /MIR : le code devient identique au paquet (les fichiers retirés de la nouvelle version sont supprimés).
rem /XD /XF : jamais touchés, ni copiés ni supprimés. /XJ : ne suit pas le lien public\storage.
robocopy "%SOURCE%" "%CIBLE%" /MIR /XJ /R:2 /W:2 /NFL /NDL /NJH /NJS /NP ^
    /XD "%SOURCE%\php" "%CIBLE%\php" "%SOURCE%\storage" "%CIBLE%\storage" "%CIBLE%\public\storage" ^
    /XF .env .env.production database.sqlite database.sqlite-journal database.sqlite-wal database.sqlite-shm jysolutions.ico
if errorlevel 8 goto :echec

echo.
echo [4/5] Base de données
cd /d "%CIBLE%"
"%PHP%" artisan optimize:clear
if errorlevel 1 goto :echec
"%PHP%" artisan migrate --force
if errorlevel 1 goto :echec

echo.
echo [5/5] Optimisation
"%PHP%" artisan optimize
if errorlevel 1 goto :echec

echo.
echo  Mise à jour terminée. Double-cliquez sur « JY Solutions » sur le bureau.
echo.
pause
exit /b 0

:meme_dossier
echo [ERREUR] Lancez update.bat depuis le dossier du NOUVEAU paquet, pas depuis le dossier installé.
goto :fin_erreur

:pas_installe
echo [ERREUR] Aucune installation trouvée dans %CIBLE% (il faut .env et php\php.exe).
goto :fin_erreur

:paquet_incomplet
echo [ERREUR] Ce paquet est incomplet : refaites-le avec deploy\build-package.ps1.
goto :fin_erreur

:sauvegarde_ratee
echo [ERREUR] La sauvegarde a échoué : rien n'a été modifié.
echo          Détails : %CIBLE%\storage\logs\sauvegardes.log
goto :fin_erreur

:echec
echo.
echo [ERREUR] La mise à jour s'est arrêtée. La sauvegarde faite à l'étape 2 permet de revenir en arrière.

:fin_erreur
echo.
pause
exit /b 1
