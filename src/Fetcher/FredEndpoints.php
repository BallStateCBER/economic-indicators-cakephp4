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

    public const VAR_EARNINGS = 'Average weekly earnings (seasonally adjusted)';
    public const EARNINGS_PRIVATE = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Total private',
        'seriesId' => 'SMU18000000500000011SA',
    ];
    public const EARNINGS_GOODS = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Goods producing',
        'seriesId' => 'SMU18000000600000011SA',
    ];
    public const EARNINGS_SERVICE = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Private service providing',
        'seriesId' => 'SMU18000000800000011SA',
    ];
    public const EARNINGS_CONSTRUCTION = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Construction',
        'seriesId' => 'SMU18000002000000011SA',
    ];
    public const EARNINGS_MANUFACTURING = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Manufacturing',
        'seriesId' => 'SMU18000003000000011SA',
    ];
    public const EARNINGS_TRADE_TRANSPORT_UTILITIES = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Trade, transportation and utilities',
        'seriesId' => 'SMU18000004000000011SA',
    ];
    public const EARNINGS_FINANCIAL = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Financial activities',
        'seriesId' => 'SMU18000005500000011SA',
    ];
    public const EARNINGS_PROFESSIONAL = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Professional and business services',
        'seriesId' => 'SMU18000006000000011SA',
    ];
    public const EARNINGS_EDUCATION_HEALTH = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Education and health service',
        'seriesId' => 'SMU18000006500000011SA',
    ];
    public const EARNINGS_LEISURE = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Leisure and Hospitality',
        'seriesId' => 'SMU18000007000000011SA',
    ];
    public const EARNINGS_OTHER = [
        'var' => self::VAR_EARNINGS,
        'subvar' => 'Other services',
        'seriesId' => 'SMU18000008000000011SA',
    ];

    public const VAR_COUNTY_UNEMPLOYMENT = 'County unemployment rate';
    public const COUNTY_UNEMPLOYMENT_IDS = [
        'Adams County' => 'INADAM1URN',
        'Allen County' => 'INALLE3URN',
        'Bartholomew County' => 'INBART5URN',
        'Benton County' => 'INBENT7URN',
        'Blackford County' => 'INBLAC9URN',
        'Boone County' => 'INBOON1URN',
        'Brown County' => 'INBROW3URN',
        'Carroll County' => 'INCARR5URN',
        'Cass County' => 'INCASS7URN',
        'Clark County' => 'INCLURN',
        'Clay County' => 'INCLAY1URN',
        'Clinton County' => 'INCLIN3URN',
        'Crawford County' => 'INCRURN',
        'Daviess County' => 'INDAURN',
        'Dearborn County' => 'INDEAR9URN',
        'Decatur County' => 'INDECA1URN',
        'DeKalb County' => 'INDEKA3URN',
        'Delaware County' => 'INDELA5URN',
        'Dubois County' => 'INDUURN',
        'Elkhart County' => 'INELKH0URN',
        'Fayette County' => 'INFAYE1URN',
        'Floyd County' => 'INFLURN',
        'Fountain County' => 'INFOUN5URN',
        'Franklin County' => 'INFRAN7URN',
        'Fulton County' => 'INFULT9URN',
        'Gibson County' => 'INGIURN',
        'Grant County' => 'INGRAN0URN',
        'Greene County' => 'INGEURN',
        'Hamilton County' => 'INHAMI5URN',
        'Hancock County' => 'INHANC9URN',
        'Harrison County' => 'INHRURN',
        'Hendricks County' => 'INHEND0URN',
        'Henry County' => 'INHENR5URN',
        'Howard County' => 'INHOWA7URN',
        'Huntington County' => 'INHUNT9URN',
        'Jackson County' => 'INJAURN',
        'Jasper County' => 'INJASP3URN',
        'Jay County' => 'INJAYC5URN',
        'Jefferson County' => 'INJEURN',
        'Jennings County' => 'INJENN9URN',
        'Johnson County' => 'INJOHN5URN',
        'Knox County' => 'INKNURN',
        'Kosciusko County' => 'INKOSC5URN',
        'LaGrange County' => 'INLAGR7URN',
        'Lake County' => 'INLAKE9URN',
        'LaPorte County' => 'INLAPO0URN',
        'Lawrence County' => 'INLWURN',
        'Madison County' => 'INMADI5URN',
        'Marion County' => 'INMARI0URN',
        'Marshall County' => 'INMARS9URN',
        'Martin County' => 'INMTURN',
        'Miami County' => 'INMIAM3URN',
        'Monroe County' => 'INMONR5URN',
        'Montgomery County' => 'INMONT7URN',
        'Morgan County' => 'INMORG5URN',
        'Newton County' => 'INNEWT1URN',
        'Noble County' => 'INNOBL3URN',
        'Ohio County' => 'INOHIO5URN',
        'Orange County' => 'INORURN',
        'Owen County' => 'INOWEN9URN',
        'Parke County' => 'INPARK1URN',
        'Perry County' => 'INPEURN',
        'Pike County' => 'INPIURN',
        'Porter County' => 'INPORT5URN',
        'Posey County' => 'INPSURN',
        'Pulaski County' => 'INPULA1URN',
        'Putnam County' => 'INPUTN3URN',
        'Randolph County' => 'INRAND5URN',
        'Ripley County' => 'INRIPL7URN',
        'Rush County' => 'INRUSH9URN',
        'Scott County' => 'INSCURN',
        'Shelby County' => 'INSHEL5URN',
        'Spencer County' => 'INSPURN',
        'Starke County' => 'INSTAR9URN',
        'Steuben County' => 'INSTEU1URN',
        'St. Joseph County' => 'INSTJO7URN',
        'Sullivan County' => 'INSUURN',
        'Switzerland County' => 'INSWURN',
        'Tippecanoe County' => 'INTIPP7URN',
        'Tipton County' => 'INTIPT9URN',
        'Union County' => 'INUNIO1URN',
        'Vanderburgh County' => 'INVAURN',
        'Vermillion County' => 'INVERM5URN',
        'Vigo County' => 'INVIGO0URN',
        'Wabash County' => 'INWABA9URN',
        'Warren County' => 'INWARR1URN',
        'Warrick County' => 'INWIURN',
        'Washington County' => 'INWSURN',
        'Wayne County' => 'INWAYN0URN',
        'Wells County' => 'INWELL9URN',
        'White County' => 'INWHIT1URN',
        'Whitley County' => 'INWHIT3URN',
    ];
}
