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
        'group' => self::VAR_HOUSING,
        'name' => 'Total – new private owned',
        'id' => 'HOUST',
    ];
    public const HOUSING_1_UNIT = [
        'group' => self::VAR_HOUSING,
        'name' => '1 unit structures',
        'id' => 'HOUST1F',
    ];
    public const HOUSING_2_4_UNIT = [
        'group' => self::VAR_HOUSING,
        'name' => '2-4 unit structures',
        'id' => 'HOUST2F',
    ];
    public const HOUSING_5_UNIT = [
        'group' => self::VAR_HOUSING,
        'name' => '5 unit structures',
        'id' => 'HOUST5F',
    ];

    public const VAR_VEHICLE_SALES = 'Motor vehicle sales';
    public const VEHICLE_SALES_TOTAL = [
        'group' => self::VAR_VEHICLE_SALES,
        'name' => 'Total vehicle sales',
        'id' => 'TOTALSA',
    ];
    public const VEHICLE_SALES_AUTOS = [
        'group' => self::VAR_VEHICLE_SALES,
        'name' => 'Total autos',
        'id' => 'LAUTOSA',
    ];
    public const VEHICLE_SALES_AUTOS_DOMESTIC = [
        'group' => self::VAR_VEHICLE_SALES,
        'name' => 'Total autos - Domestic autos',
        'id' => 'DAUTOSAAR',
    ];
    public const VEHICLE_SALES_AUTOS_FOREIGN = [
        'group' => self::VAR_VEHICLE_SALES,
        'name' => 'Total autos - Foreign autos',
        'id' => 'FAUTOSAAR',
    ];
    public const VEHICLE_SALES_LW_TRUCKS = [
        'group' => self::VAR_VEHICLE_SALES,
        'name' => 'Total light weight trucks',
        'id' => 'LTRUCKSA',
    ];
    public const VEHICLE_SALES_LW_TRUCKS_DOMESTIC = [
        'group' => self::VAR_VEHICLE_SALES,
        'name' => 'Domestic light weight trucks',
        'id' => 'DLTRUCKSSAAR',
    ];
    public const VEHICLE_SALES_LW_TRUCKS_FOREIGN = [
        'group' => self::VAR_VEHICLE_SALES,
        'name' => 'Foreign light weight trucks',
        'id' => 'FLTRUCKSSAAR',
    ];
    public const VEHICLE_SALES_HW_TRUCKS = [
        'group' => self::VAR_VEHICLE_SALES,
        'name' => 'Heavy weight trucks',
        'id' => 'HTRUCKSSAAR',
    ];

    public const VAR_RETAIL_FOOD = 'Retail and food services';
    public const RETAIL_FOOD_TOTAL = [
        'group' => self::VAR_RETAIL_FOOD,
        'name' => 'Advance retail sales: Retail and food services, Total',
        'id' => 'RSAFS',
    ];
    public const RETAIL_FOOD_EX_DEALERS = [
        'group' => self::VAR_RETAIL_FOOD,
        'name' => 'Advance retail sales: Retail and food services (excluding motor vehicle and parts dealers)',
        'id' => 'RSFSXMV',
    ];
    public const RETAIL_FOOD_EX_FOOD = [
        'group' => self::VAR_RETAIL_FOOD,
        'name' => 'Advance retail sales: Retail (excluding food services)',
        'id' => 'RSXFS',
    ];

    public const VAR_GDP = 'Gross Domestic Product';
    public const GDP = [
        'group' => self::VAR_GDP,
        'name' => 'Gross Domestic Product',
        'id' => 'GDP',
    ];
    public const GDP_REAL = [
        'group' => self::VAR_GDP,
        'name' => 'Real Gross Domestic Product (2012) dollars',
        'id' => 'GDPC1',
    ];
    public const GDP_PERSONAL_CONSUMPTION = [
        'group' => self::VAR_GDP,
        'name' => 'Personal consumption expenditures',
        'id' => 'PCEC',
    ];
    public const GDP_PERSONAL_CONSUMPTION_REAL = [
        'group' => self::VAR_GDP,
        'name' => 'Real personal consumption expenditures (2012) dollars',
        'id' => 'PCEC96',
    ];

    public const VAR_UNEMPLOYMENT = 'Unemployment Rate - Indiana';
    public const UNEMPLOYMENT_INDIANA = [
        'group' => self::VAR_UNEMPLOYMENT,
        'name' => 'Unemployment rate (seasonally adjusted)',
        'id' => 'INUR',
    ];

    public const VAR_EMPLOYMENT_BY_SECTOR = 'Employment by Sector (seasonally adjusted)';
    public const EMP_TOTAL_NONFARM = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Total nonfarm',
        'id' => 'INNA',
    ];
    public const EMP_MINING_LOGGING = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Mining and logging',
        'id' => 'SMS18000001000000001',
    ];
    public const EMP_CONSTRUCTION = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Construction',
        'id' => 'INCONS',
    ];
    public const EMP_MANUFACTURING = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Manufacturing',
        'id' => 'INMFG',
    ];
    public const EMP_DURABLE_GOODS = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Durable goods',
        'id' => 'SMS18000003100000001',
    ];
    public const EMP_NON_DURABLE_GOODS = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Non-durable goods',
        'id' => 'SMS18000003200000001',
    ];
    public const EMP_TRADE_TRANSP_UTILITIES = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Trade, transportation and utilities',
        'id' => 'INTRADN',
    ];
    public const EMP_WHOLESALE = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Wholesale trade',
        'id' => 'SMS18000004100000001',
    ];
    public const EMP_RETAIL = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Retail trade',
        'id' => 'SMS18000004200000001',
    ];
    public const EMP_TRANSP_WH_UTILITIES = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Transportation, warehousing and utilities',
        'id' => 'SMS18000004300000001',
    ];
    public const EMP_INFORMATION = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Information',
        'id' => 'ININFO',
    ];
    public const EMP_FINANCIAL = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Financial activities',
        'id' => 'INFIRE',
    ];
    public const EMP_PROFESSIONAL = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Professional and business services',
        'id' => 'INPBSV',
    ];
    public const EMP_EDU_HEALTH = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Education and health service',
        'id' => 'INEDUH',
    ];
    public const EMP_LEISURE_HOSPITALITY = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Leisure and hospitality',
        'id' => 'INLEIH',
    ];
    public const EMP_OTHER_SERVICES = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Other services',
        'id' => 'INSRVO',
    ];
    public const EMP_GOVERNMENT = [
        'group' => self::VAR_EMPLOYMENT_BY_SECTOR,
        'name' => 'Government',
        'id' => 'INGOVT',
    ];

    public const VAR_EARNINGS = 'Average weekly earnings (seasonally adjusted)';
    public const EARNINGS_PRIVATE = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Total private',
        'id' => 'SMU18000000500000011SA',
    ];
    public const EARNINGS_GOODS = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Goods producing',
        'id' => 'SMU18000000600000011SA',
    ];
    public const EARNINGS_SERVICE = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Private service providing',
        'id' => 'SMU18000000800000011SA',
    ];
    public const EARNINGS_CONSTRUCTION = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Construction',
        'id' => 'SMU18000002000000011SA',
    ];
    public const EARNINGS_MANUFACTURING = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Manufacturing',
        'id' => 'SMU18000003000000011SA',
    ];
    public const EARNINGS_TRADE_TRANSPORT_UTILITIES = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Trade, transportation and utilities',
        'id' => 'SMU18000004000000011SA',
    ];
    public const EARNINGS_FINANCIAL = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Financial activities',
        'id' => 'SMU18000005500000011SA',
    ];
    public const EARNINGS_PROFESSIONAL = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Professional and business services',
        'id' => 'SMU18000006000000011SA',
    ];
    public const EARNINGS_EDUCATION_HEALTH = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Education and health service',
        'id' => 'SMU18000006500000011SA',
    ];
    public const EARNINGS_LEISURE = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Leisure and Hospitality',
        'id' => 'SMU18000007000000011SA',
    ];
    public const EARNINGS_OTHER = [
        'group' => self::VAR_EARNINGS,
        'name' => 'Other services',
        'id' => 'SMU18000008000000011SA',
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
