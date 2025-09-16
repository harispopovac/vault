<?php

namespace Sites\SitesModule\Transformers;

use League\Fractal\TransformerAbstract;
use Sites\SitesModule\Models\Site;

class SiteTransformer extends TransformerAbstract
{
    public function transform(Site $site): array
    {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'label' => $site->label,
            'display_name' => $site->display_name,
            'phone' => $site->phone,
            'email' => $site->email,
            'fax' => $site->fax,
            'timezone' => $site->timezone,
            'currency' => $site->currency,
            'status' => $site->status,
            'organisation_id' => $site->organisation_id,
            'maintenance_team' => $site->maintenance_team,
            'truck_booking_target' => $site->truck_booking_target,
            'lolf_booking_target' => $site->lolf_booking_target,
            'force_timeslot_use' => $site->force_timeslot_use,

            // Address fields (for backward compatibility)
            'address_id' => $site->address_id,
            'address_1' => $site->address_1,
            'address_2' => $site->address_2,
            'suburbcity' => $site->suburbcity,
            'postcode' => $site->postcode,
            'stateprov' => $site->stateprov,
            'country' => $site->country,

            // Relationship data
            'address' => $site->address ? [
                'id' => $site->address->id,
                'label' => $site->address->label,
                'address' => $site->address->address,
                'address_1' => $site->address->address_1,
                'address_2' => $site->address->address_2,
                'suburbcity' => $site->address->suburbcity,
                'postcode' => $site->address->postcode,
                'stateprov' => $site->address->stateprov,
                'country' => $site->address->country,
                'lat' => $site->address->lat,
                'lng' => $site->address->lng,
            ] : null,

            'organisation' => $site->organisation ? [
                'id' => $site->organisation->id,
                'name' => $site->organisation->name,
            ] : null,

            'timezone_info' => $site->timezone ? [
                'id' => $site->timezone->id,
                'name' => $site->timezone->name,
            ] : null,

            'managers' => $site->managers ? $site->managers->map(function ($manager) {
                return [
                    'id' => $manager->id,
                    'fname' => $manager->fname,
                    'sname' => $manager->sname,
                    'email' => $manager->email,
                    'full_name' => trim($manager->fname . ' ' . $manager->sname),
                ];
            })->toArray() : [],

            // Counts
            'buildings_count' => $site->buildings ? $site->buildings->count() : 0,
            'rooms_count' => $site->rooms ? $site->rooms->count() : 0,

            // Timestamps
            'created_at' => $site->created_at ? $site->created_at->toISOString() : null,
            'updated_at' => $site->updated_at ? $site->updated_at->toISOString() : null,
            'deleted_at' => $site->deleted_at ? $site->deleted_at->toISOString() : null,
        ];
    }
}
