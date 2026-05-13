param(
    [string]$HostName = '88.99.189.7',
    [string]$User = 'root',
    [string]$KeyPath = (Join-Path $env:USERPROFILE '.ssh\id_ed25519_siterise_hetzner'),
    [string]$RemoteRoot = '/var/www/website-audit-crm/current',
    [switch]$SkipPull,
    [switch]$SkipFrontend,
    [switch]$SkipRestart
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path $KeyPath)) {
    throw "SSH key not found: $KeyPath"
}

$steps = @(
    'set -euo pipefail',
    "cd '$RemoteRoot'"
)

if (-not $SkipPull) {
    $steps += 'git pull --ff-only'
}

$steps += @(
    'cd crm',
    'COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction'
)

if (-not $SkipFrontend) {
    $steps += @(
        'npm ci',
        'npm run build'
    )
}

$steps += @(
    'php artisan migrate --force',
    'php artisan storage:link --force',
    'php artisan optimize:clear',
    'php artisan optimize',
    'install -d -o www-data -g www-data storage bootstrap/cache public/screenshots public/redesigns',
    'chown -R www-data:www-data storage bootstrap/cache public/build public/storage public/screenshots public/redesigns'
)

if (-not $SkipRestart) {
    $steps += @(
        'systemctl restart siterise-crm.service',
        'systemctl reload lshttpd || systemctl restart lshttpd'
    )
}

$steps += @(
    'php artisan migrate:status --no-ansi',
    'systemctl is-active siterise-crm.service',
    'systemctl is-active lshttpd.service'
)

$remoteScript = $steps -join "`n"
$encodedScript = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($remoteScript))
$target = "$User@$HostName"
$remoteCommand = "printf '%s' '$encodedScript' | base64 -d | bash"

Write-Host "Deploying CRM to ${target}:$RemoteRoot"
ssh -i $KeyPath -o BatchMode=yes -o ConnectTimeout=15 $target $remoteCommand

Write-Host 'Checking production URL...'
curl.exe -I --max-time 15 https://crm.ux-s.com/admin
