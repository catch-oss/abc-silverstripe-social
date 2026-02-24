<?php

namespace Azt3k\SS\Social\Tests\SiteTree;

use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use Azt3k\SS\Social\SiteTree\InstagramUpdateHolder;
use SilverStripe\Dev\SapphireTest;

class InstagramUpdateHolderTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testTableName(): void
    {
        // GIVEN an InstagramUpdateHolder class
        // WHEN we check the table name
        $tableName = InstagramUpdateHolder::config()->get('table_name');

        // THEN it should be 'InstagramHolder' (legacy name preserved)
        $this->assertSame('InstagramHolder', $tableName);
    }

    public function testAllowedChildrenIncludesInstagramUpdate(): void
    {
        // GIVEN an InstagramUpdateHolder class
        // WHEN we check the allowed_children config
        $allowedChildren = InstagramUpdateHolder::config()->get('allowed_children');

        // THEN it should include InstagramUpdate class
        $this->assertContains(InstagramUpdate::class, $allowedChildren);
    }

    public function testCanBeRoot(): void
    {
        // GIVEN an InstagramUpdateHolder class
        // WHEN we check the can_be_root config
        $canBeRoot = InstagramUpdateHolder::config()->get('can_be_root');

        // THEN it should be true
        $this->assertTrue($canBeRoot);
    }
}
