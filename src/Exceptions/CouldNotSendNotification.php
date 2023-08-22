<?php

namespace NotificationChannels\LaravelZenviaChannel\Exceptions;

use GuzzleHttp\Exception\ClientException;
use NotificationChannels\LaravelZenviaChannel\ZenviaMessage;

class CouldNotSendNotification extends \Exception
{
    public static function invalidMessageObject($message): self
    {
        $className = is_object($message) ? get_class($message) : 'Unknown';

        return new static(
            "Notification was not sent. Message object class `{$className}` is invalid. It should
            be either `".ZenviaMessage::class.'`');
    }

    public static function invalidReceiver(): self
    {
        return new static(
            'The notifiable did not have a receiving phone number. Add a routeNotificationForZenvia method or a phone_number attribute to your notifiable.'
        );
    }

    public static function contentNotProvided(): self
    {
        return new static(
            'Sms content not provided'
        );
    }

    public static function serviceConnectionError(): self
    {
        return new static(
            'Could not connect with Zenvia Service'
        );
    }

    /**
     * Thrown when there's a bad request and an error is responded.
     *
     * @param  ClientException  $exception
     * @return static
     */
    public static function serviceRespondedWithAnError(ClientException $exception): self
    {
        $statusCode = $exception->getResponse()->getStatusCode();
        $description = $exception->getResponse()->getReasonPhrase();

        $responseContents = json_decode($exception->getResponse()->getBody()->getContents());
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($responseContents->sendSmsResponse->detailDescription)) {
                $description = $responseContents->sendSmsResponse->detailDescription;
            }

            if (isset($responseContents->exception->message)) {
                $description = $responseContents->exception->message;
            }
        }

        return new static(
            "Zenvia API responded with an error `HTTP Code {$statusCode} - {$description}`"
        );
    }

    public static function unableParseResponse(): self
    {
        return new static(
            'Unable to parse response from Zenvia Service'
        );
    }

    public static function zenviaServiceError(object $response): self
    {
        $statusCode = 'no status given';
        $description = 'no description given';

        if (isset($response->sendSmsResponse->statusCode)) {
            $statusCode = $response->sendSmsResponse->statusCode;
        }

        if (isset($response->sendSmsResponse->detailDescription)) {
            $description = $response->sendSmsResponse->detailDescription;
        }

        return new static(
            "Zenvia Service responded with an error `{$statusCode} - {$description}`"
        );
    }
}
