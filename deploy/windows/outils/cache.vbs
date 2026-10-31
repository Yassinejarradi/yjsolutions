' Lance un fichier .bat sans fenêtre et attend qu'il se termine (tâche planifiée de sauvegarde).
'   wscript cache.vbs "C:\JYSolutions\backup.bat" /auto
Option Explicit

Dim sh, command, i
Set sh = CreateObject("WScript.Shell")

command = ""
For i = 0 To WScript.Arguments.Count - 1
    command = command & " """ & WScript.Arguments(i) & """"
Next

WScript.Quit sh.Run("cmd /c """ & Trim(command) & """", 0, True)
