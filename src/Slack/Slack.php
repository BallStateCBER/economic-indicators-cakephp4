<?php
namespace App\Slack;

use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * Class Slack
 *
 * Used to interface with another Slack API library
 *
 * @package App\Slack
 * @property \Maknz\Slack\Client $client
 */
class Slack
{
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
        if (!curl_exec($curlHandle)) {
            Log::error('Error sending message to Slack. Details: ' . curl_error($curlHandle));
        }
        curl_close($curlHandle);
    }
}
