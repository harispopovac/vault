<?php

namespace App\Helpers;

use DateTime;
use DateTimeZone;

class Timezone
{
    public function toUTC($datetime, $timezone): ?DateTime
    {
        if (!$datetime) {
            return null;
        }
        return (new DateTime($datetime, new DateTimeZone($timezone)))->setTimezone(new DateTimeZone('UTC'));
    }

    public function convert($datetime, $from_timezone, $to_timezone, $format = 'Y-m-d H:i:s'): ?string
    {
        if (!$datetime) {
            return null;
        }
        return (new DateTime($datetime, new DateTimeZone($from_timezone)))->setTimezone(new DateTimeZone($to_timezone))->format($format);
    }
}
