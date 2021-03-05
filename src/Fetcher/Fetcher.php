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
     * @var int The number of times to retry a failing API call after the first attempt
     */
    private int $apiRetryCount = 2;

    /**
     * @var float Seconds to wait between each API call
     */
    private float $rateThrottle = 1;

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
     * Pauses execution for $this->rateThrottle seconds
     *
     * @return void
     */
    private function throttle()
    {
        $microseconds = (int)($this->rateThrottle * 1000000);
        usleep($microseconds);
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
        $this->throttle();

        for ($attempts = 1 + $this->apiRetryCount; $attempts > 0; $attempts--) {
            $finalAttempt = $attempts == 1;
            try {
                $response = $seriesApi->get($parameters);
                if (property_exists($response, 'series')) {
                    return $response;
                }
                if ($finalAttempt) {
                    throw new NotFoundException('Series data not found');
                } else {
                    $this->consoleOutput('Failed, retrying', true);
                    continue;
                }
            } catch (fred_api_exception $e) {
                if ($finalAttempt) {
                    throw $e;
                } else {
                    $this->consoleOutput('Failed, retrying', true);
                    continue;
                }
            }
        }
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
        for ($attempts = 1 + $this->apiRetryCount; $attempts > 0; $attempts++) {
            $finalAttempt = $attempts == 1;
            /** @var \fred_api_series $seriesApi */
            $seriesApi = $this->api->factory('series');
            $parameters += $this->parameters;
            $parameters['file_type'] = 'json';

            $this->throttle();
            $response = $seriesApi->observations($parameters);
            $response = json_decode($response);
            $responseIsValid = json_last_error() != JSON_ERROR_NONE;
            $responseIsValid = $responseIsValid && !is_object($response) || !property_exists($response, 'observations');
            if ($responseIsValid) {
                if ($finalAttempt) {
                    throw new NotFoundException();
                } else {
                    $this->consoleOutput('Failed, retrying', true);
                    continue;
                }
            }

            $observations = $response->observations;

            // Adjust for requests with limit = 1
            if (isset($observations['@attributes'])) {
                $observations = [$observations];
            }

            $retval = [];
            foreach ($observations as $observation) {
                $retval[] = [
                    'date' => $observation->date,
                    'value' => $observation->value,
                ];
            }

            return $retval;
        }

        return [];
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
        // Attempt to pull from cache
        $cacheKey = $seriesGroup['cacheKey'];
        $cachedData = Cache::read($cacheKey, 'observations');
        if (!$forceOverwrite && $cachedData) {
            $this->consoleOutput('Results are still cached');

            return $cachedData;
        }

        // Fetch from API
        $data = [
            /* Stores the last_updated date for the first series in this group
             * Assumes that all series in this group are updated at roughly the same time */
            'updated' => null,
            'series' => [],
        ];
        $this->consoleOutput('Retrieving...');
        foreach ($seriesGroup['endpoints'] as $series) {
            $this->setSeries($series);
            $this->consoleOutput(sprintf('%s > %s metadata', $series['var'], $series['subvar']));
            $seriesResponse = $this->getSeries();
            $seriesMeta = (array)($seriesResponse->series);
            if (!$data['updated']) {
                $data['updated'] = $seriesMeta['@attributes']['last_updated'];
            }
            $this->parameters['sort_order'] = 'asc';

            $this->consoleOutput('Value');
            $values = $this->getObservations();
            $this->consoleOutput('Change');
            $changes = (clone $this)->changeFromYearAgo()->getObservations();
            $this->consoleOutput('Percent change');
            $percentChanges = (clone $this)->percentChangeFromYearAgo()->getObservations();

            $data['series'][$series['subvar']] = $series + [
                'units' => $seriesMeta['@attributes']['units'],
                'frequency' => $seriesMeta['@attributes']['frequency'],
                'value' => $values,
                'change' => $changes,
                'percentChange' => $percentChanges,
            ];
        }

        // Cache and return
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
    private function consoleOutput(string $msg, $error = false)
    {
        if (!$this->io) {
            return;
        }
        if ($error) {
            $this->io->error(" - $msg");

            return;
        }
        $this->io->out(" - $msg");
    }
}
