<?php

namespace Azt3k\SS\Social\Tests\DataObjects;

use Azt3k\SS\Social\DataObjects\PublicationInstagramUpdate;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

class PublicationInstagramUpdateTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        PublicationInstagramUpdate::class,
    ];

    public function testTableNameIsSetCorrectly(): void
    {
        // GIVEN the PublicationInstagramUpdate class
        // WHEN we check the table_name config
        $tableName = PublicationInstagramUpdate::config()->get('table_name');

        // THEN it should be 'PublicationInstagramUpdate'
        $this->assertSame('PublicationInstagramUpdate', $tableName);
    }

    public function testDbFieldsAreDefined(): void
    {
        // GIVEN the PublicationInstagramUpdate class
        // WHEN we check the db fields
        $db = PublicationInstagramUpdate::config()->get('db');

        // THEN it should have InstagramUpdateID field
        $this->assertArrayHasKey('InstagramUpdateID', $db);
        $this->assertSame('Varchar(255)', $db['InstagramUpdateID']);
    }

    public function testHasOneRelationsAreDefined(): void
    {
        // GIVEN the PublicationInstagramUpdate class
        // WHEN we check the has_one relations
        $hasOne = PublicationInstagramUpdate::config()->get('has_one');

        // THEN it should have a Page relation pointing to SiteTree
        $this->assertArrayHasKey('Page', $hasOne);
        $this->assertSame(SiteTree::class, $hasOne['Page']);
    }

    public function testExtendsDataObject(): void
    {
        // GIVEN the PublicationInstagramUpdate class
        // WHEN we check the class hierarchy
        // THEN it should extend DataObject
        $this->assertTrue(is_subclass_of(PublicationInstagramUpdate::class, DataObject::class));
    }

    public function testCanCreateInstance(): void
    {
        // GIVEN the PublicationInstagramUpdate class
        // WHEN we create an instance and set its fields
        $item = PublicationInstagramUpdate::create();
        $item->InstagramUpdateID = 'ig_987654321';
        $item->write();

        // THEN it should be persisted with the correct value
        $fetched = PublicationInstagramUpdate::get()->byID($item->ID);
        $this->assertNotNull($fetched);
        $this->assertSame('ig_987654321', $fetched->InstagramUpdateID);
    }
}
