param(
    [string]$HostName = '88.99.189.7',
    [string]$User = 'root',
    [string]$KeyPath = (Join-Path $env:USERPROFILE '.ssh\id_ed25519_siterise_hetzner')
)

$ErrorActionPreference = 'Stop'

if (-not (Test-Path $KeyPath)) {
    throw "SSH key not found: $KeyPath"
}

Write-Host 'DNS:'
Resolve-DnsName crm.ux-s.com | Format-Table -AutoSize

Write-Host 'Remote services:'
ssh -i $KeyPath -o BatchMode=yes -o ConnectTimeout=15 "$User@$HostName" "systemctl is-active lshttpd.service && systemctl is-active siterise-crm.service && ss -ltnp | grep -E ':80|:443|:8010'"

Write-Host 'Production URL:'
curl.exe -I --max-time 15 https://crm.ux-s.com/admin
