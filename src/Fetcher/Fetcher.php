<?php
declare(strict_types=1);

namespace App\Fetcher;

use Cake\Cache\Cache;
use Cake\Console\ConsoleIo;
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
 * @property array $parameters
 * @property \Cake\Console\ConsoleIo|null $io
 * @property \fred_api $api
 * @package App\Fetcher
 */
class Fetcher
{
    public fred_api $api;
    private array $parameters;
    private ?ConsoleIo $io;

    /**
     * Fetcher constructor.
     *
     * @param \Cake\Console\ConsoleIo|null $io Optional console output
     * @throws \Cake\Http\Exception\InternalErrorException
     */
    public function __construct(?ConsoleIo $io = null)
    {
        $this->io = $io;

        // Used in calls to file_exists() in the FredApi library
        if (!defined('FRED_API_ROOT')) {
            define('FRED_API_ROOT', ROOT . DS . 'lib' . DS . 'FredApi' . DS);
        }

        require_once ROOT . DS . 'lib' . DS . 'FredApi' . DS . 'fred_api.php';
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
     * @throws \Cake\Http\Exception\NotFoundException
     * @throws \fred_api_exception
     */
    public function getValuesAndChanges(array $seriesGroup)
    {
        $data = [];
        $this->consoleOutput('Retrieving...');
        foreach ($seriesGroup as $series) {
            $this->setSeries($series);
            $this->consoleOutput(sprintf('%s > %s metadata', $series['var'], $series['subvar']));
            $seriesResponse = $this->getSeries();
            if (!property_exists($seriesResponse, 'series')) {
                throw new NotFoundException(sprintf(
                    'Series data not found for %s >%s',
                    $series['var'],
                    $series['subvar']
                ));
            }
            $seriesMeta = (array)($seriesResponse->series);
            $this->latest();

            $this->consoleOutput('Value');
            $value = $series + $this->getObservations()[0];
            $this->consoleOutput('Change');
            $change = $series + (clone $this)->changeFromYearAgo()->getObservations()[0];
            $this->consoleOutput('Percent change');
            $percentChange = $series + (clone $this)->percentChangeFromYearAgo()->getObservations()[0];

            $data[$series['subvar']] = [
                'units' => $seriesMeta['@attributes']['units'],
                'frequency' => $seriesMeta['@attributes']['frequency'],
                'value' => $value,
                'change' => $change,
                'percentChange' => $percentChange,
            ];
        }

        return $data;
    }

    /**
     * A cache wrapper for getValuesAndChanges
     *
     * @param array $seriesGroup Series information
     * @param bool $forceOverwrite Set to TRUE to overwrite the cached value
     * @return array|bool
     * @throws \Cake\Http\Exception\NotFoundException
     * @throws \fred_api_exception
     */
    public function getCachedValuesAndChanges(array $seriesGroup, bool $forceOverwrite = false)
    {
        $cacheKey = $seriesGroup['cacheKey'];
        $data = Cache::read($cacheKey, 'observations');
        if (!$forceOverwrite && $data) {
            $this->consoleOutput('Results are still cached');

            return $data;
        }

        $data = $this->getValuesAndChanges($seriesGroup['endpoints']);
        if ($data) {
            Cache::write($cacheKey, $data, 'observations');
            $this->consoleOutput('Wrote results to cache');

            return $data;
        }

        $this->consoleOutput('No results could be retrieved', true);

        return false;
    }

    /**
     * Displays a console message if this is being run in the console
     *
     * @param string $msg Message
     * @param bool $error TRUE if the message is an error message
     * @return void
     */
    private function consoleOutput($msg, $error = false)
    {
        if (!$this->io) {
            return;
        }
        if ($error) {
            $this->io->error(" - $msg");
        }
        $this->io->out(" - $msg");
    }
}
