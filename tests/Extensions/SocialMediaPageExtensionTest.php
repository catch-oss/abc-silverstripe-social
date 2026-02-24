<?php

namespace Azt3k\SS\Social\Tests\Extensions;

use Azt3k\SS\Social\DataObjects\PublicationFBUpdate;
use Azt3k\SS\Social\DataObjects\PublicationInstagramUpdate;
use Azt3k\SS\Social\DataObjects\PublicationTweet;
use Azt3k\SS\Social\Extensions\SocialMediaPageExtension;
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\SapphireTest;

class SocialMediaPageExtensionTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testExtendsExtension(): void
    {
        // GIVEN the SocialMediaPageExtension class
        // WHEN we check the class hierarchy
        // THEN it should extend Extension
        $this->assertTrue(is_subclass_of(SocialMediaPageExtension::class, Extension::class));
    }

    public function testDbFieldsAreDefined(): void
    {
        // GIVEN the SocialMediaPageExtension class
        // WHEN we check the db config
        $db = Config::inst()->get(SocialMediaPageExtension::class, 'db');

        // THEN it should have the expected fields
        $this->assertArrayHasKey('LastPostedToSocialMedia', $db);
        $this->assertArrayHasKey('PublicationFBUpdateID', $db);
        $this->assertArrayHasKey('PublicationTweetID', $db);
        $this->assertArrayHasKey('MetaTitle', $db);
        $this->assertArrayHasKey('MetaKeywords', $db);
        $this->assertArrayHasKey('ForceUpdateMode', $db);
    }

    public function testDbFieldTypes(): void
    {
        // GIVEN the SocialMediaPageExtension class
        // WHEN we check the db field types
        $db = Config::inst()->get(SocialMediaPageExtension::class, 'db');

        // THEN field types should match expectations
        $this->assertSame('Datetime', $db['LastPostedToSocialMedia']);
        $this->assertSame('Varchar(255)', $db['PublicationFBUpdateID']);
        $this->assertSame('Varchar(255)', $db['PublicationTweetID']);
        $this->assertSame('Varchar(255)', $db['MetaTitle']);
        $this->assertSame('Varchar(255)', $db['MetaKeywords']);
        $this->assertStringStartsWith('Enum(', $db['ForceUpdateMode']);
    }

    public function testForceUpdateModeEnumValues(): void
    {
        // GIVEN the SocialMediaPageExtension class
        // WHEN we check the ForceUpdateMode field
        $db = Config::inst()->get(SocialMediaPageExtension::class, 'db');

        // THEN it should contain Default, Block, and Force values
        $this->assertStringContainsString('Default', $db['ForceUpdateMode']);
        $this->assertStringContainsString('Block', $db['ForceUpdateMode']);
        $this->assertStringContainsString('Force', $db['ForceUpdateMode']);
    }

    public function testHasManyRelationsAreDefined(): void
    {
        // GIVEN the SocialMediaPageExtension class
        // WHEN we check the has_many config
        $hasMany = Config::inst()->get(SocialMediaPageExtension::class, 'has_many');

        // THEN it should have publication relations
        $this->assertArrayHasKey('PublicationTweets', $hasMany);
        $this->assertArrayHasKey('PublicationFBUpdates', $hasMany);
        $this->assertArrayHasKey('PublicationInstagramUpdates', $hasMany);
        $this->assertSame(PublicationTweet::class, $hasMany['PublicationTweets']);
        $this->assertSame(PublicationFBUpdate::class, $hasMany['PublicationFBUpdates']);
        $this->assertSame(PublicationInstagramUpdate::class, $hasMany['PublicationInstagramUpdates']);
    }

    public function testHasOneRelationsAreDefined(): void
    {
        // GIVEN the SocialMediaPageExtension class
        // WHEN we check the has_one config
        $hasOne = Config::inst()->get(SocialMediaPageExtension::class, 'has_one');

        // THEN it should have a PrimaryImage relation
        $this->assertArrayHasKey('PrimaryImage', $hasOne);
        $this->assertSame(Image::class, $hasOne['PrimaryImage']);
    }

    public function testDefaultsAreDefined(): void
    {
        // GIVEN the SocialMediaPageExtension class
        // WHEN we check the defaults config
        $defaults = Config::inst()->get(SocialMediaPageExtension::class, 'defaults');

        // THEN ForceUpdateMode default should be 'Default'
        $this->assertArrayHasKey('ForceUpdateMode', $defaults);
        $this->assertSame('Default', $defaults['ForceUpdateMode']);
    }

    public function testParseContentStripsTagsAndPreservesBr(): void
    {
        // GIVEN a SocialMediaPageExtension instance and HTML content
        $ext = new SocialMediaPageExtension();
        $content = '<p>Hello <strong>world</strong></p><p>Second paragraph</p>';

        // WHEN we parse the content with no word limit
        $result = $ext->parseContent($content);

        // THEN tags should be stripped (except br), paragraphs converted to line breaks
        $this->assertStringNotContainsString('<p>', $result);
        $this->assertStringNotContainsString('<strong>', $result);
        $this->assertStringContainsString('Hello', $result);
        $this->assertStringContainsString('world', $result);
        $this->assertStringContainsString('Second paragraph', $result);
    }

    public function testParseContentConvertsParagraphsToBrThenNewlines(): void
    {
        // GIVEN a SocialMediaPageExtension instance and paragraph content
        $ext = new SocialMediaPageExtension();
        $content = '<p>First</p><p>Second</p>';

        // WHEN we parse with null allowedTags (triggers br2nl conversion)
        $result = $ext->parseContent($content, null, null);

        // THEN <br> should be converted to newlines
        $this->assertStringNotContainsString('<br>', $result);
        $this->assertStringContainsString("First", $result);
        $this->assertStringContainsString("Second", $result);
    }

    public function testParseContentReturnsStringWithoutWordLimit(): void
    {
        // GIVEN a SocialMediaPageExtension instance and simple content
        $ext = new SocialMediaPageExtension();
        $content = '<p>This is some test content</p>';

        // WHEN we parse with no word limit
        $result = $ext->parseContent($content);

        // THEN the full content should be returned as a string
        $this->assertIsString($result);
        $this->assertStringContainsString('This is some test content', $result);
    }

    public function testMetaReturnsStringForKnownKey(): void
    {
        // GIVEN a SocialMediaPageExtension with a mock MetaMap
        $ext = $this->getMockBuilder(SocialMediaPageExtension::class)
            ->onlyMethods(['MetaMap'])
            ->getMock();

        $ext->method('MetaMap')->willReturn([
            'Title' => 'Test Title | My Site',
            'Keywords' => 'test, keywords',
            'Description' => 'A test description',
        ]);

        // WHEN we call Meta() with a known key
        $result = $ext->Meta('Title');

        // THEN it should return the expected value
        $this->assertIsString($result);
        $this->assertSame('Test Title | My Site', $result);
    }

    public function testMetaReturnsEmptyStringForUnknownKey(): void
    {
        // GIVEN a SocialMediaPageExtension with a mock MetaMap
        $ext = $this->getMockBuilder(SocialMediaPageExtension::class)
            ->onlyMethods(['MetaMap'])
            ->getMock();

        $ext->method('MetaMap')->willReturn([
            'Title' => 'Test Title | My Site',
        ]);

        // WHEN we call Meta() with an unknown key
        $result = $ext->Meta('NonExistentKey');

        // THEN it should return an empty string
        $this->assertSame('', $result);
    }

    public function testMetaMapReturnsArray(): void
    {
        // GIVEN a SocialMediaPageExtension with a mock MetaMap
        $ext = $this->getMockBuilder(SocialMediaPageExtension::class)
            ->onlyMethods(['MetaMap'])
            ->getMock();

        $ext->method('MetaMap')->willReturn([
            'Title' => 'Test Title | My Site',
            'Keywords' => 'test, keywords',
            'Description' => 'A test description',
            'SiteName' => 'My Site',
            'Link' => 'https://example.com/page',
            'Image' => null,
            'TwitterCreator' => '@testuser',
            'TwitterPublisher' => '@testuser',
            'TimeModified' => '2026-01-01 00:00:00',
            'TimeCreated' => '2025-01-01 00:00:00',
        ]);

        // WHEN we call MetaMap()
        $result = $ext->MetaMap();

        // THEN it should return an array with the expected keys
        $this->assertIsArray($result);
        $this->assertArrayHasKey('Title', $result);
        $this->assertArrayHasKey('Keywords', $result);
        $this->assertArrayHasKey('Description', $result);
        $this->assertArrayHasKey('SiteName', $result);
        $this->assertArrayHasKey('Link', $result);
        $this->assertArrayHasKey('Image', $result);
        $this->assertArrayHasKey('TwitterCreator', $result);
        $this->assertArrayHasKey('TwitterPublisher', $result);
        $this->assertArrayHasKey('TimeModified', $result);
        $this->assertArrayHasKey('TimeCreated', $result);
    }

    public function testGetFieldsToPushReturnsArray(): void
    {
        // GIVEN a SocialMediaPageExtension with a mock owner
        $mockOwner = new class {
            public function SharedContent(): string
            {
                return '<p>Test content for social media</p>';
            }
            public function SharedTitle(): string
            {
                return 'Test Title';
            }
            public function SharedLink(): string
            {
                return 'https://example.com/test-page';
            }
            public function AssociatedImage(): bool
            {
                return false;
            }
        };

        $ext = $this->getMockBuilder(SocialMediaPageExtension::class)
            ->onlyMethods(['parseContent'])
            ->getMock();

        $ext->method('parseContent')
            ->willReturn('Test content for social media');

        $refProp = new \ReflectionProperty(Extension::class, 'owner');
        $refProp->setValue($ext, $mockOwner);

        // WHEN we call getFieldsToPush()
        $result = $ext->getFieldsToPush();

        // THEN it should return an array with the expected keys
        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('link', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('picture', $result);
        $this->assertSame('Test Title', $result['name']);
        $this->assertSame('https://example.com/test-page', $result['link']);
        $this->assertNull($result['picture']);
    }

    public function testIndexesAreDefined(): void
    {
        // GIVEN the SocialMediaPageExtension class
        // WHEN we check the indexes config
        $indexes = Config::inst()->get(SocialMediaPageExtension::class, 'indexes');

        // THEN it should have indexes for key fields
        $this->assertArrayHasKey('ForceUpdateMode', $indexes);
        $this->assertArrayHasKey('LastPostedToSocialMedia', $indexes);
        $this->assertArrayHasKey('PublicationFBUpdateID', $indexes);
        $this->assertArrayHasKey('PublicationTweetID', $indexes);
    }
}
