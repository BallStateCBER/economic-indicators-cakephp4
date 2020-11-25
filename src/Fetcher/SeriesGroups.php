<?php
declare(strict_types=1);

namespace App\Fetcher;

class SeriesGroups
{
    public const HOUSING = [
        FredEndpoints::HOUSING_TOTAL,
        FredEndpoints::HOUSING_1_UNIT,
        FredEndpoints::HOUSING_2_4_UNIT,
        FredEndpoints::HOUSING_5_UNIT,
    ];
}
