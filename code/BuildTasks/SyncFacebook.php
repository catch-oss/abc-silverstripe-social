<?php

namespace Azt3k\SS\Social\BuildTasks;

use Facebook\Facebook;
use Azt3k\SS\Social\Objects\SocialHelper;
use Azt3k\SS\Classes\DataObjectHelper;
use Azt3k\SS\Social\SiteTree\FBUpdate;
use Azt3k\SS\Social\DataObjects\PublicationFBUpdate;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use Silverstripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Model\SiteTree;

/**
 * @todo need reconcile removals in both directions
 */
class SyncFacebook extends BuildTask implements CronTask {

    protected static $conf_instance;
    protected static $facebook_instance;
    protected $conf;
    protected $facebook;
    protected $errors = array();
    protected $messages = array();

    public function __construct() {

        $this->conf     = $this->getConf();
        $this->facebook = $this->getFacebook();

        parent::__construct();
    }

    protected function flushStatements()
    {
        $conn = \SilverStripe\ORM\DB::get_conn();
        $connector = $conn->getConnector();
        $connector->flushStatements();
    }

    public function getSchedule() {
        return "*/5 * * * *";
    }

    public function getConf() {
        if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
        return static::$conf_instance;
    }

    public function getFacebook() {

        if (!$this->conf) $this->conf = $this->getConf();

        if (!static::$facebook_instance) {
            if (!empty($this->conf->FacebookAppId) && !empty($this->conf->FacebookAppSecret)) {

                // init fb
                static::$facebook_instance = new Facebook(array(
                    'app_id'  => $this->conf->FacebookAppId,
                    'app_secret' => $this->conf->FacebookAppSecret
                ));

                // get access token
                $token = (string) SocialHelper::fb_access_token();

                // set token
                static::$facebook_instance->setDefaultAccessToken($token);
            }
        }

        return static::$facebook_instance;
    }

    function init() {

        if (method_exists(parent::class, 'init')) parent::init();

        if (!Director::is_cli() && !Permission::check("ADMIN") && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
            return Security::permissionFailure();
        }

        if (!$this->conf || !$this->facebook) $this->__construct();

    }

    public function process() {
        $this->init();
        $this->run();
    }

    function run($request = null) {

        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';

        // output
        echo $eol . $eol . 'Syncing...' . $eol . $eol;
        flush();
        @ob_flush();

        if (!$this->conf->FacebookPullUpdates) {
            echo 'Sync disabled ' . $eol . $eol;
            return;
        }

        // flush first to avoid hitting prepared statements cap
        $this->flushStatements();

        // grab the most recent tweet
        $params = array();
        $params['since'] = ($lastUpdate = DataObject::get_one(FBUpdate::class,'','','UpdateID DESC')) ? $lastUpdate->UpdateID  : 1 ;

        // set the number of hits
        $params['limit'] = 200;

        // if there was no last tweet we need to go into initial population
        $initPop = $lastUpdate ? false : true ;

        // get updates
        try {
            $resp = (object) $this->facebook->sendRequest('get', '/' . $this->conf->FacebookPageId . '/' . $this->conf->FacebookPageFeedType)->getDecodedBody();
        } catch (\Exception $e) {
            echo 'Caught FB Error: ' . $eol;
            echo $e->getMessage();
            return;
        }

        // die(print_r($resp,1));

        // only proceed if we have results to work with
        if (count($resp->data)) {

            // process the response
            $this->processResponse($resp->data);

            // check if we need to do an initial population
            if ($initPop) {

                //output
                echo $eol . $eol . 'Doing initial Population' . $eol . $eol;
                flush();
                @ob_flush();

                // keep going until we hit a problem
                while (count($resp)) {

                    // only proceed if we have paging data
                    if (!empty($resp->paging)) {

                        // parse url
                        $parsed = (object) parse_url($resp->paging['next']);

                        // only proceed if we have the query string params
                        if (!empty($parsed->query)) {

                            // parse the query
                            parse_str($parsed->query, $q);
                            $q = (object) $q;

                            // get tweets
                            $until = empty($q->until) ? '' : $q->until;
                            $resp = (object) $this->facebook->sendRequest(
                                'get',
                                '/' . $this->conf->FacebookPageId .
                                '/' . $this->conf->FacebookPageFeedType. '?limit=25&until=' . $until
                            )->getDecodedBody();

                            // only proceed if we have results to work with
                            if (count($resp->data)) {

                                // process the response
                                $noNew = $this->processResponse($resp->data);

                                // break if we haven't added anything
                                if ($noNew) break;

                            }
                        } else {
                            echo 'Encountered Error with : ' . print_r($resp,1);
                        }

                    } else{
                        // output
                        echo 'No more pages' . $eol . $eol;
                        flush();
                        @ob_flush();
                        break;
                    }

                 }

                // output
                echo 'Finished' . $eol . $eol;
                flush();
                @ob_flush();
            }

        } else {

            // output
            echo 'No hits' . $eol . $eol;
            flush();
            @ob_flush();

        }

    }

    public function processResponse(array $resp) {

        $noNew = true;

        // flush first to avoid hitting prepared statements cap
        $this->flushStatements();

        // look at data
        foreach ($resp as $data) {

            // type cast
            $data = (object) $data;

            if (!$savedUpdate = DataObject::get_one(FBUpdate::class, "UpdateID='" . $data->id . "'")) {
                if (!$pubUpdate = DataObject::get_one(PublicationFBUpdate::class, "FBUpdateID='" . $data->id . "'")) {

                    // push output
                    echo 'Adding Update ' . $data->id . $eol . $eol;
                    flush();
                    @ob_flush();

                    // get extended info
                    // $res = (object) $this->facebook->sendRequest('get', '/' . $data->id)->getDecodedBody();
                    // die(print_r($res,1));

                    // create the FBUpdate Page
                    $update = new FBUpdate;
                    if ($update->updateFromUpdate($data)) {
                        if ($update->write() && $update->doRestoreToStage() && $update->doPublish()) {
                            echo 'Successfully created' . $update->Title . $eol . $eol;
                        } else {
                            die('Failed to Publish ' . $update->Title);
                        }
                    }

                    // set no new flag
                    $noNew = false;

                } else {

                    // push output
                    echo 'Update ' . $data->id . 'came from the website' . $eol;
                    flush();
                    @ob_flush();

                }
            } else {

                // this should only happen during initial population because we should have only got in tweets that are newer than x

                // push output
                echo 'Already added Update ' . $data->id . $eol;
                flush();
                @ob_flush();

            }
        }

        return $noNew;

    }

}
