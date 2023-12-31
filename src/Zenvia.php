<?php

namespace NotificationChannels\LaravelZenviaChannel;

use GuzzleHttp\Client as ZenviaService;
use GuzzleHttp\Psr7\Response as ZenviaServiceResponse;
use Illuminate\Support\Str;
use NotificationChannels\LaravelZenviaChannel\Enums\CallbackOptionEnum;
use NotificationChannels\LaravelZenviaChannel\Exceptions\CouldNotSendNotification;

class Zenvia
{
    /** @var ZenviaService */
    protected $zenviaService;

    /** @var ZenviaConfig */
    public $config;

    public function __construct(ZenviaService $zenviaService, ZenviaConfig $config)
    {
        $this->zenviaService = $zenviaService;
        $this->config = $config;
    }

    /**
     * Send a ZenviaMessage to the a phone number.
     *
     * @param  ZenviaMessage  $message
     * @param  string|null  $to
     * @return mixed
     *
     * @throws CouldNotSendNotification
     */
    public function sendMessage(ZenviaMessage $message, ?string $to)
    {
        if ($message instanceof ZenviaSmsMessage) {
            return $this->sendSmsMessage($message, $to);
        }

        throw CouldNotSendNotification::invalidMessageObject($message);
    }

    protected function sendSmsMessage(ZenviaSmsMessage $message, ?string $to): ZenviaServiceResponse
    {
        if (empty($message->content)) {
            throw CouldNotSendNotification::contentNotProvided();
        }

        $data = [
            'sendSmsRequest' => [
                'from'              => $this->config->getFrom() ?? '',
                'to'                => $to,
                'msg'               => Str::limit($message->content, 160),
                'id'                => $message->id,
                'schedule'          => $message->schedule ?? '',
                'callbackOption'    => $message->callbackOption ?? CallbackOptionEnum::OPTION_NONE,
                'aggregateId'       => $message->aggregateId ?? '',
                'flashSms'          => $message->flashSms ?? false,
            ],
        ];

        $zenviaResponse = $this->zenviaService->post('/services/send-sms', ['json' => $data]);

        $response = json_decode($zenviaResponse->getBody()->getContents());
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw CouldNotSendNotification::unableParseResponse();
        }

        if (isset($response->sendSmsResponse->statusCode) && $response->sendSmsResponse->statusCode != '00') {
            throw CouldNotSendNotification::zenviaServiceError($response);
        }

        return $zenviaResponse;
    }
}
