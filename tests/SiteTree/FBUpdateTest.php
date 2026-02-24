<?php

namespace Azt3k\SS\Social\Tests\SiteTree;

use Azt3k\SS\Social\SiteTree\FBUpdate;
use Azt3k\SS\Social\SiteTree\FBUpdateHolder;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;

class FBUpdateTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static conf between tests
        FBUpdate::set_conf([]);
    }

    public function testTableName(): void
    {
        // GIVEN an FBUpdate class
        // WHEN we check the table name
        $tableName = FBUpdate::config()->get('table_name');

        // THEN it should be 'FBUpdate'
        $this->assertSame('FBUpdate', $tableName);
    }

    public function testDbFieldsAreDefined(): void
    {
        // GIVEN an FBUpdate class
        // WHEN we check the db fields
        $db = FBUpdate::config()->get('db');

        // THEN UpdateID, OriginalCreated, and OriginalUpdate should be defined
        $this->assertArrayHasKey('UpdateID', $db);
        $this->assertArrayHasKey('OriginalCreated', $db);
        $this->assertArrayHasKey('OriginalUpdate', $db);
        $this->assertSame('Varchar(255)', $db['UpdateID']);
        $this->assertSame('Datetime', $db['OriginalCreated']);
        $this->assertSame('Text', $db['OriginalUpdate']);
    }

    public function testHasOneIncludesPrimaryImage(): void
    {
        // GIVEN an FBUpdate class
        // WHEN we check the has_one relations
        $hasOne = FBUpdate::config()->get('has_one');

        // THEN PrimaryImage should point to Image class
        $this->assertArrayHasKey('PrimaryImage', $hasOne);
        $this->assertSame(Image::class, $hasOne['PrimaryImage']);
    }

    public function testGetConfReturnsObject(): void
    {
        // GIVEN a fresh FBUpdate with default conf
        // WHEN we call get_conf
        $conf = FBUpdate::get_conf();

        // THEN it should return an object with defaults merged in
        $this->assertIsObject($conf);
        $this->assertSame('FBUpdateHolder', $conf->holder_class);
    }

    public function testSetConfMergesConfiguration(): void
    {
        // GIVEN a custom configuration array
        $customConf = ['holder_class' => 'CustomHolder', 'extra_key' => 'extra_value'];

        // WHEN we set it via set_conf
        FBUpdate::set_conf($customConf);
        $conf = FBUpdate::get_conf();

        // THEN the configuration should be merged
        $this->assertSame('CustomHolder', $conf->holder_class);
        $this->assertSame('extra_value', $conf->extra_key);
    }

    public function testSetConfAcceptsObject(): void
    {
        // GIVEN a configuration as an object
        $customConf = (object) ['holder_class' => 'ObjectHolder'];

        // WHEN we set it via set_conf
        FBUpdate::set_conf($customConf);
        $conf = FBUpdate::get_conf();

        // THEN the configuration should be applied
        $this->assertSame('ObjectHolder', $conf->holder_class);
    }

    public function testOriginalLinkReturnsExpectedFormat(): void
    {
        // GIVEN an FBUpdate with an UpdateID and a FacebookPageId configured
        $config = SiteConfig::current_site_config();
        $config->FacebookPageId = '123456';
        $config->write();

        $update = FBUpdate::create();
        $update->UpdateID = '123456_789012';

        // WHEN we call OriginalLink
        $link = $update->OriginalLink();

        // THEN it should return a Facebook post URL
        $this->assertSame('https://www.facebook.com/123456/posts/789012', $link);
    }

    public function testResolveUrlReturnsString(): void
    {
        // GIVEN an FBUpdate instance
        $update = FBUpdate::create();

        // WHEN we call resolveUrl with a URL that will fail (no network in tests)
        $result = $update->resolveUrl('http://example.com');

        // THEN it should return a string (the original URL on failure)
        $this->assertIsString($result);
        $this->assertSame('http://example.com', $result);
    }

    public function testExpandUpdateDataReturnsSelf(): void
    {
        // GIVEN an FBUpdate instance with OriginalUpdate JSON
        $update = FBUpdate::create();
        $update->OriginalUpdate = json_encode(['message' => 'Hello', 'id' => '123']);

        // WHEN we call expandUpdateData
        $result = $update->expandUpdateData();

        // THEN it should return the same instance (static)
        $this->assertInstanceOf(FBUpdate::class, $result);
        $this->assertSame($update, $result);
    }

    public function testExpandUpdateDataWithExplicitData(): void
    {
        // GIVEN an FBUpdate instance and explicit update data
        $update = FBUpdate::create();
        $data = new \stdClass();
        $data->message = 'Explicit data';
        $data->id = '456';

        // WHEN we call expandUpdateData with data
        $result = $update->expandUpdateData($data);

        // THEN it should return self
        $this->assertSame($update, $result);
    }

    public function testGetCMSFieldsReturnsFieldList(): void
    {
        // GIVEN an FBUpdate instance
        $update = FBUpdate::create();

        // WHEN we call getCMSFields
        $fields = $update->getCMSFields();

        // THEN it should return a FieldList
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    public function testGetCMSFieldsIncludesOriginalCreated(): void
    {
        // GIVEN an FBUpdate instance
        $update = FBUpdate::create();

        // WHEN we call getCMSFields
        $fields = $update->getCMSFields();

        // THEN it should contain the OriginalCreated field
        $this->assertNotNull($fields->dataFieldByName('OriginalCreated'));
    }
}
