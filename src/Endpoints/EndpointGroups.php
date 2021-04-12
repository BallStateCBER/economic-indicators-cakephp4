<?php
declare(strict_types=1);

namespace App\Endpoints;

use Cake\Http\Exception\NotFoundException;

class EndpointGroups
{
    public const HOUSING = [
        'title' => 'Housing Starts',
        'cacheKey' => 'housing',
        'endpoints' => [
            'HOUST' => 'Total – new private owned',
            'HOUST1F' => '1 unit structures',
            'HOUST2F' => '2-4 unit structures',
            'HOUST5F' => '5 unit structures',
        ],
    ];

    public const VEHICLE_SALES = [
        'title' => 'Motor Vehicle Sales',
        'cacheKey' => 'vehicle_sales',
        'endpoints' => [
            'TOTALSA' => 'Total vehicle sales',
            'LAUTOSA' => 'Total autos',
            'DAUTOSAAR' => 'Total autos - Domestic autos',
            'FAUTOSAAR' => 'Total autos - Foreign autos',
            'LTRUCKSA' => 'Total light weight trucks',
            'DLTRUCKSSAAR' => 'Domestic light weight trucks',
            'FLTRUCKSSAAR' => 'Foreign light weight trucks',
            'HTRUCKSSAAR' => 'Heavy weight trucks',
        ],
    ];

    public const RETAIL_FOOD_SERVICES = [
        'title' => 'Retail and Food Services',
        'cacheKey' => 'retail',
        'endpoints' => [
            'RSAFS' => 'Advance retail sales: Retail and food services, Total',
            'RSFSXMV' => 'Advance retail sales: Retail and food services (excluding motor vehicle and parts dealers)',
            'RSXFS' => 'Advance retail sales: Retail (excluding food services)',
        ],
    ];

    public const GDP = [
        'title' => 'Gross Domestic Product',
        'cacheKey' => 'gdp',
        'endpoints' => [
            'GDP' => 'Gross Domestic Product',
            'GDPC1' => 'Real Gross Domestic Product (2012) dollars',
            'PCEC' => 'Personal consumption expenditures',
            'PCEC96' => 'Real personal consumption expenditures (2012) dollars',
        ],
    ];

    public const UNEMPLOYMENT = [
        'title' => 'Unemployment Rate - Indiana',
        'cacheKey' => 'unemployment',
        'endpoints' => ['INUR' => 'Unemployment rate (seasonally adjusted)'],
    ];

    public const EMP_BY_SECTOR = [
        'title' => 'Employment by Sector (seasonally adjusted)',
        'cacheKey' => 'employment_by_sector',
        'endpoints' => [
            'INNA' => 'Total nonfarm',
            'SMS18000001000000001' => 'Mining and logging',
            'INCONS' => 'Construction',
            'INMFG' => 'Manufacturing',
            'SMS18000003100000001' => 'Durable goods',
            'SMS18000003200000001' => 'Non-durable goods',
            'INTRADN' => 'Trade, transportation and utilities',
            'SMS18000004100000001' => 'Wholesale trade',
            'SMS18000004200000001' => 'Retail trade',
            'SMS18000004300000001' => 'Transportation, warehousing and utilities',
            'ININFO' => 'Information',
            'INFIRE' => 'Financial activities',
            'INPBSV' => 'Professional and business services',
            'INEDUH' => 'Education and health service',
            'INLEIH' => 'Leisure and hospitality',
            'INSRVO' => 'Other services',
            'INGOVT' => 'Government',
        ],
    ];

    public const EARNINGS = [
        'title' => 'Average Weekly Earnings (seasonally adjusted)',
        'cacheKey' => 'earnings',
        'endpoints' => [
            'SMU18000000500000011SA' => 'Total private',
            'SMU18000000600000011SA' => 'Goods producing',
            'SMU18000000800000011SA' => 'Private service providing',
            'SMU18000002000000011SA' => 'Construction',
            'SMU18000003000000011SA' => 'Manufacturing',
            'SMU18000004000000011SA' => 'Trade, transportation and utilities',
            'SMU18000005500000011SA' => 'Financial activities',
            'SMU18000006000000011SA' => 'Professional and business services',
            'SMU18000006500000011SA' => 'Education and health service',
            'SMU18000007000000011SA' => 'Leisure and Hospitality',
            'SMU18000008000000011SA' => 'Other services',
        ],
    ];

    public const COUNTY_UNEMPLOYMENT = [
        'cacheKey' => 'county_unemployment',
        'title' => 'Indiana County Unemployment',
        'endpoints' => [
            'INADAM1URN' => 'Adams County',
            'INALLE3URN' => 'Allen County',
            'INBART5URN' => 'Bartholomew County',
            'INBENT7URN' => 'Benton County',
            'INBLAC9URN' => 'Blackford County',
            'INBOON1URN' => 'Boone County',
            'INBROW3URN' => 'Brown County',
            'INCARR5URN' => 'Carroll County',
            'INCASS7URN' => 'Cass County',
            'INCLURN' => 'Clark County',
            'INCLAY1URN' => 'Clay County',
            'INCLIN3URN' => 'Clinton County',
            'INCRURN' => 'Crawford County',
            'INDAURN' => 'Daviess County',
            'INDEAR9URN' => 'Dearborn County',
            'INDECA1URN' => 'Decatur County',
            'INDEKA3URN' => 'DeKalb County',
            'INDELA5URN' => 'Delaware County',
            'INDUURN' => 'Dubois County',
            'INELKH0URN' => 'Elkhart County',
            'INFAYE1URN' => 'Fayette County',
            'INFLURN' => 'Floyd County',
            'INFOUN5URN' => 'Fountain County',
            'INFRAN7URN' => 'Franklin County',
            'INFULT9URN' => 'Fulton County',
            'INGIURN' => 'Gibson County',
            'INGRAN0URN' => 'Grant County',
            'INGEURN' => 'Greene County',
            'INHAMI5URN' => 'Hamilton County',
            'INHANC9URN' => 'Hancock County',
            'INHRURN' => 'Harrison County',
            'INHEND0URN' => 'Hendricks County',
            'INHENR5URN' => 'Henry County',
            'INHOWA7URN' => 'Howard County',
            'INHUNT9URN' => 'Huntington County',
            'INJAURN' => 'Jackson County',
            'INJASP3URN' => 'Jasper County',
            'INJAYC5URN' => 'Jay County',
            'INJEURN' => 'Jefferson County',
            'INJENN9URN' => 'Jennings County',
            'INJOHN5URN' => 'Johnson County',
            'INKNURN' => 'Knox County',
            'INKOSC5URN' => 'Kosciusko County',
            'INLAGR7URN' => 'LaGrange County',
            'INLAKE9URN' => 'Lake County',
            'INLAPO0URN' => 'LaPorte County',
            'INLWURN' => 'Lawrence County',
            'INMADI5URN' => 'Madison County',
            'INMARI0URN' => 'Marion County',
            'INMARS9URN' => 'Marshall County',
            'INMTURN' => 'Martin County',
            'INMIAM3URN' => 'Miami County',
            'INMONR5URN' => 'Monroe County',
            'INMONT7URN' => 'Montgomery County',
            'INMORG5URN' => 'Morgan County',
            'INNEWT1URN' => 'Newton County',
            'INNOBL3URN' => 'Noble County',
            'INOHIO5URN' => 'Ohio County',
            'INORURN' => 'Orange County',
            'INOWEN9URN' => 'Owen County',
            'INPARK1URN' => 'Parke County',
            'INPEURN' => 'Perry County',
            'INPIURN' => 'Pike County',
            'INPORT5URN' => 'Porter County',
            'INPSURN' => 'Posey County',
            'INPULA1URN' => 'Pulaski County',
            'INPUTN3URN' => 'Putnam County',
            'INRAND5URN' => 'Randolph County',
            'INRIPL7URN' => 'Ripley County',
            'INRUSH9URN' => 'Rush County',
            'INSCURN' => 'Scott County',
            'INSHEL5URN' => 'Shelby County',
            'INSPURN' => 'Spencer County',
            'INSTAR9URN' => 'Starke County',
            'INSTEU1URN' => 'Steuben County',
            'INSTJO7URN' => 'St. Joseph County',
            'INSUURN' => 'Sullivan County',
            'INSWURN' => 'Switzerland County',
            'INTIPP7URN' => 'Tippecanoe County',
            'INTIPT9URN' => 'Tipton County',
            'INUNIO1URN' => 'Union County',
            'INVAURN' => 'Vanderburgh County',
            'INVERM5URN' => 'Vermillion County',
            'INVIGO0URN' => 'Vigo County',
            'INWABA9URN' => 'Wabash County',
            'INWARR1URN' => 'Warren County',
            'INWIURN' => 'Warrick County',
            'INWSURN' => 'Washington County',
            'INWAYN0URN' => 'Wayne County',
            'INWELL9URN' => 'Wells County',
            'INWHIT1URN' => 'White County',
            'INWHIT3URN' => 'Whitley County',
        ],
    ];

    public const STATE_MANUFACTURING_EMPLOYMENT = [
        'title' => 'State Manufacturing Employment',
        'cacheKey' => 'state_manufacturing_employment',
        'endpoints' => [
            'SMU01000003000000001SA' => 'Alabama',
            'AKMFG' => 'Alaska',
            'AZMFG' => 'Arizona',
            'ARMFG' => 'Arkansas',
            'CAMFG' => 'California',
            'COMFG' => 'Colorado',
            'CTMFG' => 'Connecticut',
            'DEMFG' => 'Delaware',
            'SMU11000003000000001SA' => 'District of Columbia',
            'FLMFG' => 'Florida',
            'GAMFG' => 'Georgia',
            'HIMFG' => 'Hawaii',
            'IDMFG' => 'Idaho',
            'ILMFG' => 'Illinois',
            'INMFG' => 'Indiana',
            'IAMFG' => 'Iowa',
            'KSMFG' => 'Kansas',
            'KYMFG' => 'Kentucky',
            'LAMFG' => 'Louisiana',
            'MEMFG' => 'Maine',
            'MDMFG' => 'Maryland',
            'MAMFG' => 'Massachusetts',
            'MIMFG' => 'Michigan',
            'MNMFG' => 'Minnesota',
            'MSMFG' => 'Mississippi',
            'MOMFG' => 'Missouri',
            'MTMFG' => 'Montana',
            'NEMFG' => 'Nebraska',
            'NVMFG' => 'Nevada',
            'NHMFG' => 'New Hampshire',
            'NJMFG' => 'New Jersey',
            'NMMFG' => 'New Mexico',
            'NYMFG' => 'New York',
            'NCMFG' => 'North Carolina',
            'NDMFG' => 'North Dakota',
            'OHMFG' => 'Ohio',
            'OKMFG' => 'Oklahoma',
            'ORMFG' => 'Oregon',
            'PAMFG' => 'Pennsylvania',
            'SMS72000003000000001' => 'Puerto Rico',
            'RIMFG' => 'Rhode Island',
            'SCMFG' => 'South Carolina',
            'SDMFG' => 'South Dakota',
            'TNMFG' => 'Tennessee',
            'TXMFG' => 'Texas',
            'UTMFG' => 'Utah',
            'VTMFG' => 'Vermont',
            'VAMFG' => 'Virginia',
            'SMU78000003000000001SA' => 'Virgin Islands',
            'WAMFG' => 'Washington',
            'WVMFG' => 'West Virginia',
            'WIMFG' => 'Wisconsin',
            'WYMFG' => 'Wyoming',
        ],
    ];

    public const DURABLE_GOODS_ORDERS = [
        'title' => 'Durable Goods Orders',
        'cacheKey' => 'durable_goods_orders',
        'endpoints' => [
            'DGORDER' => 'Manufacturers\' New Orders: Durable Goods',
            'ACDGNO' => 'Manufacturers\' New Orders: Consumer Durable Goods',
            'ADXDNO' => 'Manufacturers\' New Orders: Durable Goods Excluding Defense',
            'ADXTNO' => 'Manufacturers\' New Orders: Durable Goods Excluding Transportation',
            'A34SNO' => 'Manufacturers\' New Orders: Computers & Electronic Products',
            'A34ENO' => 'Manufacturers\' New Orders: Communications Eqpt. Manufacturing, Defense',
            'A34JNO' => 'Manufacturers\' New Orders: Search & Navigation Eqpt., Defense',
            'A34KNO' => 'Manufacturers\' New Orders: Electromedical, Measuring & Control Instrument Manufacturing',
            'A34HNO' => 'Manufacturers\' New Orders: Other Electronic Component Manufacturing',
            'A34DNO' => 'Manufacturers\' New Orders: Communications Eqpt. Manufacturing, Nondefense',
            'A34INO' => 'Manufacturers\' New Orders: Search & Navigation Eqpt., Nondefense',
            'A35SNO' => 'Manufacturers\' New Orders: Electrical Eqpt., Appliances & Components',
            'A35CNO' => 'Manufacturers\' New Orders: Electrical Eqpt. Manufacturing',
            'A35ANO' => 'Manufacturers\' New Orders: Electric Lighting Eqpt. Manufacturing',
            'A35BNO' => 'Manufacturers\' New Orders: Household Appliance Manufacturing',
            'A32SNO' => 'Manufacturers\' New Orders: Fabricated Metal Products',
            'A37SNO' => 'Manufacturers\' New Orders: Furniture & Related Products',
            'A33SNO' => 'Manufacturers\' New Orders: Machinery',
            'A33CNO' => 'Manufacturers\' New Orders: Construction Machinery Manufacturing',
            'A33ENO' => 'Manufacturers\' New Orders: Industrial Machinery Manufacturing',
            'A33MNO' => 'Manufacturers\' New Orders: Material Handling Eqpt. Manufacturing',
            'A33INO' => 'Manufacturers\' New Orders: Metalworking Machinery Manufacturing',
            'A33DNO' => 'Manufacturers\' New Orders: Mining, Oil & Gas Field Machinery Manufacturing',
            'A33GNO' => 'Manufacturers\' New Orders: Photographic Eqpt. Manufacturing',
            'ATGPNO' => 'Manufacturers\' New Orders: Turbines, Generators & Other Power Transmission Eqpt.',
            'A33HNO' => 'Manufacturers\' New Orders: Ventilation, Heating, ' .
                'Air-Conditioning & Refridgeration Eqpt. Manufacturing',
            'A31SNO' => 'Manufacturers\' New Orders: Primary Metals',
            'AANMNO' => 'Manufacturers\' New Orders: Aluminum & Nonferrous Metal Products',
            'A31CNO' => 'Manufacturers\' New Orders: Ferrous Metal Foundries',
            'A31ANO' => 'Manufacturers\' New Orders: Iron & Steel Mills & Ferroalloy & Steel Product Manufacturing',
            'A36SNO' => 'Manufacturers\' New Orders: Transportation Eqpt. ',
            'ADAPNO' => 'Manufacturers\' New Orders: Defense Aircraft & Parts',
            'ABTPNO' => 'Manufacturers\' New Orders: Motor Vehicle Bodies, Trailers & Parts',
            'ANAPNO' => 'Manufacturers\' New Orders: Nondefense Aircraft & Parts',
            'A36ZNO' => 'Manufacturers\' New Orders: Ships & Boats',
            'AODGNO' => 'Manufacturers\' New Orders: All Other Durable Goods',
            'AMNMNO' => 'Manufacturers\' New Orders: Nondurable Goods',
        ],
    ];

    /**
     * Returns an array of all endpoint groups
     *
     * @return array
     */
    public static function getAll(): array
    {
        return [
            self::HOUSING,
            self::VEHICLE_SALES,
            self::RETAIL_FOOD_SERVICES,
            self::GDP,
            self::UNEMPLOYMENT,
            self::EMP_BY_SECTOR,
            self::EARNINGS,
            self::COUNTY_UNEMPLOYMENT,
            self::STATE_MANUFACTURING_EMPLOYMENT,
            self::DURABLE_GOODS_ORDERS,
        ];
    }

    /**
     * Returns an endpoint group, identified by the $groupId string
     *
     * @param string $groupId String used for accessing an endpoint group
     * @return array
     * @throws \Cake\Http\Exception\NotFoundException
     */
    public static function get(string $groupId): array
    {
        switch ($groupId) {
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
                return self::COUNTY_UNEMPLOYMENT;
            case 'manufacturing-employment':
                return self::STATE_MANUFACTURING_EMPLOYMENT;
            case 'durable-goods':
                return self::DURABLE_GOODS_ORDERS;
        }

        throw new NotFoundException('Data group ' . $groupId . ' not found');
    }
}
