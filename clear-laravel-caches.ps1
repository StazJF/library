$ErrorActionPreference = 'Stop'

$configCache = Join-Path $PSScriptRoot 'bootstrap/cache/config.php'
if (Test-Path -LiteralPath $configCache) {
    Remove-Item -LiteralPath $configCache -Force
}

$viewCacheDir = Join-Path $PSScriptRoot 'storage/framework/views'
if (-not (Test-Path -LiteralPath $viewCacheDir)) {
    New-Item -ItemType Directory -Path $viewCacheDir -Force | Out-Null
}

Get-ChildItem -LiteralPath $viewCacheDir -Filter '*.php' -File -ErrorAction SilentlyContinue |
    Remove-Item -Force -ErrorAction SilentlyContinue

Write-Host "Cleared Laravel config cache and compiled views."

