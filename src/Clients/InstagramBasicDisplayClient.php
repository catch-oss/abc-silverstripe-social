<?php

namespace Azt3k\SS\Social\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

class InstagramBasicDisplayClient
{
    private const AUTH_URL = 'https://api.instagram.com/oauth/authorize';
    private const TOKEN_URL = 'https://api.instagram.com/oauth/access_token';
    private const GRAPH_URL = 'https://graph.instagram.com';

    private Client $client;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct(string $clientId, string $clientSecret, string $redirectUri, ?Client $client = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->client = $client ?? new Client(['timeout' => 30]);
    }

    public function getLoginUrl(array $scopes = ['user_profile', 'user_media']): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
        ]);
    }

    public function exchangeCodeForToken(string $code): array
    {
        $shortToken = $this->post(self::TOKEN_URL, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if (empty($shortToken['access_token'])) {
            throw new Exception('Failed to exchange code for access token.');
        }

        $longToken = $this->get(self::GRAPH_URL . '/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $this->clientSecret,
            'access_token' => $shortToken['access_token'],
        ]);

        if (empty($longToken['access_token'])) {
            throw new Exception('Failed to exchange short-lived access token.');
        }

        $user = $this->getUser($longToken['access_token']);

        return [
            'access_token' => $longToken['access_token'],
            'expires_in' => $longToken['expires_in'] ?? null,
            'user' => $user,
        ];
    }

    public function refreshAccessToken(string $accessToken): array
    {
        return $this->get(self::GRAPH_URL . '/refresh_access_token', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $accessToken,
        ]);
    }

    public function getUser(string $accessToken): array
    {
        return $this->get(self::GRAPH_URL . '/me', [
            'fields' => 'id,username',
            'access_token' => $accessToken,
        ]);
    }

    public function getUserMedia(string $accessToken, int $limit = 90): array
    {
        return $this->get(self::GRAPH_URL . '/me/media', [
            'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp',
            'limit' => $limit,
            'access_token' => $accessToken,
        ]);
    }

    public function getMediaPage(string $nextUrl): array
    {
        return $this->getRaw($nextUrl);
    }

    private function get(string $url, array $query): array
    {
        try {
            $response = $this->client->get($url, ['query' => $query]);
        } catch (GuzzleException $e) {
            throw new Exception('Instagram API request failed: ' . $e->getMessage(), 0, $e);
        }

        return $this->decodeResponse((string) $response->getBody());
    }

    private function post(string $url, array $formParams): array
    {
        try {
            $response = $this->client->post($url, ['form_params' => $formParams]);
        } catch (GuzzleException $e) {
            throw new Exception('Instagram API request failed: ' . $e->getMessage(), 0, $e);
        }

        return $this->decodeResponse((string) $response->getBody());
    }

    private function getRaw(string $url): array
    {
        try {
            $response = $this->client->get($url);
        } catch (GuzzleException $e) {
            throw new Exception('Instagram API request failed: ' . $e->getMessage(), 0, $e);
        }

        return $this->decodeResponse((string) $response->getBody());
    }

    private function decodeResponse(string $payload): array
    {
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            throw new Exception('Invalid response from Instagram API.');
        }

        if (isset($data['error'])) {
            $message = is_array($data['error']) ? ($data['error']['message'] ?? 'Instagram API error.') : $data['error'];
            throw new Exception($message);
        }

        return $data;
    }
}
