param()

$ErrorActionPreference = 'Stop'

$TaskName = 'SiteRise CRM Daily Discovery'
$ExistingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue

if ($ExistingTask) {
    Disable-ScheduledTask -TaskName $TaskName | Out-Null
    Write-Host "Disabled scheduled task: $TaskName"
} else {
    Write-Host "No scheduled task found: $TaskName"
}

Write-Host 'Automatic daily lead generation is intentionally disabled.'
Write-Host 'To generate leads manually, queue a job in the CRM Lead Discovery page or run:'
Write-Host '.\scripts\run-daily-discovery.ps1 -City "Sofia" -Country "Bulgaria" -Niche "Dentists" -Limit 5'
