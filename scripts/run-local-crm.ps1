param(
    [int]$Port = 8010,
    [switch]$Install,
    [switch]$Build
)

$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$CrmDir = Join-Path $RepoRoot 'crm'
$EnvPath = Join-Path $CrmDir '.env'

Push-Location $CrmDir
try {
    $PhpExtensions = & php -m
    if ($PhpExtensions -notcontains 'mbstring') {
        throw 'Local PHP is missing the mbstring extension. Enable mbstring in php.ini before running the Laravel CRM locally.'
    }

    if (-not (Test-Path $EnvPath)) {
        Copy-Item '.env.example' '.env'
        Write-Host 'Created crm\.env from crm\.env.example'
    }

    if ($Install -or -not (Test-Path (Join-Path $CrmDir 'vendor\autoload.php'))) {
        composer install
    }

    if ($Install -or -not (Test-Path (Join-Path $CrmDir 'node_modules'))) {
        npm install
    }

    $envContent = Get-Content $EnvPath -Raw

    if ($envContent -match '(?m)^APP_KEY=\s*$') {
        php artisan key:generate
    }

    if ($envContent -match '(?m)^DB_CONNECTION=sqlite\s*$') {
        New-Item -ItemType File -Force 'database\database.sqlite' | Out-Null
    }

    php artisan migrate --force

    if ($Build) {
        npm run build
    }

    php artisan serve --host=127.0.0.1 --port=$Port
}
finally {
    Pop-Location
}
