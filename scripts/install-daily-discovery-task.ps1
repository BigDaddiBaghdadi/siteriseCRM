param(
    [string]$At = '09:00',
    [string]$City = 'Sofia',
    [string]$Country = 'Bulgaria',
    [string]$Niche = 'Dentists',
    [int]$Limit = 5
)

$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$Runner = Join-Path $PSScriptRoot 'run-daily-discovery.ps1'
$TaskName = 'SiteRise CRM Daily Discovery'
$PowerShell = (Get-Command powershell.exe).Source
$RunTime = [DateTime]::Parse($At)

$Args = "-NoProfile -ExecutionPolicy Bypass -File `"$Runner`" -City `"$City`" -Country `"$Country`" -Niche `"$Niche`" -Limit $Limit"
$Action = New-ScheduledTaskAction -Execute $PowerShell -Argument $Args -WorkingDirectory $RepoRoot
$Trigger = New-ScheduledTaskTrigger -Daily -At $RunTime
$Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RestartCount 2 -RestartInterval (New-TimeSpan -Minutes 5)

Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Settings $Settings -Description 'Queues and processes 5 real website leads for SiteRise CRM every day.' -Force | Out-Null

Write-Host "Installed scheduled task: $TaskName at $At daily"
Write-Host "Run now with: .\scripts\run-daily-discovery.ps1"
