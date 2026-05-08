param(
    [switch]$Once,
    [int]$MaxCycles = 0
)

$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$WorkerDir = Join-Path $RepoRoot 'worker'
$VenvPython = Join-Path $WorkerDir '.venv\Scripts\python.exe'

if (-not (Test-Path $VenvPython)) {
    Write-Host 'Creating worker virtual environment...'
    python -m venv (Join-Path $WorkerDir '.venv')
}

& $VenvPython -m pip install --upgrade pip | Out-Host
& $VenvPython -m pip install -e $WorkerDir | Out-Host

$BrowserMarker = Join-Path $WorkerDir '.venv\.playwright-chromium-installed'
if (-not (Test-Path $BrowserMarker)) {
    Write-Host 'Installing Playwright Chromium for website snapshots...'
    & $VenvPython -m playwright install chromium | Out-Host
    New-Item -ItemType File -Force -Path $BrowserMarker | Out-Null
}

$EnvPath = Join-Path $WorkerDir '.env'
if (-not (Test-Path $EnvPath)) {
    $TokenPath = Join-Path $env:USERPROFILE '.openclaw\identity\siterise-worker-token.txt'
    $Token = if (Test-Path $TokenPath) { (Get-Content $TokenPath -Raw).Trim() } else { 'PASTE_WORKER_TOKEN_HERE' }

    @"
CRM_API_BASE=https://crm.ux-s.com
WORKER_TOKEN=$Token
WORKER_NAME=alan-windows-worker
MAX_CONCURRENCY=1
JOB_TIMEOUT_SECONDS=60
SCREENSHOT_DIR=./storage/screenshots
DISCOVERY_PROVIDER=demo
POLL_INTERVAL_SECONDS=30
"@ | Set-Content -Path $EnvPath -Encoding UTF8

    Write-Host "Created worker .env at $EnvPath"
}

Push-Location $WorkerDir
try {
    if ($Once) {
        & $VenvPython -m audit_worker run-discovery-once
        exit $LASTEXITCODE
    }

    if ($MaxCycles -gt 0) {
        & $VenvPython -m audit_worker work --max-cycles $MaxCycles
        exit $LASTEXITCODE
    }

    & $VenvPython -m audit_worker work
    exit $LASTEXITCODE
}
finally {
    Pop-Location
}
