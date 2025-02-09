<?php

namespace Vod\Vod;

use Vod\Vod\Types\VAny;

class Any extends Vod
{
    public static function schema(): VAny
    {
        return v()->any();
    }
}
