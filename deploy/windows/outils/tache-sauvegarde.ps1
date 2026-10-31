# Tâche planifiée Windows « JY Solutions - Sauvegarde » : backup.bat chaque jour à 12:00 et à l'ouverture de session.
#   -Supprimer  retire la tâche
param([switch] $Supprimer)

$ErrorActionPreference = 'Stop'
$app = Split-Path -Parent $PSScriptRoot
$name = 'JY Solutions - Sauvegarde'

if ($Supprimer) {
    Unregister-ScheduledTask -TaskName $name -Confirm:$false -ErrorAction SilentlyContinue
    Write-Host "Tâche « $name » supprimée."
    exit 0
}

# wscript + cache.vbs : la sauvegarde tourne sans ouvrir de fenêtre noire.
$action = New-ScheduledTaskAction -Execute (Join-Path $env:WINDIR 'System32\wscript.exe') `
    -Argument ('"{0}" "{1}" /auto' -f (Join-Path $app 'outils\cache.vbs'), (Join-Path $app 'backup.bat')) `
    -WorkingDirectory $app

$user = "$env:USERDOMAIN\$env:USERNAME"
$daily = New-ScheduledTaskTrigger -Daily -At '12:00'
# Heure locale sans décalage UTC : reste 12:00 au changement d'heure (sinon Windows fige le décalage du jour).
$daily.StartBoundary = (Get-Date '12:00').ToString('yyyy-MM-ddTHH:mm:ss')
$logon = New-ScheduledTaskTrigger -AtLogOn -User $user
$logon.Delay = 'PT2M'   # laisse OneDrive démarrer après l'ouverture de session

# StartWhenAvailable : si le PC était éteint à 12:00, la sauvegarde se fait dès qu'il est rallumé.
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 30) -MultipleInstances IgnoreNew

# Tâche de l'utilisateur connecté : pas besoin de droits administrateur ni de mot de passe.
$principal = New-ScheduledTaskPrincipal -UserId $user -LogonType Interactive -RunLevel Limited

Register-ScheduledTask -TaskName $name -Action $action -Trigger $daily, $logon -Settings $settings `
    -Principal $principal -Description 'Sauvegarde de JY Solutions Gestion (backup.bat)' -Force | Out-Null

Write-Host "Tâche « $name » créée : chaque jour à 12:00 et 2 minutes après l'ouverture de session."
