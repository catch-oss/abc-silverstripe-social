<?php

namespace Azt3k\SS\Social\Tests\Objects;

use Azt3k\SS\Social\Objects\SocialHelper;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\SapphireTest;

class SocialHelperTest extends SapphireTest
{
    protected $usesDatabase = false;

    private array $originalServer = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        parent::tearDown();
    }

    public function testPhpSelfReturnsString(): void
    {
        // GIVEN the SocialHelper class and a simulated server environment
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/test-page?foo=bar';

        // WHEN we call php_self()
        $result = SocialHelper::php_self();

        // THEN it should return a string URL without query string
        $this->assertIsString($result);
        $this->assertSame('http://example.com/test-page', $result);
    }

    public function testPhpSelfWithQueryString(): void
    {
        // GIVEN the SocialHelper class and a simulated server environment
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/test-page?foo=bar';

        // WHEN we call php_self() with dropqs=false
        $result = SocialHelper::php_self(false);

        // THEN it should return a string URL with query string
        $this->assertIsString($result);
        $this->assertSame('http://example.com/test-page?foo=bar', $result);
    }

    public function testPhpSelfDetectsHttps(): void
    {
        // GIVEN the SocialHelper class and an HTTPS environment
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_NAME'] = 'secure.example.com';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['REQUEST_URI'] = '/secure-page';

        // WHEN we call php_self()
        $result = SocialHelper::php_self();

        // THEN it should return an HTTPS URL
        $this->assertStringStartsWith('https://', $result);
        $this->assertSame('https://secure.example.com/secure-page', $result);
    }

    public function testPhpSelfDetectsHttpsViaPort(): void
    {
        // GIVEN the SocialHelper class with HTTPS detected by port 443
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_NAME'] = 'secure.example.com';
        $_SERVER['SERVER_PORT'] = '443';
        $_SERVER['REQUEST_URI'] = '/secure-page';

        // WHEN we call php_self()
        $result = SocialHelper::php_self();

        // THEN it should return an HTTPS URL
        $this->assertStringStartsWith('https://', $result);
    }

    public function testPhpSelfIncludesNonStandardPort(): void
    {
        // GIVEN the SocialHelper class with a non-standard port
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['SERVER_PORT'] = '8080';
        $_SERVER['REQUEST_URI'] = '/page';

        // WHEN we call php_self()
        $result = SocialHelper::php_self();

        // THEN it should include the port in the URL
        $this->assertStringContainsString(':8080', $result);
        $this->assertSame('http://example.com:8080/page', $result);
    }

    public function testLinkReturnsFacebookUserUrl(): void
    {
        // GIVEN a Facebook user ID
        $id = '123456789';

        // WHEN we call link() for facebook user
        $result = SocialHelper::link($id, 'facebook');

        // THEN it should return the expected Facebook user URL
        $this->assertSame('https://www.facebook.com/123456789', $result);
    }

    public function testLinkReturnsFacebookPageUrl(): void
    {
        // GIVEN a Facebook page ID
        $id = '987654321';

        // WHEN we call link() for facebook page
        $result = SocialHelper::link($id, 'facebook', 'page');

        // THEN it should return the expected Facebook page URL
        $this->assertSame('https://www.facebook.com/pages/987654321', $result);
    }

    public function testLinkReturnsTwitterUrl(): void
    {
        // GIVEN a Twitter username
        $id = 'testuser';

        // WHEN we call link() for twitter
        $result = SocialHelper::link($id, 'twitter');

        // THEN it should return the expected Twitter URL
        $this->assertSame('https://twitter.com/testuser', $result);
    }

    public function testLinkReturnsInstagramUrl(): void
    {
        // GIVEN an Instagram username
        $id = 'photouser';

        // WHEN we call link() for instagram
        $result = SocialHelper::link($id, 'instagram');

        // THEN it should return the expected Instagram URL
        $this->assertSame('https://instagram.com/photouser', $result);
    }

    public function testLinkReturnsNullForUnknownService(): void
    {
        // GIVEN an unknown service name
        $id = 'someuser';

        // WHEN we call link() for an unknown service
        $result = SocialHelper::link($id, 'tiktok');

        // THEN it should return null
        $this->assertNull($result);
    }

    public function testUsesExtensibleTrait(): void
    {
        // GIVEN the SocialHelper class
        // WHEN we check for the Extensible trait
        $traits = class_uses(SocialHelper::class);

        // THEN it should use the Extensible trait
        $this->assertContains(Extensible::class, $traits);
    }

    public function testUsesInjectableTrait(): void
    {
        // GIVEN the SocialHelper class
        // WHEN we check for the Injectable trait
        $traits = class_uses(SocialHelper::class);

        // THEN it should use the Injectable trait
        $this->assertContains(Injectable::class, $traits);
    }

    public function testUsesConfigurableTrait(): void
    {
        // GIVEN the SocialHelper class
        // WHEN we check for the Configurable trait
        $traits = class_uses(SocialHelper::class);

        // THEN it should use the Configurable trait
        $this->assertContains(Configurable::class, $traits);
    }

    public function testFbAccessTokenMethodExists(): void
    {
        // GIVEN the SocialHelper class
        // WHEN we check for the fb_access_token method
        $ref = new \ReflectionMethod(SocialHelper::class, 'fb_access_token');

        // THEN it should be a public static method
        $this->assertTrue($ref->isPublic());
        $this->assertTrue($ref->isStatic());
    }

    public function testLinkMethodIsPublicStatic(): void
    {
        // GIVEN the SocialHelper class
        // WHEN we check the link method signature
        $ref = new \ReflectionMethod(SocialHelper::class, 'link');

        // THEN it should be public and static
        $this->assertTrue($ref->isPublic());
        $this->assertTrue($ref->isStatic());
    }

    public function testPhpSelfMethodIsPublicStatic(): void
    {
        // GIVEN the SocialHelper class
        // WHEN we check the php_self method signature
        $ref = new \ReflectionMethod(SocialHelper::class, 'php_self');

        // THEN it should be public and static
        $this->assertTrue($ref->isPublic());
        $this->assertTrue($ref->isStatic());
    }

    public function testPhpSelfDefaultsToLocalhostWhenNoServerName(): void
    {
        // GIVEN the SocialHelper class with no SERVER_NAME
        unset($_SERVER['HTTPS']);
        $_SERVER['SERVER_NAME'] = '';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/test';

        // WHEN we call php_self()
        $result = SocialHelper::php_self();

        // THEN it should default to localhost
        $this->assertStringContainsString('localhost', $result);
    }
}
