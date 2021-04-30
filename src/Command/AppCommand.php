<?php
declare(strict_types=1);

namespace App\Command;

use App\Endpoints\EndpointGroups;
use App\Slack\Slack;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Http\Exception\InternalErrorException;
use Cake\Utility\Hash;
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
    protected int $apiRetryCount = 5;

    /**
     * @var float Seconds to wait between each API call
     */
    protected float $rateThrottle = 2;

    /**
     * @var float Seconds to wait after an invalid API response before trying again
     */
    protected float $waitAfterError = 10;

    /**
     * @var bool If TRUE (set by --mute-slack option), prevents sending any messages to Slack
     */
    protected bool $muteSlack = false;

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
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->addOption('mute-slack', [
            'short' => 'm',
            'help' => 'Don\'t send any messages to Slack',
            'boolean' => true,
        ]);

        return $parser;
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
        $this->muteSlack = (bool)$args->getOption('mute-slack');
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

        $this->toSlack($text);
    }

    /**
     * Sends a message to Slack if the --mute-slack option is not in use
     *
     * @param string $text Message to send
     * @return void
     */
    protected function toSlack(string $text)
    {
        if (!$this->muteSlack) {
            Slack::sendMessage($text);
        }
    }

    /**
     * Returns all endpoint groups OR an array containing the user's selection
     *
     * @param \Cake\Console\Arguments $args Console arguments
     * @return array
     */
    protected function getSelectedEndpointGroups(Arguments $args): array
    {
        $choose = (bool)$args->getOption('choose');
        $allEndpointGroups = array_values(EndpointGroups::getAll());
        $allEndpointGroups = Hash::combine($allEndpointGroups, '{n}.title', '{n}');
        ksort($allEndpointGroups);
        $allEndpointGroups = array_values($allEndpointGroups);
        if (!$choose) {
            return $allEndpointGroups;
        }

        foreach ($allEndpointGroups as $k => $endpointGroup) {
            $this->io->out(($k + 1) . ") {$endpointGroup['title']}");
        }
        $count = count($allEndpointGroups);
        do {
            $choice = (int)$this->io->ask("Select an endpoint group: (1-$count)");
        } while (!($choice >= 1 && $choice <= $count));

        return [$allEndpointGroups[$choice - 1]];
    }
}
