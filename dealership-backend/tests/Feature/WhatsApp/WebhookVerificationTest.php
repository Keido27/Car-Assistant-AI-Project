<?php

test('webhook verification succeeds with correct token', function () {
    $response = $this->get('/api/webhook/whatsapp?hub_mode=subscribe&hub_verify_token=test-verify-token&hub_challenge=12345');

    $response->assertStatus(200);
    $response->assertSee('12345');
});

test('webhook verification fails with wrong token', function () {
    $response = $this->get('/api/webhook/whatsapp?hub_mode=subscribe&hub_verify_token=wrong-token&hub_challenge=12345');

    $response->assertStatus(403);
});