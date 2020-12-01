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

    public const VEHICLE_SALES = [
        FredEndpoints::VEHICLE_SALES_AUTOS,
        FredEndpoints::VEHICLE_SALES_AUTOS_DOMESTIC,
        FredEndpoints::VEHICLE_SALES_AUTOS_FOREIGN,
        FredEndpoints::VEHICLE_SALES_LW_TRUCKS,
        FredEndpoints::VEHICLE_SALES_LW_TRUCKS_DOMESTIC,
        FredEndpoints::VEHICLE_SALES_LW_TRUCKS_FOREIGN,
        FredEndpoints::VEHICLE_SALES_HW_TRUCKS
    ];
}