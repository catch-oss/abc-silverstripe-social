<?php

namespace Azt3k\SS\Social\Tests\DataObjects;

use Azt3k\SS\Social\DataObjects\PublicationTweet;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;

class PublicationTweetTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        PublicationTweet::class,
    ];

    public function testTableNameIsSetCorrectly(): void
    {
        // GIVEN the PublicationTweet class
        // WHEN we check the table_name config
        $tableName = PublicationTweet::config()->get('table_name');

        // THEN it should be 'PublicationTweet'
        $this->assertSame('PublicationTweet', $tableName);
    }

    public function testDbFieldsAreDefined(): void
    {
        // GIVEN the PublicationTweet class
        // WHEN we check the db fields
        $db = PublicationTweet::config()->get('db');

        // THEN it should have TweetID field
        $this->assertArrayHasKey('TweetID', $db);
        $this->assertSame('Varchar(255)', $db['TweetID']);
    }

    public function testHasOneRelationsAreDefined(): void
    {
        // GIVEN the PublicationTweet class
        // WHEN we check the has_one relations
        $hasOne = PublicationTweet::config()->get('has_one');

        // THEN it should have a Page relation pointing to SiteTree
        $this->assertArrayHasKey('Page', $hasOne);
        $this->assertSame(SiteTree::class, $hasOne['Page']);
    }

    public function testExtendsDataObject(): void
    {
        // GIVEN the PublicationTweet class
        // WHEN we check the class hierarchy
        // THEN it should extend DataObject
        $this->assertTrue(is_subclass_of(PublicationTweet::class, DataObject::class));
    }

    public function testCanCreateInstance(): void
    {
        // GIVEN the PublicationTweet class
        // WHEN we create an instance and set its fields
        $item = PublicationTweet::create();
        $item->TweetID = 'tweet_123456';
        $item->write();

        // THEN it should be persisted with the correct value
        $fetched = PublicationTweet::get()->byID($item->ID);
        $this->assertNotNull($fetched);
        $this->assertSame('tweet_123456', $fetched->TweetID);
    }
}
