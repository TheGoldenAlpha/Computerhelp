@echo off
rem Startet den GoBattle-Scan und startet ihn bei einem Absturz automatisch neu.
rem Holt vor jedem Start die neueste Version und den neuesten Fortschritt aus Git.
rem Beenden: Strg+C im Fenster (Fortschritt wird gesichert), danach haelt diese Datei an.
cd /d "%~dp0"
title GoBattle-Scan

for /f "delims=" %%b in ('git rev-parse --abbrev-ref HEAD') do set BRANCH=%%b

:loop
echo [%date% %time%] Hole neueste Version (Branch %BRANCH%)...
git pull --rebase --autostash -q origin %BRANCH%
python -m pip install -q requests
python alpha_scan_simple.py
set CODE=%errorlevel%
if %CODE%==0 goto fertig
if %CODE%==130 goto abgebrochen
if %CODE%==2 goto fehler
echo [%date% %time%] Script beendet mit Fehlercode %CODE%. Neustart in 30 Sekunden (Strg+C zum Abbrechen)...
timeout /t 30 /nobreak >nul
goto loop

:fertig
echo Fertig: alle IDs sind geprueft.
goto ende

:abgebrochen
echo Abgebrochen. Fortschritt ist gesichert. Zum Weitermachen diese Datei nochmal starten.
goto ende

:fehler
echo Fehler, den ein Neustart nicht loest (siehe Meldung oben). Bitte beheben und nochmal starten.
goto ende

:ende
pause
