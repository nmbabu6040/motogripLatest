<?php

use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

// Tests must give the same result on every computer, whatever is written in the local .env file.
// This pins the switches the tests assume; a test that needs a different value sets it inside the test.
pest()->beforeEach(function () {
    config([
        'motogrip.features.affiliate' => false,
        'motogrip.features.adsense' => false,
        'motogrip.payments.enabled' => false,
        'motogrip.features.guest_comments' => true,
        'motogrip.features.dealer_locator' => true,
        'motogrip.csp_enabled' => true,
        'motogrip.backups.archive_password' => null,
    ]);
})->in('Feature');
