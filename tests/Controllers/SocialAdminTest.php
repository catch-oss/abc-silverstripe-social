<?php

namespace Azt3k\SS\Social\Tests\Controllers;

use Azt3k\SS\Social\Controllers\SocialAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;

class SocialAdminTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testExtendsController(): void
    {
        // GIVEN the SocialAdmin class
        // WHEN we check its class hierarchy
        // THEN it should extend Controller
        $this->assertTrue(is_subclass_of(SocialAdmin::class, Controller::class));
    }

    public function testAllowedActionsIncludesIndex(): void
    {
        // GIVEN a SocialAdmin class
        // WHEN we check allowed_actions
        $allowedActions = SocialAdmin::config()->get('allowed_actions');

        // THEN it should include 'index'
        $this->assertContains('index', $allowedActions);
    }

    public function testAllowedActionsIncludesHtmlFragment(): void
    {
        // GIVEN a SocialAdmin class
        // WHEN we check allowed_actions
        $allowedActions = SocialAdmin::config()->get('allowed_actions');

        // THEN it should include 'htmlfragment'
        $this->assertContains('htmlfragment', $allowedActions);
    }

    public function testModuleDirReturnsString(): void
    {
        // GIVEN a SocialAdmin instance
        $admin = SocialAdmin::create();

        // WHEN we call ModuleDir
        $result = $admin->ModuleDir();

        // THEN it should return a string
        $this->assertIsString($result);
    }
}
