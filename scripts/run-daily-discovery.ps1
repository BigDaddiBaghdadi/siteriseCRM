param(
    [string]$City = 'Sofia',
    [string]$Country = 'Bulgaria',
    [string]$Niche = 'Dentists',
    [int]$Limit = 5
)

$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$KeyPath = Join-Path $env:USERPROFILE '.ssh\id_ed25519_siterise_hetzner'

Write-Host "Queueing daily discovery job: $Limit $Niche leads in $City, $Country"
ssh -i $KeyPath -o BatchMode=yes root@88.99.189.7 "cd /var/www/website-audit-crm/current/crm && php artisan lead-discovery:queue-daily --city='$City' --country='$Country' --niche='$Niche' --limit=$Limit"

Write-Host 'Running local worker to process the queued job...'
& (Join-Path $PSScriptRoot 'run-local-worker.ps1') -MaxCycles 3
exit $LASTEXITCODE
