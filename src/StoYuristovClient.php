<?php
declare(strict_types=1);

namespace StoYuristov;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use StoYuristov\Exception\ApiException;
use StoYuristov\Exception\ValidationException;

/**
 * Клиент для работы с API сервиса 100 Юристов
 * @author Michael Krutikov <misha.sunsetboy@gmail.com>
 */
class StoYuristovClient
{
    const SIGNATURE_ALGORITHM = 'sha256';

    public function __construct(
        private readonly int $appId,
        private readonly string $secretKey,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $baseUrl = 'https://100yuristov.com/api2/',
        private readonly bool $testMode = false,
    ) {
    }

    /**
     * @throws ValidationException
     * @throws ApiException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendLead(StoYuristovLead $lead): LeadResponse
    {
        $errors = $lead->validate();
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $params = [
            'appId'      => $this->appId,
            'signature'  => $this->calculateSignature($lead),
            'testMode'   => (int) $this->testMode,
            'name'       => $lead->getName(),
            'phone'      => $lead->getPhone(),
            'email'      => $lead->getEmail(),
            'town'       => $lead->getTown(),
            'type'       => $lead->getType(),
            'question'   => $lead->getQuestion(),
        ];

        if ($lead->getPrice() !== null) {
            $params['price'] = $lead->getPrice();
        }

        if ($lead->getWidgetUuid() !== null) {
            $params['widgetUuid'] = $lead->getWidgetUuid();
        }

        $body = json_encode($params);

        $request = $this->requestFactory
            ->createRequest('POST', rtrim($this->baseUrl, '/') . '/lead/create/')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));

        $response = $this->httpClient->sendRequest($request);

        try {
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ApiException('Invalid JSON response from API', $response->getStatusCode(), $e);
        }

        if ($response->getStatusCode() >= 400) {
            throw new ApiException($data['message'] ?? 'API error', $response->getStatusCode());
        }

        return LeadResponse::fromArray($data);
    }

    private function calculateSignature(StoYuristovLead $lead): string
    {
        return hash_hmac(self::SIGNATURE_ALGORITHM,
            $lead->getName() . $lead->getPhone() . $lead->getTown() . $lead->getQuestion() . $this->appId,
            $this->secretKey
        );
    }
}
