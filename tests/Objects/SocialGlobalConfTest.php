<?php

namespace Azt3k\SS\Social\Tests\Objects;

use Azt3k\SS\Social\Objects\SocialGlobalConf;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\SapphireTest;

class SocialGlobalConfTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testUsesExtensibleTrait(): void
    {
        // GIVEN the SocialGlobalConf class
        // WHEN we check for the Extensible trait
        $traits = class_uses(SocialGlobalConf::class);

        // THEN it should use the Extensible trait
        $this->assertContains(Extensible::class, $traits);
    }

    public function testUsesInjectableTrait(): void
    {
        // GIVEN the SocialGlobalConf class
        // WHEN we check for the Injectable trait
        $traits = class_uses(SocialGlobalConf::class);

        // THEN it should use the Injectable trait
        $this->assertContains(Injectable::class, $traits);
    }

    public function testUsesConfigurableTrait(): void
    {
        // GIVEN the SocialGlobalConf class
        // WHEN we check for the Configurable trait
        $traits = class_uses(SocialGlobalConf::class);

        // THEN it should use the Configurable trait
        $this->assertContains(Configurable::class, $traits);
    }

    public function testDisableShortcodeEmbedConfigExists(): void
    {
        // GIVEN the SocialGlobalConf class
        // WHEN we check for the disable_shortcode_embed config
        $ref = new \ReflectionClass(SocialGlobalConf::class);
        $prop = $ref->getProperty('disable_shortcode_embed');

        // THEN the config key should exist as a private static property
        $this->assertTrue($prop->isPrivate());
        $this->assertTrue($prop->isStatic());
    }

    public function testLegacyDisableWysiwygEmbedConfigExists(): void
    {
        // GIVEN the SocialGlobalConf class
        // WHEN we check for the legacy disable_wysiwyg_embed config
        $ref = new \ReflectionClass(SocialGlobalConf::class);
        $prop = $ref->getProperty('disable_wysiwyg_embed');

        // THEN the legacy config key should still exist for backwards compatibility
        $this->assertTrue($prop->isPrivate());
        $this->assertTrue($prop->isStatic());
    }

    public function testCanBeInstantiated(): void
    {
        // GIVEN the SocialGlobalConf class
        // WHEN we create a new instance
        $conf = new SocialGlobalConf();

        // THEN it should be a valid instance
        $this->assertInstanceOf(SocialGlobalConf::class, $conf);
    }

    public function testCanBeCreatedViaInjectable(): void
    {
        // GIVEN the SocialGlobalConf class uses Injectable
        // WHEN we use the create() factory method
        $conf = SocialGlobalConf::create();

        // THEN it should return a valid instance
        $this->assertInstanceOf(SocialGlobalConf::class, $conf);
    }
}
