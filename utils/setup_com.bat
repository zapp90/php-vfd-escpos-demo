@echo off
REM Usage: utils\setup_com.bat COM3 [9600]
set PORT=%1
if "%PORT%"=="" (
  echo Usage: utils\setup_com.bat COM3 [BAUD]
  exit /b 1
)
set BAUD=%2
if "%BAUD%"=="" set BAUD=9600
mode %PORT% BAUD=%BAUD% PARITY=N DATA=8 STOP=1
echo Configured %PORT% at %BAUD% 8N1
