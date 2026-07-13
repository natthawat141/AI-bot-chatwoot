# Fills the LINE Channel Access Token into .mcp.json by reading it from the
# existing line-bot-service/.env — the token value is never printed.
#
# Usage:
#   powershell -ExecutionPolicy Bypass -File D:\git\line\scripts\setup-line-mcp.ps1

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $root 'line-bot-service\.env'
$mcpFile = Join-Path $root '.mcp.json'

if (-not (Test-Path $envFile)) { throw "Missing file: $envFile" }
if (-not (Test-Path $mcpFile)) { throw "Missing file: $mcpFile" }

$tokenLine = Get-Content $envFile | Where-Object { $_ -match '^LINE_CHANNEL_ACCESS_TOKEN=' } | Select-Object -First 1
if (-not $tokenLine) { throw "LINE_CHANNEL_ACCESS_TOKEN not found in $envFile" }
$token = ($tokenLine -split '=', 2)[1].Trim()
if ([string]::IsNullOrWhiteSpace($token)) { throw "LINE_CHANNEL_ACCESS_TOKEN is empty in $envFile" }

$json = Get-Content $mcpFile -Raw | ConvertFrom-Json
$json.mcpServers.'line-bot'.env.CHANNEL_ACCESS_TOKEN = $token

$utf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($mcpFile, ($json | ConvertTo-Json -Depth 10), $utf8)

Write-Host "OK: token written into .mcp.json (value not shown)." -ForegroundColor Green
Write-Host "Next: restart Claude Code so it loads the 'line-bot' MCP server."
