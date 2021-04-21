<?php

namespace Azt3k\SS\Social\BuildTasks;

use SilverStripe\Control\Director;
use Azt3k\SS\Social\SiteTree\Tweet;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Dev\BuildTask;

/**
 * @author AzT3k
 */
class PurgeTwitter extends BuildTask {

    protected function flushStatements()
    {
        $conn = \SilverStripe\ORM\DB::get_conn();
        $connector = $conn->getConnector();
        $connector->flushStatements();
    }

    public function init() {

        parent::init();

        if (!Director::is_cli() && !Permission::check("ADMIN") && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
            return Security::permissionFailure();
        }

    }

    public function process() {
        $this->init();
        $this->run();
    }

    public function run($request = null) {
        // eol
        $eol = php_sapi_name() == 'cli' ? "\n" : "<br>\n";

        // output
        echo $eol . $eol . 'Purging...' . $eol . $eol;
        flush();
        @ob_flush();

        foreach(Tweet::get() as $k => $page) {

            // make sure we dont hit the prepared statements cap
            if ($k % 1000 == 0) {
                $this->flushStatements();
            }

            echo "Deleting " . $page->Title . $eol;
            $page->delete();
        }

        foreach(Versioned::get_by_stage(Tweet::class, 'Stage') as $k => $page) {

            // make sure we dont hit the prepared statements cap
            if ($k % 1000 == 0) {
                $this->flushStatements();
            }

            echo "Deleting From Stage: " . $page->Title . $eol;
            $page->deleteFromStage('Stage');
        }

        foreach(Versioned::get_by_stage(Tweet::class, 'Live') as $k => $page) {

            // make sure we dont hit the prepared statements cap
            if ($k % 1000 == 0) {
                $this->flushStatements();
            }

            echo "Deleting From Live: " . $page->Title . $eol;
            $page->deleteFromStage('Live');
        }

    }
}
