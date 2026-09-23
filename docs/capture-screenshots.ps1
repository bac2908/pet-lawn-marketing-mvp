param(
    [string]$BaseUrl = 'http://localhost:8000'
)

$ErrorActionPreference = 'Stop'
if (!(Get-Command node -ErrorAction SilentlyContinue)) {
    throw 'Node.js 22.12+ or 24 is required.'
}
& node (Join-Path $PSScriptRoot 'capture-screenshots.mjs') $BaseUrl
if ($LASTEXITCODE -ne 0) { throw 'Screenshot capture failed.' }
