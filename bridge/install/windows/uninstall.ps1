$ErrorActionPreference = 'Stop'

$TaskName = 'CustomRTBridge'
$task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
if ($null -ne $task) {
  Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false | Out-Null
  Write-Output "Bridge rimosso."
} else {
  Write-Output "Task non trovato."
}
