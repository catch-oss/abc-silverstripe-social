<?php

namespace Azt3k\SS\Social\Tests\SiteTree;

use Azt3k\SS\Social\SiteTree\FBUpdate;
use Azt3k\SS\Social\SiteTree\FBUpdateHolder;
use SilverStripe\Dev\SapphireTest;

class FBUpdateHolderTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testTableName(): void
    {
        // GIVEN an FBUpdateHolder class
        // WHEN we check the table name
        $tableName = FBUpdateHolder::config()->get('table_name');

        // THEN it should be 'FBUpdateHolder'
        $this->assertSame('FBUpdateHolder', $tableName);
    }

    public function testAllowedChildrenIncludesFBUpdate(): void
    {
        // GIVEN an FBUpdateHolder class
        // WHEN we check the allowed_children config
        $allowedChildren = FBUpdateHolder::config()->get('allowed_children');

        // THEN it should include FBUpdate class
        $this->assertContains(FBUpdate::class, $allowedChildren);
    }

    public function testCanBeRoot(): void
    {
        // GIVEN an FBUpdateHolder class
        // WHEN we check the can_be_root config
        $canBeRoot = FBUpdateHolder::config()->get('can_be_root');

        // THEN it should be true
        $this->assertTrue($canBeRoot);
    }
}
