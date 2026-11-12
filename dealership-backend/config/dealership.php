<?php

/*
 * Business facts the bot is allowed to state. These are the ONLY source of
 * truth for them: anything left null, the bot must say it doesn't know and
 * offer to have the team confirm. Never let the model fill these in.
 *
 * Not secrets, so they live here rather than in .env. After editing, run
 * `php artisan config:clear` so a cached config doesn't hide the change.
 */
return [
    'name' => 'Hello Cars',            // e.g. 'Showroom Mobil Jaya'

    // Free text, written the way you'd tell a customer.
    // e.g. 'Senin-Sabtu 09.00-17.00 WIB. Minggu dan tanggal merah tutup.'
    'opening_hours' => 'Senin-Sabtu 09.00-17.00 WIB. Minggu dan tanggal merah tutup.',

    'address' => null,         // e.g. 'Jl. Contoh No. 12, Jakarta Selatan'
    'maps_url' => null,        // e.g. a Google Maps link
];
