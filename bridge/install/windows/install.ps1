$ErrorActionPreference = 'Stop'

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$BaseDir = Resolve-Path (Join-Path $ScriptDir '..\..')
$BridgeDir = Join-Path $BaseDir 'bridge'
$ConfigPath = Join-Path $BridgeDir 'config.json'
$ServerPath = Join-Path $BridgeDir 'src\server.js'
$TaskName = 'CustomRTBridge'

$Cmd = "cd /d `"$BridgeDir`" && set BRIDGE_CONFIG=$ConfigPath && node `"$ServerPath`""
$Action = New-ScheduledTaskAction -Execute 'cmd.exe' -Argument "/c $Cmd"
$Trigger = New-ScheduledTaskTrigger -AtLogOn
$Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Settings $Settings -RunLevel Highest -Force | Out-Null
Write-Output "Bridge installato."
