<?php
declare(strict_types=1);

namespace App\Slack;

use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * Class Slack
 *
 * Used to interface with another Slack API library
 *
 * @package App\Slack
 */
class Slack
{
    /**
     * @var int RETRY_ATTEMPTS The number of times to retry a failed attempt to send a message
     */
    public const RETRY_ATTEMPTS = 2;

    /**
     * @var float DELAY_BETWEEN_ATTEMPTS The number of seconds to wait after each failed attempt
     */
    public const DELAY_BETWEEN_ATTEMPTS = 1;

    /**
     * Sends a message to Slack and logs an error if the attempt fails
     *
     * @param string $text Message to send
     * @return void
     */
    public static function sendMessage(string $text)
    {
        $url = Configure::read('slack_webhook');
        $curlHandle = curl_init($url);
        $payload = json_encode(compact('text'));
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

        // Try to send this message RETRY_ATTEMPTS + 1 times and log any errors
        for ($attempt = 0; $attempt <= self::RETRY_ATTEMPTS; $attempt++) {
            if (curl_exec($curlHandle)) {
                break;
            }
            Log::error('Error sending message to Slack. Details: ' . curl_error($curlHandle));
            $microseconds = (int)(self::DELAY_BETWEEN_ATTEMPTS * 1000000);
            usleep($microseconds);
        }

        curl_close($curlHandle);
    }
}
