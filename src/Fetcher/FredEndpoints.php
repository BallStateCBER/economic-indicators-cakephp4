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
        'var' => self::VAR_RETAIL_FOOD,
        'subvar' => 'Advance retail sales: Retail and food services, Total',
        'seriesId' => 'RSAFS',
    ];
    public const RETAIL_FOOD_EX_DEALERS = [
        'var' => self::VAR_RETAIL_FOOD,
        'subvar' => 'Advance retail sales: Retail and food services (excluding motor vehicle and parts dealers)',
        'seriesId' => 'RSFSXMV',
    ];
    public const RETAIL_FOOD_EX_FOOD = [
        'var' => self::VAR_RETAIL_FOOD,
        'subvar' => 'Advance retail sales: Retail (excluding food services)',
        'seriesId' => 'RSXFS',
    ];
    public const RETAIL_FOOD_REAL_SALES = [
        'var' => self::VAR_RETAIL_FOOD,
        'subvar' => 'Advance real retail and food services sales',
        'seriesId' => 'TOTALSA',
    ];

    public const VAR_GDP = 'Gross Domestic Product';
    public const GDP = [
        'var' => self::VAR_GDP,
        'subvar' => 'Gross Domestic Product',
        'seriesId' => 'GDP',
    ];
    public const GDP_REAL = [
        'var' => self::VAR_GDP,
        'subvar' => 'Real Gross Domestic Product (2012) dollars',
        'seriesId' => 'GDPC1',
    ];
    public const GDP_PERSONAL_CONSUMPTION = [
        'var' => self::VAR_GDP,
        'subvar' => 'Personal consumption expenditures',
        'seriesId' => 'PCEC',
    ];
    public const GDP_PERSONAL_CONSUMPTION_REAL = [
        'var' => self::VAR_GDP,
        'subvar' => 'Real personal consumption expenditures (2012) dollars',
        'seriesId' => 'PCEC96',
    ];

    public const VAR_UNEMPLOYMENT = 'Unemployment Rate - Indiana';
    public const UNEMPLOYMENT_INDIANA = [
        'var' => self::VAR_UNEMPLOYMENT,
        'subvar' => 'Unemployment rate (seasonally adjusted)',
        'seriesId' => 'INUR',
    ];

    public const VAR_EMPLOYMENT_BY_SECTOR = 'Employment by Sector (seasonally adjusted)';
    public const EMP_TOTAL_NONFARM = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Total nonfarm',
        'seriesId' => 'INNA',
    ];
    public const EMP_MINING_LOGGING = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Mining and logging',
        'seriesId' => 'SMS18000001000000001',
    ];
    public const EMP_CONSTRUCTION = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Construction',
        'seriesId' => 'INCONS',
    ];
    public const EMP_MANUFACTURING = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Manufacturing',
        'seriesId' => 'INMFG',
    ];
    public const EMP_DURABLE_GOODS = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Durable goods',
        'seriesId' => 'SMS18000003100000001',
    ];
    public const EMP_NON_DURABLE_GOODS = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Non-durable goods',
        'seriesId' => 'SMS18000003200000001',
    ];
    public const EMP_TRADE_TRANSP_UTILITIES = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Trade, transportation and utilities',
        'seriesId' => 'INTRADN',
    ];
    public const EMP_WHOLESALE = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Wholesale trade',
        'seriesId' => 'SMS18000004100000001',
    ];
    public const EMP_RETAIL = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Retail trade',
        'seriesId' => 'SMS18000004200000001',
    ];
    public const EMP_TRANSP_WH_UTILITIES = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Transportation, warehousing and utilities',
        'seriesId' => 'SMS18000004300000001',
    ];
    public const EMP_INFORMATION = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Information',
        'seriesId' => 'ININFO',
    ];
    public const EMP_FINANCIAL = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Financial activities',
        'seriesId' => 'INFIRE',
    ];
    public const EMP_PROFESSIONAL = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Professional and business services',
        'seriesId' => 'INPBSV',
    ];
    public const EMP_EDU_HEALTH = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Education and health service',
        'seriesId' => 'INEDUH',
    ];
    public const EMP_LEISURE_HOSPITALITY = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Leisure and hospitality',
        'seriesId' => 'INLEIH',
    ];
    public const EMP_OTHER_SERVICES = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Other services',
        'seriesId' => 'INSRVO',
    ];
    public const EMP_GOVERNMENT = [
        'var' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'subvar' => 'Government',
        'seriesId' => 'INGOVT',
    ];
}
