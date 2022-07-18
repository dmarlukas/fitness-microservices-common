<?php

namespace Fitness\MSCommon\Services;

use Fitness\MSCommon\Models\Environment;
use Fitness\MSCommon\Models\Subscription as SubscriptionModel;
use Illuminate\Support\Carbon;

class Subscription
{
    static public function fetchSubscriptionArray($userId): array
    {
        $environment = Environment::current();
        $subscriptionEnvironment = $environment->isProduction() ? 'production' : 'sandbox';

        $subscriptions = SubscriptionModel::query()
            ->where([
                'user_id' => $userId,
                'environment' => $subscriptionEnvironment
            ])
            ->orderBy('expiry_date', 'desc')
            ->get();

        if ($subscriptions->isEmpty()) {
            Logger("Returning empty subscription object, userId: {$userId}, env: {$subscriptionEnvironment}");
            return [
                "hasHeldSubscription" => false,
                // Returning a 1970 date here instead of null. Null would be cleaner.
                // The API spec was changed on 12th October, 2021 to make this nullable. Apps need to be updated
                // first before we can return a null here.
                "subscriptionExpiryDate" => Carbon::createFromTimestampUTC(0)->toAtomString(),
                "hasHadFreeTrial" => false,
                "autoRenews" => false,
                "lastPaymentType" => 'none'
            ];
        }

        $hasHadFreeTrial = false;

        foreach ($subscriptions as $item) {
            if ($item['is_trial_period']) {
                $hasHadFreeTrial = true;
            }
        }

        $lastSubscription = $subscriptions->first();
        $expiryDate = Carbon::parse($lastSubscription->expiry_date);
        return [
            "hasHeldSubscription" => true, // This field is deprecated.
            "subscriptionExpiryDate" => $expiryDate->toAtomString(), // null if no subscription had or an ISO 8601 date if one has.
            "hasHadFreeTrial" => $hasHadFreeTrial, // Allows apps to show Trial UI if they haven't had a free trial.
            "autoRenews" => (bool)$lastSubscription->will_auto_renew, // Have they chosen to continue subscribing when current period ends?
            "lastPaymentType" => $lastSubscription->payment_source // 'apple', 'android' or 'none'. Have they chosen to continue subscribing when current period ends?
        ];
    }
}