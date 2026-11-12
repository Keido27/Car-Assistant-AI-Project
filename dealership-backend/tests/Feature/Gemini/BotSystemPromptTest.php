<?php

use App\Services\Gemini\BotSystemPrompt;

test('marks unset business info as unknown instead of leaving a gap', function () {
    config(['dealership.opening_hours' => null]);

    expect(BotSystemPrompt::text())
        ->toContain('Opening hours (including which days it is open or closed): UNKNOWN');
});

test('includes configured business info verbatim', function () {
    config(['dealership.opening_hours' => 'Senin-Sabtu 09.00-17.00 WIB. Minggu tutup.']);

    expect(BotSystemPrompt::text())
        ->toContain('Senin-Sabtu 09.00-17.00 WIB. Minggu tutup.')
        ->not->toContain('Opening hours (including which days it is open or closed): UNKNOWN');
});
