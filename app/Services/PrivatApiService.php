<?php

namespace App\Services;

use GuzzleHttp\Client;

class PrivatApiService
{
    private const API_URL = 'https://acp.privatbank.ua/api';

    private const STATEMENT_ENDPOINT = '/statements/transactions';

    public function __construct(readonly private Client $client)
    {
    }

    public function getStatements(string $token, array $params = [])
    {
        $response = $this->client->get($this->buildUri(), [
            'headers' => [
                'Accept' => 'application/json',
                'token' => $token,
                'Content-Type' => 'application/json;charset=cp1251',
            ],
            'query' => $params,
        ]);

        return json_decode($response->getBody()->getContents(), true, 512, JSON_INVALID_UTF8_IGNORE) ?? [];
    }

    private function buildUri(): string
    {
        return self::API_URL . self::STATEMENT_ENDPOINT;
    }
}
