#!/usr/bin/env php
<?php
declare (strict_types = 1);
require __DIR__ . '/../vendor/autoload.php';

use Carbon\CarbonInterval;
use Stripe\Stripe;
use Stripe\Subscription;
use Support\RateLimiter;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

if (!isset($argv[1])) {
    echo 'Supply a subscription id parameter to test against.' . PHP_EOL;
    die;
}

$subId = $argv[1];

Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

$limiter = new RateLimiter(100, CarbonInterval::seconds(1), true);
$limiter->setMode(RateLimiter::MODE_DELAY);

foreach (range(0, 100) as $idx) {
    // Issue 100 get requests to the stripe api.
    $limiter->hit();
    try {
        Subscription::retrieve($subId);
        echo 'Retrieved subscription ' . $idx . ' times.' . PHP_EOL;
    } catch (\Stripe\Exception\RateLimitException $e) {
        echo "Exceeded the stripe api limit." . PHP_EOL;
        echo $e->getError()->message . PHP_EOL;
    }
}
