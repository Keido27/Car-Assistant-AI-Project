#!/usr/bin/env bash
# 8-prompt x 5-repeat matrix for gemini:test-tools, both models.
# Run from dealership-backend/. Output goes to matrix.log.
# Requires that config/ reads GEMINI_MODEL via env() and config is NOT cached.

# Always run from the script's own folder (must be dealership-backend/)
cd "$(dirname "$0")" || exit 1
if [ ! -f artisan ]; then
  echo "artisan not found. Put this script in dealership-backend/ and run it there."
  exit 1
fi

# Call php.exe directly so Git Bash doesn't route it through winpty
# (winpty is what prints "stdout is not a tty" when output is redirected).
PHP=php.exe
command -v "$PHP" >/dev/null || PHP=php

"$PHP" artisan config:clear

prompts=(
  "mobil keluarga yang bagus apa ya"
  "ada Avanza tahun 2019 ke atas, matic, di bawah 200 juta?"
  "budget 150 juta dapet mobil apa aja?"
  "ada Lamborghini gak?"
  "Fortuner 2020 di sini 300 juta kan?"
  "kalau kredit DP 30 juta, cicilan per bulannya berapa?"
  "mau nego harga sama test drive besok bisa?"
  "Hi, what time are you open on Sunday?"
)

models=("gemini-3.5-flash" "gemini-3.5-flash-lite")
LOG=matrix.log
: > "$LOG"

for model in "${models[@]}"; do
  for i in "${!prompts[@]}"; do
    for run in 1 2 3 4 5; do
      echo "=== $model | P$((i+1)) | run $run ===" >> "$LOG"
      start=$SECONDS
      GEMINI_MODEL="$model" "$PHP" artisan gemini:test-tools "${prompts[$i]}" >> "$LOG" 2>&1
      code=$?
      echo "--- exit=$code time=$((SECONDS - start))s" >> "$LOG"
      echo >> "$LOG"
      sleep 5   # stay under free-tier rate limits
    done
  done
done

echo "Done. 80 runs logged to $LOG"
