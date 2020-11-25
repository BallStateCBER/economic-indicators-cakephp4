<?php
declare(strict_types=1);

namespace App\Fetcher;

use Cake\Core\Configure;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use fred_api;
use fred_api_exception;

/**
 * Class Fetcher
 *
 * Used for pulling data from external APIs
 *
 * @package App\Fetcher
 */
class Fetcher
{
    public fred_api $api;
    private array $parameters;

    /**
     * Fetcher constructor.
     *
     * @throws \Cake\Http\Exception\InternalErrorException
     */
    public function __construct()
    {
        // Used in calls to file_exists() in the FredApi library
        if (!defined('FRED_API_ROOT')) {
            define('FRED_API_ROOT', ROOT . DS . 'lib' . DS . 'FredApi' . DS);
        }

        require_once(ROOT . DS . 'lib' . DS . 'FredApi' . DS . 'fred_api.php');
        $apiKey = Configure::read('fred_api_key');
        try {
            $this->api = new fred_api($apiKey);
        } catch (fred_api_exception $e) {
            throw new InternalErrorException('Error creating FRED API object: ' . $e->getMessage());
        }
    }

    /**
     * Sets the series ID for this request
     *
     * @param string|array $series Series ID or array that contains 'seriesId' key
     * @return $this
     */
    public function setSeries($series)
    {
        if (is_array($series)) {
            if (!isset($series['seriesId'])) {
                throw new InternalErrorException('Series ID not provided');
            }
            $seriesId = $series['seriesId'];
        } else {
            $seriesId = $series;
        }

        $this->parameters['series_id'] = $seriesId;

        return $this;
    }

    /**
     * Sets the next request to only return the most recent observation
     *
     * @return $this
     */
    public function latest()
    {
        $this->parameters['sort_order'] = 'desc';
        $this->parameters['limit'] = 1;

        return $this;
    }

    /**
     * Returns information about a data series
     *
     * @param array $parameters Additional optional parameters
     * @return \SimpleXMLElement
     * @throws \fred_api_exception
     * @link https://fred.stlouisfed.org/docs/api/fred/
     */
    public function getSeries(array $parameters = [])
    {
        /** @var \fred_api_series $seriesApi */
        $seriesApi = $this->api->factory('series');
        $parameters += $this->parameters;

        return $seriesApi->get($parameters);
    }

    /**
     * Returns the observations or data values for a data series
     *
     * @param array $parameters Additional optional parameters
     * @return array
     * @throws \fred_api_exception
     * @throws \Cake\Http\Exception\NotFoundException
     * @link https://fred.stlouisfed.org/docs/api/fred/
     */
    public function getObservations(array $parameters = [])
    {
        /** @var \fred_api_series $seriesApi */
        $seriesApi = $this->api->factory('series');
        $parameters += $this->parameters;

        $response = $seriesApi->observations($parameters);
        if (!is_object($response) || !property_exists($response, 'observation')) {
            throw new NotFoundException();
        }

        $observations = (array)$response->observation;

        // Adjust for requests with limit = 1
        if (isset($observations['@attributes'])) {
            $observations = [$observations];
        }

        $retval = [];
        foreach ($observations as $observation) {
            $retval[] = [
                'date' => $observation['@attributes']['date'],
                'value' => $observation['@attributes']['value'],
            ];
        }

        return $retval;
    }

    /**
     * Sets the current request to return values as the change from one year ago
     *
     * @return $this
     */
    public function changeFromYearAgo()
    {
        $this->parameters['units'] = 'ch1';

        return $this;
    }

    /**
     * Sets the current request to return values as the percent of change from one year ago
     *
     * @return $this
     */
    public function percentChangeFromYearAgo()
    {
        $this->parameters['units'] = 'pc1';

        return $this;
    }

    /**
     * Returns an array of observations, with value, change from year ago, and percent change from year ago
     *
     * Returns FALSE if there's an error getting this data
     *
     * @param array $seriesGroup An array of series IDs or of arrays that contain the 'seriesId' key
     * @return array|false
     * @throws \fred_api_exception
     */
    public function getValuesAndChanges(array $seriesGroup)
    {
        $data = [];
        try {
            foreach ($seriesGroup as $series) {
                $this
                    ->setSeries($series)
                    ->latest();

                $data[$series['subvar']] = [
                    'value' => $series + $this->getObservations()[0],
                    'change' => $series + $this->changeFromYearAgo()->getObservations()[0],
                    'percentChange' => $series + $this->percentChangeFromYearAgo()->getObservations()[0],
                ];
            }
        } catch (NotFoundException $e) {
            return false;
        }

        return $data;
    }
}
