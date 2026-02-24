<?php

namespace Azt3k\SS\Social\Tests\Clients;

use Azt3k\SS\Social\Clients\InstagramBasicDisplayClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class InstagramBasicDisplayClientTest extends TestCase
{
    private string $clientId = 'test-client-id';
    private string $clientSecret = 'test-client-secret';
    private string $redirectUri = 'https://example.com/callback';

    public function testConstructorSetsProperties(): void
    {
        // GIVEN a set of Instagram API credentials
        $mockClient = $this->createMock(Client::class);

        // WHEN we create a new InstagramBasicDisplayClient
        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // THEN the object should be an instance of InstagramBasicDisplayClient
        $this->assertInstanceOf(InstagramBasicDisplayClient::class, $client);
    }

    public function testConstructorCreatesDefaultGuzzleClient(): void
    {
        // GIVEN a set of Instagram API credentials without a Guzzle client
        // WHEN we create a new InstagramBasicDisplayClient without passing a client
        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri
        );

        // THEN the object should still be created successfully
        $this->assertInstanceOf(InstagramBasicDisplayClient::class, $client);
    }

    public function testGetLoginUrlContainsClientId(): void
    {
        // GIVEN an InstagramBasicDisplayClient
        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri
        );

        // WHEN we get the login URL
        $url = $client->getLoginUrl();

        // THEN it should contain the client_id
        $this->assertStringContainsString('client_id=' . $this->clientId, $url);
    }

    public function testGetLoginUrlContainsRedirectUri(): void
    {
        // GIVEN an InstagramBasicDisplayClient
        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri
        );

        // WHEN we get the login URL
        $url = $client->getLoginUrl();

        // THEN it should contain the redirect_uri
        $this->assertStringContainsString('redirect_uri=' . urlencode($this->redirectUri), $url);
    }

    public function testGetLoginUrlContainsResponseType(): void
    {
        // GIVEN an InstagramBasicDisplayClient
        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri
        );

        // WHEN we get the login URL
        $url = $client->getLoginUrl();

        // THEN it should contain response_type=code
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function testGetLoginUrlStartsWithAuthUrl(): void
    {
        // GIVEN an InstagramBasicDisplayClient
        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri
        );

        // WHEN we get the login URL
        $url = $client->getLoginUrl();

        // THEN it should start with the Instagram OAuth authorize URL
        $this->assertStringStartsWith('https://api.instagram.com/oauth/authorize?', $url);
    }

    public function testGetLoginUrlContainsDefaultScopes(): void
    {
        // GIVEN an InstagramBasicDisplayClient
        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri
        );

        // WHEN we get the login URL with default scopes
        $url = $client->getLoginUrl();

        // THEN it should contain the default scopes
        $this->assertStringContainsString('scope=user_profile%2Cuser_media', $url);
    }

    public function testGetLoginUrlContainsCustomScopes(): void
    {
        // GIVEN an InstagramBasicDisplayClient
        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri
        );

        // WHEN we get the login URL with custom scopes
        $url = $client->getLoginUrl(['user_profile']);

        // THEN it should contain only the custom scope
        $this->assertStringContainsString('scope=user_profile', $url);
        $this->assertStringNotContainsString('user_media', $url);
    }

    public function testExchangeCodeForTokenCallsCorrectEndpoints(): void
    {
        // GIVEN a mock Guzzle client that returns valid token responses
        $mockClient = $this->createMock(Client::class);

        $shortTokenResponse = new Response(200, [], json_encode([
            'access_token' => 'short-lived-token',
            'user_id' => '12345',
        ]));

        $longTokenResponse = new Response(200, [], json_encode([
            'access_token' => 'long-lived-token',
            'expires_in' => 5184000,
        ]));

        $userResponse = new Response(200, [], json_encode([
            'id' => '12345',
            'username' => 'testuser',
        ]));

        $mockClient->expects($this->once())
            ->method('post')
            ->with(
                'https://api.instagram.com/oauth/access_token',
                $this->callback(function ($options) {
                    return isset($options['form_params'])
                        && $options['form_params']['grant_type'] === 'authorization_code'
                        && $options['form_params']['code'] === 'test-code'
                        && $options['form_params']['client_id'] === $this->clientId
                        && $options['form_params']['client_secret'] === $this->clientSecret
                        && $options['form_params']['redirect_uri'] === $this->redirectUri;
                })
            )
            ->willReturn($shortTokenResponse);

        $mockClient->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($url, $options) use ($longTokenResponse, $userResponse) {
                if (str_contains($url, '/access_token')) {
                    $this->assertSame('ig_exchange_token', $options['query']['grant_type']);
                    $this->assertSame($this->clientSecret, $options['query']['client_secret']);
                    $this->assertSame('short-lived-token', $options['query']['access_token']);
                    return $longTokenResponse;
                }
                if (str_contains($url, '/me')) {
                    $this->assertSame('long-lived-token', $options['query']['access_token']);
                    return $userResponse;
                }
                $this->fail('Unexpected URL: ' . $url);
            });

        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // WHEN we exchange a code for a token
        $result = $client->exchangeCodeForToken('test-code');

        // THEN we should get back the long-lived token and user data
        $this->assertSame('long-lived-token', $result['access_token']);
        $this->assertSame(5184000, $result['expires_in']);
        $this->assertSame('12345', $result['user']['id']);
        $this->assertSame('testuser', $result['user']['username']);
    }

    public function testExchangeCodeForTokenThrowsOnMissingShortToken(): void
    {
        // GIVEN a mock Guzzle client that returns an empty access_token
        $mockClient = $this->createMock(Client::class);
        $mockClient->method('post')
            ->willReturn(new Response(200, [], json_encode(['user_id' => '123'])));

        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // WHEN we exchange a code that doesn't yield a token
        // THEN an exception should be thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to exchange code for access token.');
        $client->exchangeCodeForToken('bad-code');
    }

    public function testRefreshAccessTokenCallsCorrectEndpoint(): void
    {
        // GIVEN a mock Guzzle client
        $mockClient = $this->createMock(Client::class);
        $refreshResponse = new Response(200, [], json_encode([
            'access_token' => 'refreshed-token',
            'expires_in' => 5184000,
        ]));

        $mockClient->expects($this->once())
            ->method('get')
            ->with(
                'https://graph.instagram.com/refresh_access_token',
                $this->callback(function ($options) {
                    return $options['query']['grant_type'] === 'ig_refresh_token'
                        && $options['query']['access_token'] === 'old-token';
                })
            )
            ->willReturn($refreshResponse);

        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // WHEN we refresh an access token
        $result = $client->refreshAccessToken('old-token');

        // THEN we should get the refreshed token
        $this->assertSame('refreshed-token', $result['access_token']);
        $this->assertSame(5184000, $result['expires_in']);
    }

    public function testGetUserMediaConstructsCorrectUrl(): void
    {
        // GIVEN a mock Guzzle client
        $mockClient = $this->createMock(Client::class);
        $mediaResponse = new Response(200, [], json_encode([
            'data' => [['id' => '1', 'caption' => 'test']],
        ]));

        $mockClient->expects($this->once())
            ->method('get')
            ->with(
                'https://graph.instagram.com/me/media',
                $this->callback(function ($options) {
                    return $options['query']['access_token'] === 'test-token'
                        && $options['query']['limit'] === 90
                        && str_contains($options['query']['fields'], 'id')
                        && str_contains($options['query']['fields'], 'caption')
                        && str_contains($options['query']['fields'], 'media_type')
                        && str_contains($options['query']['fields'], 'media_url')
                        && str_contains($options['query']['fields'], 'permalink')
                        && str_contains($options['query']['fields'], 'thumbnail_url')
                        && str_contains($options['query']['fields'], 'timestamp');
                })
            )
            ->willReturn($mediaResponse);

        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // WHEN we get user media
        $result = $client->getUserMedia('test-token');

        // THEN we should get the media data
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('1', $result['data'][0]['id']);
    }

    public function testGetUserMediaRespectsCustomLimit(): void
    {
        // GIVEN a mock Guzzle client
        $mockClient = $this->createMock(Client::class);
        $mediaResponse = new Response(200, [], json_encode(['data' => []]));

        $mockClient->expects($this->once())
            ->method('get')
            ->with(
                'https://graph.instagram.com/me/media',
                $this->callback(function ($options) {
                    return $options['query']['limit'] === 25;
                })
            )
            ->willReturn($mediaResponse);

        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // WHEN we get user media with a custom limit
        $result = $client->getUserMedia('test-token', 25);

        // THEN it should use the custom limit
        $this->assertArrayHasKey('data', $result);
    }

    public function testGetUserCallsCorrectEndpoint(): void
    {
        // GIVEN a mock Guzzle client
        $mockClient = $this->createMock(Client::class);
        $userResponse = new Response(200, [], json_encode([
            'id' => '12345',
            'username' => 'testuser',
        ]));

        $mockClient->expects($this->once())
            ->method('get')
            ->with(
                'https://graph.instagram.com/me',
                $this->callback(function ($options) {
                    return $options['query']['fields'] === 'id,username'
                        && $options['query']['access_token'] === 'test-token';
                })
            )
            ->willReturn($userResponse);

        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // WHEN we get the user
        $result = $client->getUser('test-token');

        // THEN we should get the user data
        $this->assertSame('12345', $result['id']);
        $this->assertSame('testuser', $result['username']);
    }

    public function testApiErrorResponseThrowsException(): void
    {
        // GIVEN a mock Guzzle client that returns an API error
        $mockClient = $this->createMock(Client::class);
        $errorResponse = new Response(200, [], json_encode([
            'error' => ['message' => 'Invalid access token'],
        ]));

        $mockClient->method('get')->willReturn($errorResponse);

        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // WHEN we make a request that returns an error
        // THEN an exception with the error message should be thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid access token');
        $client->getUser('bad-token');
    }

    public function testInvalidJsonResponseThrowsException(): void
    {
        // GIVEN a mock Guzzle client that returns invalid JSON
        $mockClient = $this->createMock(Client::class);
        $badResponse = new Response(200, [], 'not-json');

        $mockClient->method('get')->willReturn($badResponse);

        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // WHEN we make a request that returns invalid JSON
        // THEN an exception should be thrown
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid response from Instagram API.');
        $client->getUser('some-token');
    }

    public function testGetMediaPageCallsRawUrl(): void
    {
        // GIVEN a mock Guzzle client
        $mockClient = $this->createMock(Client::class);
        $pageResponse = new Response(200, [], json_encode([
            'data' => [['id' => '999']],
        ]));

        $mockClient->expects($this->once())
            ->method('get')
            ->with('https://graph.instagram.com/me/media?cursor=abc123')
            ->willReturn($pageResponse);

        $client = new InstagramBasicDisplayClient(
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $mockClient
        );

        // WHEN we get a media page via a next URL
        $result = $client->getMediaPage('https://graph.instagram.com/me/media?cursor=abc123');

        // THEN we should get the page data
        $this->assertSame('999', $result['data'][0]['id']);
    }
}
