<?php declare(strict_types=1);

namespace Torq\Shopware\CustomPricing\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class CascadeApiWrapper
{
    private const AUTH_ENDPOINT = 'https://test-api.cascade-usa.com/api/authorization/token';
    private const PRICES_ENDPOINT = 'https://test-api.cascade-usa.com/api/p21pricing/getpricesbyskus';
    private const TOKEN_EXPIRY_BUFFER = 300; // 5 minutes buffer

    private ?string $token = null;
    private ?int $tokenExpiry = null;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
        private readonly string $username,
        private readonly string $password
    ) {}

    public function authenticate(): void
    {
        // Check if token is still valid
        if ($this->token && $this->tokenExpiry && time() < ($this->tokenExpiry - self::TOKEN_EXPIRY_BUFFER)) {
            return;
        }

        try {
            $response = $this->client->request('POST', self::AUTH_ENDPOINT, [
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Authentication failed');
            }

            $data = $response->toArray();
            $this->token = $data['token'];
            $this->tokenExpiry = time() + $data['expiresIn'];
            
        } catch (\Exception $e) {
            $this->logger->error('Authentication failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPrices(
        string $companyId,
        string $customerId,
        array $skus,
        string $sourceLocationId,
        string $salesLocationId
    ): ?array {
        try {
            $this->authenticate();

            $response = $this->client->request('POST', self::PRICES_ENDPOINT, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'companyId' => $companyId,
                    'customerId' => $customerId,
                    'skus' => $skus,
                    'sourceLocationId' => $sourceLocationId,
                    'salesLocationId' => $salesLocationId
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Failed to fetch prices');
            }

            $prices = $response->toArray();
            $this->logger->info('Prices fetched successfully', ['skus' => $skus]);
            
            return $prices;

        } catch (\Exception $e) {
            $this->logger->error('Price fetch failed: ' . $e->getMessage(), [
                'skus' => $skus,
                'customerId' => $customerId
            ]);
            return null;
        }
    }
}