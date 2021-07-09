#!/usr/bin/env php
<?php
declare (strict_types = 1);
require __DIR__ . '/../vendor/autoload.php';

use Support\RateLimiter;

$limiter = new RateLimiter(25, 1, true);
$limiter->setMode(RateLimiter::MODE_DELAY);

foreach (range(0, 100) as $idx) {
    $limiter->hit();
    echo "Hit: " . $idx . PHP_EOL;
}
