<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use DataCenter\Command\AppCommand as DataCenterCommand;
use fred_api;
use fred_api_exception;

/**
 * App command parent class
 *
 * @property \Cake\Console\ConsoleIo $io
 * @property \Cake\Shell\Helper\ProgressHelper $progress
 * @property \fred_api $api
 */
abstract class AppCommand extends DataCenterCommand
{
    protected fred_api $api;

    /**
     * @var int The number of times to retry a failing API call after the first attempt
     */
    protected int $apiRetryCount = 2;

    /**
     * @var float Seconds to wait between each API call
     */
    protected float $rateThrottle = 1;

    /**
     * UpdateStatsCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Used in calls to file_exists() in the FredApi library
        if (!defined('FRED_API_ROOT')) {
            define('FRED_API_ROOT', ROOT . DS . 'lib' . DS . 'FredApi' . DS);
        }

        require_once ROOT . DS . 'lib' . DS . 'FredApi' . DS . 'fred_api.php';
        try {
            $this->api = new fred_api(Configure::read('fred_api_key'));
        } catch (fred_api_exception $e) {
            throw new InternalErrorException('Error creating FRED API object: ' . $e->getMessage());
        }
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        parent::execute($args, $io);
    }

    /**
     * Pauses execution for $this->rateThrottle seconds
     *
     * @return void
     */
    protected function throttle()
    {
        $microseconds = (int)($this->rateThrottle * 1000000);
        usleep($microseconds);
    }

    /**
     * Returns a decoded object if $response is valid JSON, returns FALSE if it's invalid
     *
     * Outputs a message to the console if returning FALSE
     * Throws an exception if $throwException is TRUE
     * Halts execution if $response is not a string
     *
     * @param mixed $response JSON response from FRED API
     * @param string|null $requiredProperty A property expected to be in the decoded object
     * @param bool $throwException Set to TRUE to throw a NotFoundException instead of returning FALSE
     * @return bool|\stdClass
     */
    protected function decodeResponse(mixed $response, ?string $requiredProperty = null, $throwException = false)
    {
        if (!is_string($response)) {
            $this->io->err('JSON response is not a string. API returned:');
            var_dump($response);

            exit;
        }

        $responseObj = json_decode($response);

        $responseIsValid = json_last_error() == JSON_ERROR_NONE && is_object($responseObj);
        if ($requiredProperty) {
            $responseIsValid = $responseIsValid && property_exists($responseObj, $requiredProperty);
        }

        if ($responseIsValid) {
            return $responseObj;
        }

        if ($throwException) {
            throw new NotFoundException();
        }

        $this->io->error('Failed, retrying');

        return false;
    }
}
