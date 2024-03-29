<?php

namespace Azt3k\SS\Social\BuildTasks;

use SilverStripe\Dev\BuildTask;
use Azt3k\SS\Social\SiteTree\Tweet;
use Azt3k\SS\Social\DataObjects\PublicationTweet;
use Azt3k\SS\Classes\DataObjectHelper;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Security\Security;
use SilverStripe\Security\Permission;
use SilverStripe\Control\Director;
use Silverstripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObject;
use tmhOAuth;

/**
 * @todo need reconcile removals in both directions
 */
class SyncTwitter extends BuildTask implements CronTask{

    protected static $conf_instance;
    protected static $tmh_oauth_instance;
    protected $conf;
    protected $tmhOAuth;
    protected $errors = array();
    protected $messages = array();

    public function __construct() {

        $this->conf        = $this->getConf();
        $this->tmhOAuth = $this->getTmhOauth();

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

    public function getTmhOauth() {

        if (!$this->conf) $this->conf = $this->getConf();

        if (!static::$tmh_oauth_instance) {
            static::$tmh_oauth_instance = new tmhOAuth(array(
                'consumer_key'        => $this->conf->TwitterConsumerKey,
                'consumer_secret'    => $this->conf->TwitterConsumerSecret,
            ));
            static::$tmh_oauth_instance->config['user_token']        = $this->conf->TwitterOAuthToken;
            static::$tmh_oauth_instance->config['user_secret']    = $this->conf->TwitterOAuthSecret;
        }

        return static::$tmh_oauth_instance;
    }

    public function init() {

        if (method_exists(parent::class,'init')) parent::init();

        if (!Director::is_cli() && !Permission::check("ADMIN") && $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
            return Security::permissionFailure();
        }

        if (!$this->conf || !$this->tmhOAuth) $this->__construct();

    }

    public function process() {
        $this->init();
        $this->run();
    }

    public function run($request = null) {

        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';

        // output
        echo $eol . $eol . 'Syncing...' . $eol . $eol;
        flush();
        @ob_flush();

        if (!$this->conf->TwitterPullUpdates) {
            echo 'Sync disabled' . $eol . $eol;
            return;
        }

        // flush first to avoid hitting prepared statements cap
        $this->flushStatements();

        // grab the most recent tweet
        $params = array();
        if ($lastTweet = DataObject::get_one(Tweet::class,'','','TweetID DESC')) {
            if ($lastTweet->TweetID) $params['since_id'] = $lastTweet->TweetID;
        }

        // set the number of hits
        $params['count'] = 200;

        // if there was no last tweet we need to go into initial population
        $initPop = $lastTweet ? false : true ;

        // get tweets
        $code = $this->tmhOAuth->request(
            'GET',
            $this->tmhOAuth->url('1.1/statuses/user_timeline'),
            $params
        );

        // only proceed if the request was valid
        if ($code == 200) {

            // decode the response
            $resp = json_decode($this->tmhOAuth->response['response']);

            // only proceed if we have results to work with
            if (count($resp)) {

                // process the response
                $this->processResponse($resp);

                // check if we need to do an initial population
                if ($initPop) {

                    //output
                    echo $eol . $eol . 'Doing initial Population' . $eol . $eol;
                    flush();
                    @ob_flush();

                     // keep going until we hit a problem
                     while ($code == 200 && count($resp)) {

                        // find the earliest tweet we have in the db
                        $firstTweet = DataObject::get_one(Tweet::class,'','','TweetID ASC');

                        // reconfigure the params
                        unset($params['since_id']);
                        $params['max_id'] = $firstTweet->TweetID;

                        // get tweets
                        $code = $this->tmhOAuth->request(
                            'GET',
                            $this->tmhOAuth->url('1.1/statuses/user_timeline'),
                            $params
                        );

                        // only proceed if the request was valid
                        if ($code == 200) {

                            // decode the response
                            $resp = json_decode($this->tmhOAuth->response['response']);

                            // only proceed if we have results to work with
                            if (count($resp)) {

                                // process the response
                                $noNew = $this->processResponse($resp);

                                // break if we haven't added anything
                                if ($noNew) break;

                            }
                        }

                     }

                    // output
                    echo "Finished";
                    flush();
                    @ob_flush();
                }

            } else {

                // output
                echo 'No hits' . $eol . $eol;
                flush();
                @ob_flush();

            }

        } else {

            die($code." : ".$this->tmhOAuth->response['response']);

        }

    }

    public function processResponse(array $resp) {

        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';

        // flush first to avoid hitting prepared statements cap
        $this->flushStatements();

        $noNew = true;

        foreach ($resp as $tweetData) {
            if (!$savedTweet = DataObject::get_one(Tweet::class, "TweetID='" . $tweetData->id_str . "'")) {
                if (!$pubTweet = DataObject::get_one(PublicationTweet::class, "TweetID='" . $tweetData->id_str . "'")) {

                    // push output
                    echo 'Adding Tweet ' . $tweetData->id_str . $eol;
                    flush();
                    @ob_flush();

                    // create the Tweet Page
                    $tweet = new Tweet;
                    $tweet->updateFromTweet($tweetData);
                    if ($tweet->write() && $tweet->doRestoreToStage() && $tweet->doPublish()) {
                        echo 'Successfully created' . $tweet->Title . $eol;
                    } else {
                        die('Failed to Publish ' . $tweet->Title);
                    }

                    // set no new flag
                    $noNew = false;

                } else {

                    // push output
                    echo 'Tweet ' . $tweetData->id_str . ' came from the website' . $eol;
                    flush();
                    @ob_flush();

                }

            } else {

                // this should only happen during initial population because we should have only got in tweets that are newer than x

                // push output
                echo 'Already added Tweet ' . $tweetData->id_str . $eol;
                flush();
                @ob_flush();

            }
        }

        return $noNew;

    }

}
