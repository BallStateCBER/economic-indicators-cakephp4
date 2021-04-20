<?php
declare(strict_types=1);

namespace App\Command;

use App\Slack\Slack;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Http\Exception\InternalErrorException;
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
     * @var float Seconds to wait after an invalid API response before trying again
     */
    protected float $waitAfterError = 5;

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
        $this->sleep($this->rateThrottle);
    }

    /**
     * Pauses execution for $this->waitAfterError seconds
     *
     * @return void
     */
    protected function waitAfterError()
    {
        $this->sleep($this->waitAfterError);
    }

    /**
     * Pauses execution for $seconds seconds
     *
     * @param float $seconds Number of seconds to sleep
     * @return void
     */
    private function sleep(float $seconds)
    {
        $microseconds = (int)($seconds * 1000000);
        usleep($microseconds);
    }

    /**
     * Returns a decoded object if $response is valid JSON or FALSE if it's invalid
     *
     * Outputs messages to the console if $response is invalid
     * Halts execution instead of returning FALSE if $haltOnError is set to TRUE
     *
     * @param mixed $response JSON response from FRED API
     * @param string|null $requiredProperty A property expected to be in the decoded object
     * @param bool $haltOnError Set to TRUE to halt execution on error instead of returning FALSE
     * @return bool|\stdClass
     */
    protected function decodeResponse(mixed $response, ?string $requiredProperty = null, $haltOnError = false)
    {
        if (!is_string($response)) {
            $this->io->error('JSON response is not a string. API returned:');
            var_dump($response);

            if ($haltOnError) {
                exit;
            }

            return false;
        }

        $responseObj = json_decode($response);

        $responseIsValid = json_last_error() == JSON_ERROR_NONE && is_object($responseObj);
        if ($requiredProperty) {
            $responseIsValid = $responseIsValid && property_exists($responseObj, $requiredProperty);
        }

        if ($responseIsValid) {
            return $responseObj;
        }

        if ($haltOnError) {
            $this->io->error(sprintf(
                'API failed to return a valid response %d times in a row, aborting',
                $this->apiRetryCount + 1
            ));
            exit;
        }

        $this->io->error('Failed, retrying');

        return false;
    }

    /**
     * Sends a message to the console and to Slack
     *
     * @param string $text Message to send
     * @param string $mode 'success', 'error', or 'out' (default)
     * @return void
     * @throws \Cake\Http\Exception\InternalErrorException
     */
    protected function toConsoleAndSlack(string $text, string $mode = 'out'): void
    {
        switch ($mode) {
            case 'success':
                $this->io->success($text);
                break;
            case 'error':
                $this->io->error($text);
                break;
            case 'warning':
                $this->io->warning($text);
                break;
            default:
                $this->io->out($text);
        }
        Slack::sendMessage($text);
    }
}
