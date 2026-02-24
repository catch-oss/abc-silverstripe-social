<?php

namespace Azt3k\SS\Social\Tests\Extensions;

use Azt3k\SS\Social\Extensions\SocialUpdatePageExtension;
use Azt3k\SS\Social\SiteTree\FBUpdate;
use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use Azt3k\SS\Social\SiteTree\Tweet;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\SapphireTest;

class SocialUpdatePageExtensionTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testExtendsExtension(): void
    {
        // GIVEN the SocialUpdatePageExtension class
        // WHEN we check the class hierarchy
        // THEN it should extend Extension
        $this->assertTrue(is_subclass_of(SocialUpdatePageExtension::class, Extension::class));
    }

    public function testUpdateTypeReturnsTwitterForTweet(): void
    {
        // GIVEN a SocialUpdatePageExtension with a Tweet owner
        $mockOwner = new \stdClass();
        $mockOwner->ClassName = Tweet::class;

        $ext = new SocialUpdatePageExtension();
        $refProp = new \ReflectionProperty(Extension::class, 'owner');
        $refProp->setValue($ext, $mockOwner);

        // WHEN we call UpdateType()
        $result = $ext->UpdateType();

        // THEN it should return 'Twitter'
        $this->assertSame('Twitter', $result);
    }

    public function testUpdateTypeReturnsFacebookForFBUpdate(): void
    {
        // GIVEN a SocialUpdatePageExtension with an FBUpdate owner
        $mockOwner = new \stdClass();
        $mockOwner->ClassName = FBUpdate::class;

        $ext = new SocialUpdatePageExtension();
        $refProp = new \ReflectionProperty(Extension::class, 'owner');
        $refProp->setValue($ext, $mockOwner);

        // WHEN we call UpdateType()
        $result = $ext->UpdateType();

        // THEN it should return 'Facebook'
        $this->assertSame('Facebook', $result);
    }

    public function testUpdateTypeReturnsInstagramForInstagramUpdate(): void
    {
        // GIVEN a SocialUpdatePageExtension with an InstagramUpdate owner
        $mockOwner = new \stdClass();
        $mockOwner->ClassName = InstagramUpdate::class;

        $ext = new SocialUpdatePageExtension();
        $refProp = new \ReflectionProperty(Extension::class, 'owner');
        $refProp->setValue($ext, $mockOwner);

        // WHEN we call UpdateType()
        $result = $ext->UpdateType();

        // THEN it should return 'Instagram'
        $this->assertSame('Instagram', $result);
    }

    public function testUpdateTypeReturnsNullForUnknownClass(): void
    {
        // GIVEN a SocialUpdatePageExtension with an unknown class owner
        $mockOwner = new \stdClass();
        $mockOwner->ClassName = 'SomeOtherClass';

        $ext = new SocialUpdatePageExtension();
        $refProp = new \ReflectionProperty(Extension::class, 'owner');
        $refProp->setValue($ext, $mockOwner);

        // WHEN we call UpdateType()
        $result = $ext->UpdateType();

        // THEN it should return null
        $this->assertNull($result);
    }
}
