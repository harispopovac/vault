<?php

namespace App\Transformers\Me;

use App\Models\SubscriptionItem;
use League\Fractal\TransformerAbstract;

class MeSubscriptionItemsTransformer extends TransformerAbstract
{
    public function transform(SubscriptionItem $subscriptionItem): array
    {
        return [
            'status' =>  $subscriptionItem->status,
            'product_type' =>  $subscriptionItem->product_type,
        ];
    }
}
