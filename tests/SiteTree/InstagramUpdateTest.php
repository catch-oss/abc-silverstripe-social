<?php

namespace Azt3k\SS\Social\Tests\SiteTree;

use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use Azt3k\SS\Social\SiteTree\InstagramUpdateHolder;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class InstagramUpdateTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static conf between tests
        InstagramUpdate::set_conf([]);
    }

    public function testTableName(): void
    {
        // GIVEN an InstagramUpdate class
        // WHEN we check the table name
        $tableName = InstagramUpdate::config()->get('table_name');

        // THEN it should be 'InstagramUpdate'
        $this->assertSame('InstagramUpdate', $tableName);
    }

    public function testDbFieldsAreDefined(): void
    {
        // GIVEN an InstagramUpdate class
        // WHEN we check the db fields
        $db = InstagramUpdate::config()->get('db');

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
        // GIVEN an InstagramUpdate class
        // WHEN we check the has_one relations
        $hasOne = InstagramUpdate::config()->get('has_one');

        // THEN PrimaryImage should point to Image class
        $this->assertArrayHasKey('PrimaryImage', $hasOne);
        $this->assertSame(Image::class, $hasOne['PrimaryImage']);
    }

    public function testGetConfReturnsObject(): void
    {
        // GIVEN a fresh InstagramUpdate with default conf
        // WHEN we call get_conf
        $conf = InstagramUpdate::get_conf();

        // THEN it should return an object with defaults merged in
        $this->assertIsObject($conf);
        $this->assertSame(InstagramUpdateHolder::class, $conf->holder_class);
    }

    public function testSetConfMergesConfiguration(): void
    {
        // GIVEN a custom configuration array
        $customConf = ['holder_class' => 'CustomHolder', 'extra_key' => 'extra_value'];

        // WHEN we set it via set_conf
        InstagramUpdate::set_conf($customConf);
        $conf = InstagramUpdate::get_conf();

        // THEN the configuration should be merged
        $this->assertSame('CustomHolder', $conf->holder_class);
        $this->assertSame('extra_value', $conf->extra_key);
    }

    public function testSetConfAcceptsObject(): void
    {
        // GIVEN a configuration as an object
        $customConf = (object) ['holder_class' => 'ObjectHolder'];

        // WHEN we set it via set_conf
        InstagramUpdate::set_conf($customConf);
        $conf = InstagramUpdate::get_conf();

        // THEN the configuration should be applied
        $this->assertSame('ObjectHolder', $conf->holder_class);
    }

    public function testOriginalLinkReturnsNullWithoutData(): void
    {
        // GIVEN an InstagramUpdate without OriginalUpdate data
        $update = InstagramUpdate::create();

        // WHEN we call OriginalLink
        $link = $update->OriginalLink();

        // THEN it should return null
        $this->assertNull($link);
    }

    public function testOriginalLinkReturnsPermalink(): void
    {
        // GIVEN an InstagramUpdate with OriginalUpdate containing a permalink
        $update = InstagramUpdate::create();
        $update->OriginalUpdate = json_encode(['permalink' => 'https://www.instagram.com/p/ABC123/']);

        // WHEN we call OriginalLink
        $link = $update->OriginalLink();

        // THEN it should return the permalink
        $this->assertSame('https://www.instagram.com/p/ABC123/', $link);
    }

    public function testOriginalLinkFallsBackToLink(): void
    {
        // GIVEN an InstagramUpdate with OriginalUpdate containing a link but no permalink
        $update = InstagramUpdate::create();
        $update->OriginalUpdate = json_encode(['link' => 'https://www.instagram.com/p/XYZ789/']);

        // WHEN we call OriginalLink
        $link = $update->OriginalLink();

        // THEN it should return the link field
        $this->assertSame('https://www.instagram.com/p/XYZ789/', $link);
    }

    public function testExpandUpdateDataReturnsSelf(): void
    {
        // GIVEN an InstagramUpdate instance with OriginalUpdate JSON
        $update = InstagramUpdate::create();
        $update->OriginalUpdate = json_encode(['caption' => 'Hello', 'id' => '123']);

        // WHEN we call expandUpdateData
        $result = $update->expandUpdateData();

        // THEN it should return the same instance (static)
        $this->assertInstanceOf(InstagramUpdate::class, $result);
        $this->assertSame($update, $result);
    }

    public function testExpandUpdateDataWithExplicitData(): void
    {
        // GIVEN an InstagramUpdate instance and explicit update data
        $update = InstagramUpdate::create();
        $data = new \stdClass();
        $data->caption = 'Explicit data';
        $data->id = '456';

        // WHEN we call expandUpdateData with data
        $result = $update->expandUpdateData($data);

        // THEN it should return self
        $this->assertSame($update, $result);
    }

    public function testGetCMSFieldsReturnsFieldList(): void
    {
        // GIVEN an InstagramUpdate instance
        $update = InstagramUpdate::create();

        // WHEN we call getCMSFields
        $fields = $update->getCMSFields();

        // THEN it should return a FieldList
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    public function testGetCMSFieldsIncludesOriginalCreated(): void
    {
        // GIVEN an InstagramUpdate instance
        $update = InstagramUpdate::create();

        // WHEN we call getCMSFields
        $fields = $update->getCMSFields();

        // THEN it should contain the OriginalCreated field
        $this->assertNotNull($fields->dataFieldByName('OriginalCreated'));
    }
}
