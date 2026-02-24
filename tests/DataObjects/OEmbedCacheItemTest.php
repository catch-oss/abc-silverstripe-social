<?php

namespace Azt3k\SS\Social\Tests\DataObjects;

use Azt3k\SS\Social\DataObjects\OEmbedCacheItem;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

class OEmbedCacheItemTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        OEmbedCacheItem::class,
    ];

    public function testTableNameIsSetCorrectly(): void
    {
        // GIVEN the OEmbedCacheItem class
        // WHEN we check the table_name config
        $tableName = OEmbedCacheItem::config()->get('table_name');

        // THEN it should be 'OEmbedCacheItem'
        $this->assertSame('OEmbedCacheItem', $tableName);
    }

    public function testDbFieldsAreDefined(): void
    {
        // GIVEN the OEmbedCacheItem class
        // WHEN we check the db fields
        $db = OEmbedCacheItem::config()->get('db');

        // THEN it should have URL and Response fields
        $this->assertArrayHasKey('URL', $db);
        $this->assertArrayHasKey('Response', $db);
        $this->assertSame('Varchar(255)', $db['URL']);
        $this->assertSame('Text', $db['Response']);
    }

    public function testIndexesAreDefined(): void
    {
        // GIVEN the OEmbedCacheItem class
        // WHEN we check the indexes
        $indexes = OEmbedCacheItem::config()->get('indexes');

        // THEN URL should be indexed
        $this->assertArrayHasKey('URL', $indexes);
        $this->assertTrue($indexes['URL']);
    }

    public function testDataReturnsDecodedJsonObject(): void
    {
        // GIVEN an OEmbedCacheItem with valid JSON response
        $item = OEmbedCacheItem::create();
        $item->URL = 'https://example.com/oembed';
        $item->Response = json_encode(['html' => '<div>embed</div>', 'type' => 'rich']);
        $item->write();

        // WHEN we call data()
        $data = $item->data();

        // THEN it should return a decoded object with the expected properties
        $this->assertIsObject($data);
        $this->assertSame('<div>embed</div>', $data->html);
        $this->assertSame('rich', $data->type);
    }

    public function testDataReturnsNullForEmptyResponse(): void
    {
        // GIVEN an OEmbedCacheItem with no response
        $item = OEmbedCacheItem::create();
        $item->URL = 'https://example.com/oembed';

        // WHEN we call data()
        $data = $item->data();

        // THEN it should return null
        $this->assertNull($data);
    }

    public function testDataReturnsNullForNullResponse(): void
    {
        // GIVEN an OEmbedCacheItem with explicitly null-ish response
        $item = OEmbedCacheItem::create();
        $item->URL = 'https://example.com/oembed';
        $item->Response = '';

        // WHEN we call data()
        $data = $item->data();

        // THEN it should return null
        $this->assertNull($data);
    }

    public function testFetchWithCacheHitReturnsExistingItem(): void
    {
        // GIVEN a cached OEmbedCacheItem for a twitter URL
        $twitterUrl = 'https://twitter.com/test/status/123';
        $expectedOEmbedUrl = 'https://api.twitter.com/1/statuses/oembed.json'
            . '?url=' . rawurlencode($twitterUrl)
            . '&widget_type=tweet';

        $cached = OEmbedCacheItem::create();
        $cached->URL = $expectedOEmbedUrl;
        $cached->Response = json_encode(['html' => '<blockquote>tweet</blockquote>']);
        $cached->write();

        // WHEN we fetch with the same URL and service
        $result = OEmbedCacheItem::fetch([
            'url' => $twitterUrl,
            'service' => 'twitter',
        ]);

        // THEN it should return the cached item
        $this->assertInstanceOf(OEmbedCacheItem::class, $result);
        $this->assertSame($cached->ID, $result->ID);
        $this->assertSame('<blockquote>tweet</blockquote>', $result->data()->html);
    }

    public function testFetchReturnsNullForMissingService(): void
    {
        // GIVEN a configuration with no service and an unrecognised URL
        $conf = ['url' => 'https://example.com/unknown'];

        // WHEN we call fetch
        $result = OEmbedCacheItem::fetch($conf);

        // THEN it should return null
        $this->assertNull($result);
    }

    public function testFetchDetectsServiceFromFacebookUrl(): void
    {
        // GIVEN a cached item for a facebook URL (no service in conf)
        $fbUrl = 'https://www.facebook.com/test/posts/123';
        $expectedOEmbedUrl = 'https://www.facebook.com/plugins/post/oembed.json'
            . '?url=' . rawurlencode($fbUrl);

        $cached = OEmbedCacheItem::create();
        $cached->URL = $expectedOEmbedUrl;
        $cached->Response = json_encode(['html' => '<div>fb embed</div>']);
        $cached->write();

        // WHEN we fetch with a facebook URL but no service
        $result = OEmbedCacheItem::fetch(['url' => $fbUrl]);

        // THEN it should detect facebook service and return the cached item
        $this->assertInstanceOf(OEmbedCacheItem::class, $result);
        $this->assertSame($cached->ID, $result->ID);
    }

    public function testFetchDetectsServiceFromInstagramUrl(): void
    {
        // GIVEN a cached item for an instagram URL (no service in conf)
        $igUrl = 'https://www.instagram.com/p/ABC123/';
        $expectedOEmbedUrl = 'https://api.instagram.com/oembed'
            . '?url=' . rawurlencode($igUrl);

        $cached = OEmbedCacheItem::create();
        $cached->URL = $expectedOEmbedUrl;
        $cached->Response = json_encode(['html' => '<blockquote>ig</blockquote>']);
        $cached->write();

        // WHEN we fetch with an instagram URL but no service
        $result = OEmbedCacheItem::fetch(['url' => $igUrl]);

        // THEN it should detect instagram service and return the cached item
        $this->assertInstanceOf(OEmbedCacheItem::class, $result);
        $this->assertSame($cached->ID, $result->ID);
    }

    public function testExtendsDataObject(): void
    {
        // GIVEN the OEmbedCacheItem class
        // WHEN we check the class hierarchy
        // THEN it should extend DataObject
        $this->assertTrue(is_subclass_of(OEmbedCacheItem::class, DataObject::class));
    }
}
