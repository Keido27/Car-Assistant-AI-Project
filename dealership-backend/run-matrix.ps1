# run-matrix.ps1 — Gemini model grounding/reliability matrix
# Put this file in dealership-backend\ and run it from there.
#
# Usage (inside a PowerShell window):
#   Set-ExecutionPolicy -Scope Process Bypass
#   .\run-matrix.ps1 -Only 4,5,6,8                                  # grounding check, flash-lite only
#   .\run-matrix.ps1                                                # all 8 prompts, flash-lite only
#   .\run-matrix.ps1 -Models gemini-3.5-flash,gemini-3.5-flash-lite # full comparison
#
# The model is set per run via the GEMINI_MODEL environment variable, which
# takes precedence over .env — so .env is never edited by this script.

param(
    [string[]]$Models = @("gemini-3.5-flash-lite"),
    [int]$Repeats = 5,
    [int[]]$Only = @(),          # prompt numbers to run; empty = all
    [int]$DelaySeconds = 5       # pause between calls, to stay under free-tier rate limits
)

# Read PHP's output as UTF-8 (Windows PowerShell defaults to the legacy console codepage)
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding = [System.Text.Encoding]::UTF8

$prompts = @(
    "mobil keluarga yang bagus apa ya",                              # 1 Vague
    "ada Avanza tahun 2019 ke atas, matic, di bawah 200 juta?",       # 2 Specific filters
    "budget 150 juta dapet mobil apa aja?",                           # 3 Budget only
    "ada Lamborghini gak?",                                           # 4 No match
    "Fortuner 2020 di sini 300 juta kan?",                            # 5 Price bait
    "kalau kredit DP 30 juta, cicilan per bulannya berapa?",          # 6 Financing (tool not built)
    "mau nego harga sama test drive besok bisa?",                     # 7 Handoff
    "Hi, what time are you open on Sunday?"                           # 8 Off-topic / English
)

$log = "matrix.log"
$summary = @()
Add-Content -Path $log -Encoding utf8 -Value "`n=== Run started $(Get-Date -Format s) | models: $($Models -join ', ') ==="

foreach ($model in $Models) {
    $env:GEMINI_MODEL = $model

    for ($i = 0; $i -lt $prompts.Count; $i++) {
        $n = $i + 1
        if ($Only.Count -gt 0 -and $Only -notcontains $n) { continue }

        for ($r = 1; $r -le $Repeats; $r++) {
            $sw = [Diagnostics.Stopwatch]::StartNew()
            $out = php artisan gemini:test-tools "$($prompts[$i])" 2>&1 | Out-String
            $code = $LASTEXITCODE
            $sw.Stop()

            $status = if ($code -eq 0) { "OK" } else { "ERROR" }
            $secs = [math]::Round($sw.Elapsed.TotalSeconds, 1)
            $entry = "--- model=$model | prompt=$n | run=$r | $status | ${secs}s ---`n$($out.Trim())`n"

            Write-Host $entry
            Add-Content -Path $log -Encoding utf8 -Value $entry
            $summary += [pscustomobject]@{ Model = $model; Prompt = $n; Status = $status; Seconds = $secs }

            Start-Sleep -Seconds $DelaySeconds
        }
    }
}

Remove-Item Env:GEMINI_MODEL -ErrorAction SilentlyContinue

Write-Host "`n=== Error count and average latency per model ==="
$summary | Group-Object Model | ForEach-Object {
    [pscustomobject]@{
        Model      = $_.Name
        Runs       = $_.Count
        Errors     = ($_.Group | Where-Object Status -eq "ERROR").Count
        AvgSeconds = [math]::Round(($_.Group | Measure-Object Seconds -Average).Average, 1)
    }
} | Format-Table -AutoSize
