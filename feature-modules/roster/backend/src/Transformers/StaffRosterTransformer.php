<?php

namespace StaffRoster\StaffRosterModule\Transformers;

use League\Fractal\TransformerAbstract;
use StaffRoster\StaffRosterModule\Models\StaffRoster;

class StaffRosterTransformer extends TransformerAbstract
{
    public function transform(StaffRoster $roster): array
    {
        $staff = $roster->staff;
        $site = null;
        if (config('roster.enable_sites')) {
            $site = $roster->site;
        }

        return [
            'id' => $roster->id,
            'staff_roster_id' => $roster->id,
            'staff_id' => $roster->staff_id,
            'site_id' => $roster->site_id,

            // Staff information
            'staff_name' => $staff ? $staff->fname . ' ' . $staff->sname : null,
            'full_name' => $staff ? trim($staff->fname . ' ' . $staff->sname) : null,
            'fname' => $staff ? $staff->fname : null,
            'sname' => $staff ? $staff->sname : null,

            // Site information
            'site_name' => $site ? $site->label : null,
            'site_label' => $site ? $site->label : null,

            // Roster times
            'rostered_start' => $roster->rostered_start ? $roster->rostered_start->toISOString() : null,
            'rostered_end' => $roster->rostered_end ? $roster->rostered_end->toISOString() : null,
            'rostered_start_date' => $roster->rostered_start ? $roster->rostered_start->format('Y-m-d') : null,
            'rostered_end_date' => $roster->rostered_end ? $roster->rostered_end->format('Y-m-d') : null,
            'rostered_start_time' => $roster->rostered_start ? $roster->rostered_start->format('H:i:s') : null,
            'rostered_end_time' => $roster->rostered_end ? $roster->rostered_end->format('H:i:s') : null,

            // Rates
            'hourlyrate' => $roster->hourlyrate,
            'shiftrate' => $roster->shiftrate,

            // Absence information
            'absent_notice' => $roster->absent_notice,
            'absence_noted_by' => $roster->absence_noted_by,
            'absent_notes' => $roster->absent_notes,
            'absent' => $roster->is_absent,

            // Timestamps
            'created_at' => $roster->created_at ? $roster->created_at->toISOString() : null,
            'updated_at' => $roster->updated_at ? $roster->updated_at->toISOString() : null,
            'deleted_at' => $roster->deleted_at ? $roster->deleted_at->toISOString() : null,
        ];
    }
}
