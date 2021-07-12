#!/usr/bin/env php
<?php
declare (strict_types = 1);
require __DIR__ . '/../vendor/autoload.php';

use Carbon\CarbonInterval;
use Support\RateLimiter;

$limiter = new RateLimiter(100, CarbonInterval::seconds(1));
$limiter->setMode(RateLimiter::MODE_DELAY);

foreach (range(0, 199) as $idx) {
    $limiter->hit();
    echo "Hit: " . $idx . PHP_EOL;
}
