param(
    [Parameter(Mandatory = $true)]
    [string]$PhpBinary
)

$ErrorActionPreference = 'Stop'
if (-not (Test-Path -LiteralPath $PhpBinary -PathType Leaf)) {
    throw 'PHP executable not found.'
}
$schedulerRepo = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $schedulerRepo
$env:TELESCOPE_ENABLED = 'false'
& $PhpBinary artisan schedule:run --no-interaction
exit $LASTEXITCODE
