<?php
declare(strict_types=1);

namespace App\Fetcher;

use Cake\Http\Exception\NotFoundException;

class EndpointGroups
{
    public const HOUSING = [
        'title' => FredEndpoints::VAR_HOUSING,
        'cacheKey' => 'housing',
        'endpoints' => [
            FredEndpoints::HOUSING_TOTAL,
            FredEndpoints::HOUSING_1_UNIT,
            FredEndpoints::HOUSING_2_4_UNIT,
            FredEndpoints::HOUSING_5_UNIT,
        ],
    ];

    public const VEHICLE_SALES = [
        'title' => FredEndpoints::VAR_VEHICLE_SALES,
        'cacheKey' => 'vehicle_sales',
        'endpoints' => [
            FredEndpoints::VEHICLE_SALES_TOTAL,
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
        'title' => FredEndpoints::VAR_RETAIL_FOOD,
        'cacheKey' => 'retail',
        'endpoints' => [
            FredEndpoints::RETAIL_FOOD_TOTAL,
            FredEndpoints::RETAIL_FOOD_EX_DEALERS,
            FredEndpoints::RETAIL_FOOD_EX_FOOD,
        ],
    ];

    public const GDP = [
        'title' => FredEndpoints::VAR_GDP,
        'cacheKey' => 'gdp',
        'endpoints' => [
            FredEndpoints::GDP,
            FredEndpoints::GDP_REAL,
            FredEndpoints::GDP_PERSONAL_CONSUMPTION,
            FredEndpoints::GDP_PERSONAL_CONSUMPTION_REAL,
        ],
    ];

    public const UNEMPLOYMENT = [
        'title' => FredEndpoints::VAR_UNEMPLOYMENT,
        'cacheKey' => 'unemployment',
        'endpoints' => [FredEndpoints::UNEMPLOYMENT_INDIANA],
    ];

    public const EMP_BY_SECTOR = [
        'title' => FredEndpoints::VAR_EMPLOYMENT_BY_SECTOR,
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

    public const EARNINGS = [
        'title' => FredEndpoints::VAR_EARNINGS,
        'cacheKey' => 'earnings',
        'endpoints' => [
            FredEndpoints::EARNINGS_PRIVATE,
            FredEndpoints::EARNINGS_GOODS,
            FredEndpoints::EARNINGS_SERVICE,
            FredEndpoints::EARNINGS_CONSTRUCTION,
            FredEndpoints::EARNINGS_MANUFACTURING,
            FredEndpoints::EARNINGS_TRADE_TRANSPORT_UTILITIES,
            FredEndpoints::EARNINGS_FINANCIAL,
            FredEndpoints::EARNINGS_PROFESSIONAL,
            FredEndpoints::EARNINGS_EDUCATION_HEALTH,
            FredEndpoints::EARNINGS_LEISURE,
            FredEndpoints::EARNINGS_OTHER,
        ],
    ];

    /**
     * Returns the county unemployment group of endpoints
     *
     * @return array
     */
    public static function getCountyUnemployment()
    {
        $endpoints = [];
        foreach (FredEndpoints::COUNTY_UNEMPLOYMENT_IDS as $countyName => $seriesId) {
            $endpoints[] = [
                'group' => FredEndpoints::VAR_COUNTY_UNEMPLOYMENT,
                'name' => $countyName,
                'id' => $seriesId,
            ];
        }

        return [
            'cacheKey' => 'county_unemployment',
            'title' => 'Indiana County Unemployment',
            'endpoints' => $endpoints,
        ];
    }

    /**
     * Returns the state manufacturing group of endpoints
     *
     * @return array
     */
    public static function getStateManufacturing()
    {
        $endpoints = [];
        $states = array_flip(FredEndpoints::STATES);
        foreach (FredEndpoints::STATE_MANUFACTURING_EMPLOYMENT_IDS as $stateAbbreviation => $seriesId) {
            $endpoints[] = [
                'group' => FredEndpoints::VAR_STATE_MANUFACTURING_EMPLOYMENT,
                'name' => $states[$stateAbbreviation],
                'id' => $seriesId,
            ];
        }

        return [
            'cacheKey' => 'state_manufacturing_employment',
            'endpoints' => $endpoints,
            'title' => 'State Manufacturing Employment',
        ];
    }

    /**
     * Returns an array of all endpoint groups
     *
     * @return array
     */
    public static function getAll()
    {
        return [
            self::HOUSING,
            self::VEHICLE_SALES,
            self::RETAIL_FOOD_SERVICES,
            self::GDP,
            self::UNEMPLOYMENT,
            self::EMP_BY_SECTOR,
            self::EARNINGS,
            self::getCountyUnemployment(),
            self::getStateManufacturing(),
        ];
    }

    /**
     * Returns an endpoint group, identified by the $groupName string
     *
     * @param string $groupName String used for accessing an endpoint group
     * @return array
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public static function get(string $groupName): array
    {
        switch ($groupName) {
            case 'housing':
                return self::HOUSING;
            case 'vehicle-sales':
                return self::VEHICLE_SALES;
            case 'retail-food-services':
                return self::RETAIL_FOOD_SERVICES;
            case 'gdp':
                return self::GDP;
            case 'unemployment':
                return self::UNEMPLOYMENT;
            case 'employment-by-sector':
                return self::EMP_BY_SECTOR;
            case 'earnings':
                return self::EARNINGS;
            case 'county-unemployment':
                return self::getCountyUnemployment();
            case 'manufacturing-employment':
                return self::getStateManufacturing();
        }

        throw new NotFoundException('Data group ' . $groupName . ' not found');
    }
}
