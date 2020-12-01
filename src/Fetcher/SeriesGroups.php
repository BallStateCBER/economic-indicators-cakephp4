<?php
declare(strict_types=1);

namespace App\Fetcher;

class SeriesGroups
{
    public const HOUSING = [
        'cacheKey' => 'housing',
        'endpoints' => [
            FredEndpoints::HOUSING_TOTAL,
            FredEndpoints::HOUSING_1_UNIT,
            FredEndpoints::HOUSING_2_4_UNIT,
            FredEndpoints::HOUSING_5_UNIT,
        ],
    ];

    public const VEHICLE_SALES = [
        'cacheKey' => 'vehicle_sales',
        'endpoints' => [
            FredEndpoints::VEHICLE_SALES_AUTOS,
            FredEndpoints::VEHICLE_SALES_AUTOS_DOMESTIC,
            FredEndpoints::VEHICLE_SALES_AUTOS_FOREIGN,
            FredEndpoints::VEHICLE_SALES_LW_TRUCKS,
            FredEndpoints::VEHICLE_SALES_LW_TRUCKS_DOMESTIC,
            FredEndpoints::VEHICLE_SALES_LW_TRUCKS_FOREIGN,
            FredEndpoints::VEHICLE_SALES_HW_TRUCKS,
        ],
    ];

    public const RETAIL_FOOD_SERVICES = [
        'cacheKey' => 'retail',
        'endpoints' => [
            FredEndpoints::RETAIL_FOOD_TOTAL,
            FredEndpoints::RETAIL_FOOD_EX_DEALERS,
            FredEndpoints::RETAIL_FOOD_EX_FOOD,
            FredEndpoints::RETAIL_FOOD_REAL_SALES,
        ],
    ];

    public const GDP = [
        'cacheKey' => 'gdp',
        'endpoints' => [
            FredEndpoints::GDP,
            FredEndpoints::GDP_REAL,
            FredEndpoints::GDP_PERSONAL_CONSUMPTION,
            FredEndpoints::GDP_PERSONAL_CONSUMPTION_REAL,
        ],
    ];

    public const UNEMPLOYMENT = [
        'cacheKey' => 'unemployment',
        'endpoints' => [FredEndpoints::UNEMPLOYMENT_INDIANA],
    ];

    public const EMP_BY_SECTOR = [
        'cacheKey' => 'employment_by_sector',
        'endpoints' => [
            FredEndpoints::EMP_TOTAL_NONFARM,
            FredEndpoints::EMP_MINING_LOGGING,
            FredEndpoints::EMP_CONSTRUCTION,
            FredEndpoints::EMP_MANUFACTURING,
            FredEndpoints::EMP_DURABLE_GOODS,
            FredEndpoints::EMP_NON_DURABLE_GOODS,
            FredEndpoints::EMP_TRADE_TRANSP_UTILITIES,
            FredEndpoints::EMP_WHOLESALE,
            FredEndpoints::EMP_RETAIL,
            FredEndpoints::EMP_TRANSP_WH_UTILITIES,
            FredEndpoints::EMP_INFORMATION,
            FredEndpoints::EMP_FINANCIAL,
            FredEndpoints::EMP_PROFESSIONAL,
            FredEndpoints::EMP_EDU_HEALTH,
            FredEndpoints::EMP_LEISURE_HOSPITALITY,
            FredEndpoints::EMP_OTHER_SERVICES,
            FredEndpoints::EMP_GOVERNMENT,
        ],
    ];

    public const ALL = [
        self::HOUSING,
        self::VEHICLE_SALES,
        self::RETAIL_FOOD_SERVICES,
        self::GDP,
        self::UNEMPLOYMENT,
        self::EMP_BY_SECTOR,
    ];
}
