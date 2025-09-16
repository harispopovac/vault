<?php

namespace App\Transformers\Me;

use App\Models\Subscription;
use League\Fractal\TransformerAbstract;

class MeSubscriptionsTransformer extends TransformerAbstract
{
    public function transform(Subscription $subscription): array
    {
        return [
            'status' =>  $subscription->status,
            'items' =>  fractal($subscription->items, new MeSubscriptionItemsTransformer())->toArray()['data'],
        ];
    }
}
