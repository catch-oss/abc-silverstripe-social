<?php

namespace Azt3k\SS\Social\Tests\Controllers;

use Azt3k\SS\Social\Controllers\PostToSocialMedia;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SiteConfig\SiteConfig;

class PostToSocialMediaTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testExtendsController(): void
    {
        // GIVEN the PostToSocialMedia class
        // WHEN we check its class hierarchy
        // THEN it should extend Controller
        $this->assertTrue(is_subclass_of(PostToSocialMedia::class, Controller::class));
    }

    public function testConfirmTwitterAccessReturnsBool(): void
    {
        // GIVEN a PostToSocialMedia instance with no Twitter config
        $config = SiteConfig::current_site_config();
        $config->TwitterPushUpdates = false;
        $config->write();

        $controller = PostToSocialMedia::create();

        // WHEN we call confirmTwitterAccess
        $result = $controller->confirmTwitterAccess();

        // THEN it should return false (no push updates enabled)
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    public function testConfirmFacebookAccessReturnsBool(): void
    {
        // GIVEN a PostToSocialMedia instance with no Facebook config
        $config = SiteConfig::current_site_config();
        $config->FacebookPushUpdates = false;
        $config->write();

        $controller = PostToSocialMedia::create();

        // WHEN we call confirmFacebookAccess
        $result = $controller->confirmFacebookAccess();

        // THEN it should return false (no push updates enabled)
        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    public function testSendToSocialMediaReturnsArray(): void
    {
        // GIVEN a PostToSocialMedia instance with no social media configured
        $config = SiteConfig::current_site_config();
        $config->TwitterPushUpdates = false;
        $config->FacebookPushUpdates = false;
        $config->write();

        $controller = PostToSocialMedia::create();

        // WHEN we call sendToSocialMedia with test data
        $result = $controller->sendToSocialMedia([
            'name' => 'Test Post',
            'link' => 'https://example.com',
        ]);

        // THEN it should return an array with facebook and twitter keys
        $this->assertIsArray($result);
        $this->assertArrayHasKey('facebook', $result);
        $this->assertArrayHasKey('twitter', $result);
    }

    public function testSendToSocialMediaReturnsNullIdsWhenDisabled(): void
    {
        // GIVEN a PostToSocialMedia instance with social media disabled
        $config = SiteConfig::current_site_config();
        $config->TwitterPushUpdates = false;
        $config->FacebookPushUpdates = false;
        $config->write();

        $controller = PostToSocialMedia::create();

        // WHEN we call sendToSocialMedia
        $result = $controller->sendToSocialMedia([
            'name' => 'Test',
            'link' => 'https://example.com',
        ]);

        // THEN both IDs should be null since services are disabled
        $this->assertNull($result['facebook']);
        $this->assertNull($result['twitter']);
    }
}
