<?php
namespace App\Slack;

use Cake\Core\Configure;
use Cake\Http\Exception\InternalErrorException;

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
     * Sends a message
     *
     * @param string $text Message to send
     * @return void
     * @throws \Cake\Http\Exception\InternalErrorException
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
            throw new InternalErrorException('Error sending message to Slack: ' . $text);
        }
        curl_close($curlHandle);
    }
}
