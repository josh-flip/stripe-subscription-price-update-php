#!/usr/bin/env php
<?php
declare (strict_types = 1);
require __DIR__ . '/../vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Subscription;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

if (!isset($argv[1])) {
    echo 'You must the path to a file to import subscription ids from.';
}

$inputFile = $argv[1];

if (($handle = fopen($inputFile, "r")) === false) {
    echo "Could not open file $inputFile";
    die;
}

// Skip the heading row.
fgets($handle);

$subscriptionIds = [];
while (($line = fgetcsv($handle)) !== false) {
    $subscriptionIds[] = trim($line[0]);
}

fclose($handle);

// Store the ids that still require processing.
$idsNotUpdated = $subscriptionIds;

Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

$idsNotUpdated = UpdateSubscriptionPrice($idsNotUpdated, $_ENV['STRIPE_PRICE']);

if (empty($idsNotUpdated)) {
    echo "All subscriptions updated.\r\n";
} else {
    echo "Subscriptions that failed to update: \r\n";
    foreach ($idsNotUpdated as $id) {
        echo $id . "\r\n";
    }
}

// Update a list of subscriptions to a new price, return those that failed to update.
function UpdateSubscriptionPrice($subscriptionIds, $price)
{
    // Store a separate id array to avoid manipulating loop array during execution.
    $failedToUpdate = $subscriptionIds;

    foreach ($subscriptionIds as $subscriptionId) {
        try {
            $subscription = Subscription::retrieve($subscriptionId);
        } catch (\Exception $e) {
            echo 'Failed to retrieve subscription with id \'' . $subscriptionId . "'.\r\n";
            echo $e->getMessage() . "\r\n";
            // Continue to avoid attempting to update subscription not retrieved.
            continue;
        }
    
        try {
            Subscription::update($subscriptionId, [
                'cancel_at_period_end' => false,
                'proration_behavior' => 'create_prorations',
                'items' => [
                    [
                        'id' => $subscription->items->data[0]->id,
                        'price' => $price,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            echo 'Failed to update subscription with id \'' . $subscriptionId . "'.\r\n";
            echo $e->getMessage() . "\r\n";
            continue;
        }

        // No exceptions, remove to indicate successful update.
        unset($failedToUpdate[array_search($subscriptionId, $failedToUpdate)]);

        // Stripe api handles 100 read/write requests per minute.
        // We've just sent two, so delay is 1s = 1000000us / 100 = 10000 * 2 = 20000.
        // And a 20% buffer to be safe.
        // https://stripe.com/docs/rate-limits
        usleep(24000);
    }


    return $failedToUpdate;
}
