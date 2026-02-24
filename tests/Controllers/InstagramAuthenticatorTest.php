<?php

namespace Azt3k\SS\Social\Tests\Controllers;

use Azt3k\SS\Social\Controllers\InstagramAuthenticator;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;

class InstagramAuthenticatorTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testExtendsController(): void
    {
        // GIVEN the InstagramAuthenticator class
        // WHEN we check its class hierarchy
        // THEN it should extend Controller
        $this->assertTrue(is_subclass_of(InstagramAuthenticator::class, Controller::class));
    }

    public function testAllowedActionsIncludesIndex(): void
    {
        // GIVEN an InstagramAuthenticator class
        // WHEN we check allowed_actions
        $allowedActions = InstagramAuthenticator::config()->get('allowed_actions');

        // THEN it should include 'index'
        $this->assertContains('index', $allowedActions);
    }

    public function testAllowedActionsIncludesExpectedEntries(): void
    {
        // GIVEN an InstagramAuthenticator class
        // WHEN we check allowed_actions
        $allowedActions = InstagramAuthenticator::config()->get('allowed_actions');

        // THEN it should include at least index
        $this->assertContains('index', $allowedActions);
    }
}
