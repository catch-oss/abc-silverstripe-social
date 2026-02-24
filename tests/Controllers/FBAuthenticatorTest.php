<?php

namespace Azt3k\SS\Social\Tests\Controllers;

use Azt3k\SS\Social\Controllers\FBAuthenticator;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;

class FBAuthenticatorTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testExtendsController(): void
    {
        // GIVEN the FBAuthenticator class
        // WHEN we check its class hierarchy
        // THEN it should extend Controller
        $this->assertTrue(is_subclass_of(FBAuthenticator::class, Controller::class));
    }

    public function testAllowedActionsIncludesIndex(): void
    {
        // GIVEN an FBAuthenticator class
        // WHEN we check allowed_actions
        $allowedActions = FBAuthenticator::config()->get('allowed_actions');

        // THEN it should include 'index'
        $this->assertContains('index', $allowedActions);
    }

    public function testAllowedActionsIncludesPurge(): void
    {
        // GIVEN an FBAuthenticator class
        // WHEN we check allowed_actions
        $allowedActions = FBAuthenticator::config()->get('allowed_actions');

        // THEN it should include 'purge'
        $this->assertContains('purge', $allowedActions);
    }

    public function testAllowedActionsIncludesExpectedEntries(): void
    {
        // GIVEN an FBAuthenticator class
        // WHEN we check allowed_actions
        $allowedActions = FBAuthenticator::config()->get('allowed_actions');

        // THEN it should include at least index and purge
        $this->assertContains('index', $allowedActions);
        $this->assertContains('purge', $allowedActions);
    }
}
