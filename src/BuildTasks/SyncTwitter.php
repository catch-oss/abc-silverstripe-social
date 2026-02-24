<?php

namespace Azt3k\SS\Social\BuildTasks;

use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Azt3k\SS\Social\SiteTree\Tweet;
use Azt3k\SS\Social\DataObjects\PublicationTweet;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObject;
use themattharris\tmhOAuth;

/**
 * @todo need reconcile removals in both directions
 */
class SyncTwitter extends PolyCommand implements CronTask
{

    protected static string $commandName = 'social:sync-twitter';
    protected string $title = 'Sync Twitter';
    protected static string $description = 'Syncs Twitter updates from a configured account';

    protected static $conf_instance;
    protected static $tmh_oauth_instance;
    protected $conf;
    protected $tmhOAuth;
    protected $errors = array();
    protected $messages = array();

    public function __construct()
    {

        $this->conf        = $this->getConf();
        $this->tmhOAuth = $this->getTmhOauth();

        parent::__construct();
    }

    public function getSchedule()
    {
        return "*/5 * * * *";
    }

    public function getConf()
    {
        if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
        return static::$conf_instance;
    }

    public function getTmhOauth()
    {

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

    /**
     * adapter for cron task
     */
    public function process()
    {
        if (!$this->conf || !$this->tmhOAuth) $this->__construct();

        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';

        echo $eol . $eol . 'Syncing...' . $eol . $eol;
        flush();
        @ob_flush();

        if (!$this->conf->TwitterPullUpdates) {
            echo 'Sync disabled' . $eol . $eol;
            return;
        }

        $this->doSync(null);
    }

    public function run(InputInterface $input, PolyOutput $output): int
    {

        if (!$this->conf || !$this->tmhOAuth) $this->__construct();

        $output->writeln('');
        $output->writeln('Syncing...');
        $output->writeln('');

        if (!$this->conf->TwitterPullUpdates) {
            $output->writeln('Sync disabled');
            return Command::SUCCESS;
        }

        $this->doSync($output);

        return Command::SUCCESS;
    }

    /**
     * Shared sync logic used by both run() and process()
     */
    protected function doSync(?PolyOutput $output): void
    {
        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';

        // grab the most recent tweet
        $params = array();
        if ($lastTweet = DataObject::get_one(Tweet::class, '', '', 'TweetID DESC')) {
            if ($lastTweet->TweetID) $params['since_id'] = $lastTweet->TweetID;
        }

        // set the number of hits
        $params['count'] = 200;

        // if there was no last tweet we need to go into initial population
        $initPop = $lastTweet ? false : true;

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
                $this->processResponse($resp, $output);

                // check if we need to do an initial population
                if ($initPop) {

                    if ($output) {
                        $output->writeln('');
                        $output->writeln('Doing initial Population');
                        $output->writeln('');
                    } else {
                        echo $eol . $eol . 'Doing initial Population' . $eol . $eol;
                        flush();
                        @ob_flush();
                    }

                    // keep going until we hit a problem
                    while ($code == 200 && count($resp)) {

                        // find the earliest tweet we have in the db
                        $firstTweet = DataObject::get_one(Tweet::class, '', '', 'TweetID ASC');

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
                                $noNew = $this->processResponse($resp, $output);

                                // break if we haven't added anything
                                if ($noNew) break;
                            }
                        }
                    }

                    if ($output) {
                        $output->writeln('Finished');
                    } else {
                        echo "Finished";
                        flush();
                        @ob_flush();
                    }
                }
            } else {

                if ($output) {
                    $output->writeln('No hits');
                } else {
                    echo 'No hits' . $eol . $eol;
                    flush();
                    @ob_flush();
                }
            }
        } else {

            die($code . " : " . $this->tmhOAuth->response['response']);
        }
    }

    public function processResponse(array $resp, ?PolyOutput $output = null)
    {

        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';

        $noNew = true;

        foreach ($resp as $tweetData) {
            if (!$savedTweet = DataObject::get_one(Tweet::class, "TweetID='" . $tweetData->id_str . "'")) {
                if (!$pubTweet = DataObject::get_one(PublicationTweet::class, "TweetID='" . $tweetData->id_str . "'")) {

                    if ($output) {
                        $output->writeln('Adding Tweet ' . $tweetData->id_str);
                    } else {
                        echo 'Adding Tweet ' . $tweetData->id_str . $eol;
                        flush();
                        @ob_flush();
                    }

                    // create the Tweet Page
                    $tweet = new Tweet;
                    $tweet->updateFromTweet($tweetData);
                    if ($tweet->write() && $tweet->doRestoreToStage() && $tweet->publishRecursive()) {
                        if ($output) {
                            $output->writeln('Successfully created' . $tweet->Title);
                        } else {
                            echo 'Successfully created' . $tweet->Title . $eol;
                        }
                    } else {
                        die('Failed to Publish ' . $tweet->Title);
                    }

                    // set no new flag
                    $noNew = false;
                } else {

                    if ($output) {
                        $output->writeln('Tweet ' . $tweetData->id_str . ' came from the website');
                    } else {
                        echo 'Tweet ' . $tweetData->id_str . ' came from the website' . $eol;
                        flush();
                        @ob_flush();
                    }
                }
            } else {

                if ($output) {
                    $output->writeln('Already added Tweet ' . $tweetData->id_str);
                } else {
                    echo 'Already added Tweet ' . $tweetData->id_str . $eol;
                    flush();
                    @ob_flush();
                }
            }
        }

        return $noNew;
    }

    public function getOptions(): array
    {
        return [];
    }
}
