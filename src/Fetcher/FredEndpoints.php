<?php
declare(strict_types=1);

namespace App\Fetcher;

/**
 * Class FredEndpoints
 *
 * This class stores constants for each FRED API endpoint
 *
 * @package App\Fetcher
 */
class FredEndpoints
{
    public const VAR_HOUSING = 'Housing starts';
    public const HOUSING_TOTAL = [
        'var' => self::VAR_HOUSING,
        'subvar' => 'Total – new private owned',
        'seriesId' => 'HOUST',
    ];
    public const HOUSING_1_UNIT = [
        'var' => self::VAR_HOUSING,
        'subvar' => '1 unit structures',
        'seriesId' => 'HOUST1F',
    ];
    public const HOUSING_2_4_UNIT = [
        'var' => self::VAR_HOUSING,
        'subvar' => '2-4 unit structures',
        'seriesId' => 'HOUST2F',
    ];
    public const HOUSING_5_UNIT = [
        'var' => self::VAR_HOUSING,
        'subvar' => '5 unit structures',
        'seriesId' => 'HOUST5F',
    ];

    public const VAR_VEHICLE_SALES = 'Motor vehicle sales';
    public const VEHICLE_SALES_TOTAL = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Total vehicle sales',
        'seriesId' => 'TOTALSA',
    ];
    public const VEHICLE_SALES_AUTOS = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Total autos',
        'seriesId' => 'LAUTOSA',
    ];
    public const VEHICLE_SALES_AUTOS_DOMESTIC = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Total autos - Domestic autos',
        'seriesId' => 'DAUTOSAAR',
    ];
    public const VEHICLE_SALES_AUTOS_FOREIGN = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Total autos - Foreign autos',
        'seriesId' => 'FAUTOSAAR',
    ];
    public const VEHICLE_SALES_LW_TRUCKS = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Total light weight trucks',
        'seriesId' => 'LTRUCKSA',
    ];
    public const VEHICLE_SALES_LW_TRUCKS_DOMESTIC = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Domestic light weight trucks',
        'seriesId' => 'DLTRUCKSSAAR',
    ];
    public const VEHICLE_SALES_LW_TRUCKS_FOREIGN = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Foreign light weight trucks',
        'seriesId' => 'FLTRUCKSSAAR',
    ];
    public const VEHICLE_SALES_HW_TRUCKS = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Heavy weight trucks',
        'seriesId' => 'HTRUCKSSAAR',
    ];

    public const VAR_RETAIL_FOOD = 'Retail and food services';
    public const RETAIL_FOOD_TOTAL = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Advance retail sales: Retail and food services, Total',
        'seriesId' => 'RSAFS',
    ];
    public const RETAIL_FOOD_EX_DEALERS = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Advance retail sales: Retail and food services (excluding motor vehicle and parts dealers)',
        'seriesId' => 'RSFSXMV',
    ];
    public const RETAIL_FOOD_EX_FOOD = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Advance retail sales: Retail (excluding food services)',
        'seriesId' => 'RSXFS',
    ];
    public const RETAIL_FOOD_REAL_SALES = [
        'var' => self::VAR_VEHICLE_SALES,
        'subvar' => 'Advance real retail and food services sales',
        'seriesId' => 'TOTALSA',
    ];
}
