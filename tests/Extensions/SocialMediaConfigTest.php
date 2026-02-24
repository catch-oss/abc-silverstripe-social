<?php

namespace Azt3k\SS\Social\Tests\Extensions;

use Azt3k\SS\Social\Extensions\SocialMediaConfig;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;

class SocialMediaConfigTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        SiteConfig::class => [SocialMediaConfig::class],
    ];

    public function testExtendsExtension(): void
    {
        // GIVEN the SocialMediaConfig class
        // WHEN we check the class hierarchy
        // THEN it should extend Extension (not DataExtension)
        $this->assertTrue(is_subclass_of(SocialMediaConfig::class, Extension::class));
    }

    public function testHasFacebookDbFields(): void
    {
        // GIVEN the SocialMediaConfig class
        // WHEN we check the db config
        $db = Config::inst()->get(SocialMediaConfig::class, 'db');

        // THEN it should have Facebook-related fields
        $this->assertArrayHasKey('FacebookAppId', $db);
        $this->assertArrayHasKey('FacebookAppSecret', $db);
        $this->assertArrayHasKey('FacebookUserId', $db);
        $this->assertArrayHasKey('FacebookUserAccessToken', $db);
        $this->assertArrayHasKey('FacebookPageId', $db);
        $this->assertArrayHasKey('FacebookPageAccessToken', $db);
        $this->assertArrayHasKey('FacebookPageFeedType', $db);
        $this->assertArrayHasKey('FacebookPushUpdates', $db);
        $this->assertArrayHasKey('FacebookPullUpdates', $db);
    }

    public function testHasTwitterDbFields(): void
    {
        // GIVEN the SocialMediaConfig class
        // WHEN we check the db config
        $db = Config::inst()->get(SocialMediaConfig::class, 'db');

        // THEN it should have Twitter-related fields
        $this->assertArrayHasKey('TwitterConsumerKey', $db);
        $this->assertArrayHasKey('TwitterConsumerSecret', $db);
        $this->assertArrayHasKey('TwitterOAuthToken', $db);
        $this->assertArrayHasKey('TwitterOAuthSecret', $db);
        $this->assertArrayHasKey('TwitterUsername', $db);
        $this->assertArrayHasKey('TwitterPushUpdates', $db);
        $this->assertArrayHasKey('TwitterPullUpdates', $db);
    }

    public function testHasInstagramDbFields(): void
    {
        // GIVEN the SocialMediaConfig class
        // WHEN we check the db config
        $db = Config::inst()->get(SocialMediaConfig::class, 'db');

        // THEN it should have Instagram-related fields
        $this->assertArrayHasKey('InstagramApiKey', $db);
        $this->assertArrayHasKey('InstagramApiSecret', $db);
        $this->assertArrayHasKey('InstagramOAuthToken', $db);
        $this->assertArrayHasKey('InstagramUsername', $db);
        $this->assertArrayHasKey('InstagramUserId', $db);
        $this->assertArrayHasKey('InstagramPushUpdates', $db);
        $this->assertArrayHasKey('InstagramPullUpdates', $db);
    }

    public function testHasOneRelationsAreDefined(): void
    {
        // GIVEN the SocialMediaConfig class
        // WHEN we check the has_one config
        $hasOne = Config::inst()->get(SocialMediaConfig::class, 'has_one');

        // THEN it should have image relations
        $this->assertArrayHasKey('DefaultImage', $hasOne);
        $this->assertArrayHasKey('DefaultFBUpdateImage', $hasOne);
        $this->assertArrayHasKey('DefaultTweetImage', $hasOne);
        $this->assertArrayHasKey('DefaultInstagramUpdateImage', $hasOne);
        $this->assertSame(Image::class, $hasOne['DefaultImage']);
        $this->assertSame(Image::class, $hasOne['DefaultFBUpdateImage']);
        $this->assertSame(Image::class, $hasOne['DefaultTweetImage']);
        $this->assertSame(Image::class, $hasOne['DefaultInstagramUpdateImage']);
    }

    public function testDbFieldTypes(): void
    {
        // GIVEN the SocialMediaConfig class
        // WHEN we check the db field types
        $db = Config::inst()->get(SocialMediaConfig::class, 'db');

        // THEN text fields should be Varchar(255)
        $this->assertSame('Varchar(255)', $db['FacebookAppId']);
        $this->assertSame('Varchar(255)', $db['TwitterConsumerKey']);
        $this->assertSame('Varchar(255)', $db['InstagramApiKey']);
    }

    public function testBooleanFieldTypes(): void
    {
        // GIVEN the SocialMediaConfig class
        // WHEN we check the boolean field types
        $db = Config::inst()->get(SocialMediaConfig::class, 'db');

        // THEN push/pull update fields should be Boolean
        $this->assertSame('Boolean', $db['FacebookPushUpdates']);
        $this->assertSame('Boolean', $db['FacebookPullUpdates']);
        $this->assertSame('Boolean', $db['TwitterPushUpdates']);
        $this->assertSame('Boolean', $db['TwitterPullUpdates']);
        $this->assertSame('Boolean', $db['InstagramPushUpdates']);
        $this->assertSame('Boolean', $db['InstagramPullUpdates']);
    }

    public function testDatetimeFieldTypes(): void
    {
        // GIVEN the SocialMediaConfig class
        // WHEN we check the datetime field types
        $db = Config::inst()->get(SocialMediaConfig::class, 'db');

        // THEN token expiry fields should be Datetime
        $this->assertSame('Datetime', $db['FacebookUserAccessTokenExpires']);
        $this->assertSame('Datetime', $db['FacebookPageAccessTokenExpires']);
        $this->assertSame('Datetime', $db['TwitterOAuthTokenExpires']);
        $this->assertSame('Datetime', $db['InstagramOAuthTokenExpires']);
    }

    public function testFacebookPageFeedTypeIsEnum(): void
    {
        // GIVEN the SocialMediaConfig class
        // WHEN we check the FacebookPageFeedType field
        $db = Config::inst()->get(SocialMediaConfig::class, 'db');

        // THEN it should be an Enum with feed as default
        $this->assertStringStartsWith('Enum(', $db['FacebookPageFeedType']);
        $this->assertStringContainsString('feed', $db['FacebookPageFeedType']);
        $this->assertStringContainsString('posts', $db['FacebookPageFeedType']);
        $this->assertStringContainsString('tagged', $db['FacebookPageFeedType']);
    }

    public function testUpdateCMSFieldsAddsFieldsToSiteConfig(): void
    {
        // GIVEN a SiteConfig object with the SocialMediaConfig extension
        $siteConfig = SiteConfig::current_site_config();

        // WHEN we get its CMS fields
        $fields = $siteConfig->getCMSFields();

        // THEN it should have social media fields added by the extension
        $this->assertNotNull($fields->dataFieldByName('FacebookAppId'));
    }
}
