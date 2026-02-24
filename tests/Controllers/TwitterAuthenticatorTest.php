<?php

namespace Azt3k\SS\Social\Tests\Controllers;

use Azt3k\SS\Social\Controllers\TwitterAuthenticator;
use SilverStripe\Control\Controller;
use SilverStripe\Dev\SapphireTest;

class TwitterAuthenticatorTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testExtendsController(): void
    {
        // GIVEN the TwitterAuthenticator class
        // WHEN we check its class hierarchy
        // THEN it should extend Controller
        $this->assertTrue(is_subclass_of(TwitterAuthenticator::class, Controller::class));
    }

    public function testAllowedActionsIncludesIndex(): void
    {
        // GIVEN a TwitterAuthenticator class
        // WHEN we check allowed_actions
        $allowedActions = TwitterAuthenticator::config()->get('allowed_actions');

        // THEN it should include 'index'
        $this->assertContains('index', $allowedActions);
    }

    public function testAllowedActionsIncludesExpectedEntries(): void
    {
        // GIVEN a TwitterAuthenticator class
        // WHEN we check allowed_actions
        $allowedActions = TwitterAuthenticator::config()->get('allowed_actions');

        // THEN it should include at least index
        $this->assertContains('index', $allowedActions);
    }
}
