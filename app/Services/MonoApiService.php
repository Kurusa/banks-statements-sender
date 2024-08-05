<?php

namespace App\Services;

use GuzzleHttp\Client;

class MonoApiService
{
    private const API_URL = 'https://api.monobank.ua';
    private const STATEMENT_ENDPOINT = '/personal/statement/';

    public function __construct(private readonly Client $client)
    {
    }

    public function getStatements(string $token, array $params = []): array
    {
        $response = $this->client->get($this->buildUri($params), [
            'headers' => [
                'X-Token' => $token,
                'Accept' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function buildUri(array $params): string
    {
        $paramList = implode('/', $params);
        return self::API_URL . self::STATEMENT_ENDPOINT . $paramList;
    }
}
