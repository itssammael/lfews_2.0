@echo off

echo Installing LFEWS_Modbus_Data Service...
nssm install LFEWS_Modbus_Data "C:\Users\LFEWS\AppData\Local\Programs\PHP\current\php.exe" "artisan app:pull-modbus-data"
nssm set LFEWS_Modbus_Data AppDirectory "c:\Production\lfews_2.0"
nssm set LFEWS_Modbus_Data Description "LFEWS Modbus Data Pull Service"
nssm set LFEWS_Modbus_Data AppStdout "c:\Production\lfews_2.0\storage\logs\modbus_service.log"
nssm set LFEWS_Modbus_Data AppStderr "c:\Production\lfews_2.0\storage\logs\modbus_service.log"

echo Installing LFEWS_Weather_Data Service...
nssm install LFEWS_Weather_Data "C:\Users\LFEWS\AppData\Local\Programs\PHP\current\php.exe" "artisan app:pull-weather-observation-data"
nssm set LFEWS_Weather_Data AppDirectory "c:\Production\lfews_2.0"
nssm set LFEWS_Weather_Data Description "LFEWS Weather Observation Data Pull Service"
nssm set LFEWS_Weather_Data AppStdout "c:\Production\lfews_2.0\storage\logs\weather_service.log"
nssm set LFEWS_Weather_Data AppStderr "c:\Production\lfews_2.0\storage\logs\weather_service.log"

echo Starting services...
nssm start LFEWS_Modbus_Data
nssm start LFEWS_Weather_Data

echo Done.
pause
