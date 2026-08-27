@echo off

call :test "Baseline" "Ada Toyota Avanza gak?"
call :test "Multi-criteria" "Cari Honda matic tahun di atas 2019, harga di bawah 200 juta"
call :test "Vague" "mobil keluarga yang bagus apa ya"
call :test "No-match" "Ada Ferrari?"
call :test "Memory-bait" "Kemarin saya lihat ada Avanza abu-abu, itu masih ada?"
call :test "Financing" "Kalau DP 20 juta, cicilan per bulan berapa ya buat Avanza itu?"
call :test "Handoff" "saya udah nanya berkali-kali gak dijawab, mau ketemu sales aja"
call :test "English" "Do you have any Hondas under 200 million rupiah?"

exit /b

:test
echo.
echo ========== %~1 ==========
echo USER: %~2
php artisan gemini:test-tools "%~2"
echo ============================
pause
exit /b