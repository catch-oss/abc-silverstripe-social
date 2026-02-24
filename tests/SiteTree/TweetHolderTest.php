<?php

namespace Azt3k\SS\Social\Tests\SiteTree;

use Azt3k\SS\Social\SiteTree\Tweet;
use Azt3k\SS\Social\SiteTree\TweetHolder;
use SilverStripe\Dev\SapphireTest;

class TweetHolderTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testTableName(): void
    {
        // GIVEN a TweetHolder class
        // WHEN we check the table name
        $tableName = TweetHolder::config()->get('table_name');

        // THEN it should be 'TweetHolder'
        $this->assertSame('TweetHolder', $tableName);
    }

    public function testAllowedChildrenIncludesTweet(): void
    {
        // GIVEN a TweetHolder class
        // WHEN we check the allowed_children config
        $allowedChildren = TweetHolder::config()->get('allowed_children');

        // THEN it should include Tweet class
        $this->assertContains(Tweet::class, $allowedChildren);
    }

    public function testCanBeRoot(): void
    {
        // GIVEN a TweetHolder class
        // WHEN we check the can_be_root config
        $canBeRoot = TweetHolder::config()->get('can_be_root');

        // THEN it should be true
        $this->assertTrue($canBeRoot);
    }
}
