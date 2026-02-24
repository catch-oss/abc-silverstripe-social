<?php

namespace Azt3k\SS\Social\Tests\DataObjects;

use Azt3k\SS\Social\DataObjects\PublicationFBUpdate;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

class PublicationFBUpdateTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        PublicationFBUpdate::class,
    ];

    public function testTableNameIsSetCorrectly(): void
    {
        // GIVEN the PublicationFBUpdate class
        // WHEN we check the table_name config
        $tableName = PublicationFBUpdate::config()->get('table_name');

        // THEN it should be 'PublicationFBUpdate'
        $this->assertSame('PublicationFBUpdate', $tableName);
    }

    public function testDbFieldsAreDefined(): void
    {
        // GIVEN the PublicationFBUpdate class
        // WHEN we check the db fields
        $db = PublicationFBUpdate::config()->get('db');

        // THEN it should have FBUpdateID field
        $this->assertArrayHasKey('FBUpdateID', $db);
        $this->assertSame('Varchar(255)', $db['FBUpdateID']);
    }

    public function testHasOneRelationsAreDefined(): void
    {
        // GIVEN the PublicationFBUpdate class
        // WHEN we check the has_one relations
        $hasOne = PublicationFBUpdate::config()->get('has_one');

        // THEN it should have a Page relation pointing to SiteTree
        $this->assertArrayHasKey('Page', $hasOne);
        $this->assertSame(SiteTree::class, $hasOne['Page']);
    }

    public function testExtendsDataObject(): void
    {
        // GIVEN the PublicationFBUpdate class
        // WHEN we check the class hierarchy
        // THEN it should extend DataObject
        $this->assertTrue(is_subclass_of(PublicationFBUpdate::class, DataObject::class));
    }

    public function testCanCreateInstance(): void
    {
        // GIVEN the PublicationFBUpdate class
        // WHEN we create an instance and set its fields
        $item = PublicationFBUpdate::create();
        $item->FBUpdateID = '123456789';
        $item->write();

        // THEN it should be persisted with the correct value
        $fetched = PublicationFBUpdate::get()->byID($item->ID);
        $this->assertNotNull($fetched);
        $this->assertSame('123456789', $fetched->FBUpdateID);
    }
}
