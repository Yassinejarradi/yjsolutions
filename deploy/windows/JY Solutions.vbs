' JY Solutions Gestion : ouvre l'application (le raccourci du bureau pointe ici).
'
' 1. Si rien ne répond sur 127.0.0.1:8765, démarre « php artisan serve » dans une fenêtre cachée
'    et attend qu'il réponde (15 secondes au plus).
' 2. Ouvre Edge en mode application (fenêtre sans barre d'adresse).
'
' Un second double-clic ne démarre jamais un second serveur : le serveur qui répond est réutilisé, et pendant
' un démarrage, un fichier verrou (storage\framework\demarrage.lock) fait attendre les autres clics.
' deploy/build-package.ps1 enregistre ce fichier en UTF-16 : sinon Windows affiche mal les accents.
Option Explicit

Const PORT = 8765
Const ATTENTE_MAX = 15        ' secondes
Const VERROU_PERIME = 30      ' secondes : un verrou plus vieux vient d'un démarrage interrompu

Dim fso, sh, appDir, url, verrou, verrouPris
Set fso = CreateObject("Scripting.FileSystemObject")
Set sh = CreateObject("WScript.Shell")
appDir = fso.GetParentFolderName(WScript.ScriptFullName)
url = "http://127.0.0.1:" & PORT
verrou = appDir & "\storage\framework\demarrage.lock"
verrouPris = False

If Not ServeurRepond() Then
    If PrendreVerrou() Then
        If Not fso.FileExists(appDir & "\php\php.exe") Then
            Erreur "Le programme PHP est introuvable dans le dossier :" & vbCrLf & appDir & "\php"
        End If
        sh.CurrentDirectory = appDir
        ' 0 = fenêtre cachée ; False = ne pas attendre la fin du serveur.
        sh.Run """" & appDir & "\php\php.exe"" artisan serve --host=127.0.0.1 --port=" & PORT, 0, False
    End If

    If Not AttendreServeur() Then
        Erreur "JY Solutions n'a pas pu démarrer." & vbCrLf & vbCrLf & _
               "Redémarrez l'ordinateur, puis double-cliquez à nouveau sur l'icône JY Solutions." & vbCrLf & _
               "Si le problème continue, demandez de l'aide."
    End If
    LibererVerrou
End If

OuvrirFenetre


' Le serveur répond-il ? (page /up de Laravel)
Function ServeurRepond()
    Dim http
    ServeurRepond = False
    On Error Resume Next
    Set http = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    http.setTimeouts 1000, 1000, 3000, 3000
    http.open "GET", url & "/up", False
    http.send
    If Err.Number = 0 Then ServeurRepond = (http.status = 200)
    On Error GoTo 0
End Function

Function AttendreServeur()
    Dim i
    AttendreServeur = False
    For i = 1 To ATTENTE_MAX * 4
        If ServeurRepond() Then
            AttendreServeur = True
            Exit Function
        End If
        WScript.Sleep 250
    Next
End Function

' Crée le fichier verrou. Faux si un autre clic est déjà en train de démarrer le serveur.
Function PrendreVerrou()
    Dim fichier
    On Error Resume Next
    If fso.FileExists(verrou) Then
        If DateDiff("s", fso.GetFile(verrou).DateLastModified, Now) > VERROU_PERIME Then fso.DeleteFile verrou, True
    End If
    Err.Clear
    Set fichier = fso.CreateTextFile(verrou, False)   ' False : échoue si le fichier existe déjà
    PrendreVerrou = (Err.Number = 0)
    If PrendreVerrou Then
        fichier.Close
        verrouPris = True
    End If
    On Error GoTo 0
End Function

Sub LibererVerrou()
    On Error Resume Next
    If verrouPris Then fso.DeleteFile verrou, True
    verrouPris = False
    On Error GoTo 0
End Sub

Sub Erreur(message)
    LibererVerrou
    MsgBox message, vbExclamation, "JY Solutions"
    WScript.Quit 1
End Sub

' Edge en mode application ; à défaut, le navigateur par défaut.
Sub OuvrirFenetre()
    Dim chemins, chemin, edge
    chemins = Array( _
        sh.ExpandEnvironmentStrings("%ProgramFiles(x86)%") & "\Microsoft\Edge\Application\msedge.exe", _
        sh.ExpandEnvironmentStrings("%ProgramFiles%") & "\Microsoft\Edge\Application\msedge.exe", _
        sh.ExpandEnvironmentStrings("%LocalAppData%") & "\Microsoft\Edge\Application\msedge.exe")
    edge = ""
    For Each chemin In chemins
        If edge = "" And fso.FileExists(chemin) Then edge = chemin
    Next

    If edge <> "" Then
        sh.Run """" & edge & """ --app=" & url & " --window-size=1366,850", 1, False
    Else
        sh.Run url, 1, False
    End If
End Sub
