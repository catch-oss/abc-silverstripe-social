<?php

namespace Azt3k\SS\Social\BuildTasks;

use JanuSoftware\Facebook\Facebook;
use Azt3k\SS\Social\Objects\SocialHelper;
use Azt3k\SS\Classes\DataObjectHelper;
use Azt3k\SS\Social\SiteTree\FBUpdate;
use Azt3k\SS\Social\DataObjects\PublicationFBUpdate;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObject;
use SilverStripe\CMS\Model\SiteTree;

/**
 * @todo need reconcile removals in both directions
 */
class SyncFacebook extends PolyCommand implements CronTask
{

    protected static string $commandName = 'social:sync-facebook';
    protected static string $description = 'Syncs Facebook updates from a configured page';

    protected static $conf_instance;
    protected static $facebook_instance;
    protected $conf;
    protected $facebook;
    protected $errors = array();
    protected $messages = array();

    public function getTitle(): string
    {
        return 'Sync Facebook';
    }

    public function __construct()
    {

        $this->conf     = $this->getConf();
        $this->facebook = $this->getFacebook();

        parent::__construct();
    }

    public function getSchedule(): string
    {
        return "*/5 * * * *";
    }

    public function getConf(): mixed
    {
        if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
        return static::$conf_instance;
    }

    public function getFacebook(): ?Facebook
    {

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

    /**
     * adapter for cron task
     */
    public function process(): void
    {
        if (!$this->conf || !$this->facebook) $this->__construct();

        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';

        echo $eol . $eol . 'Syncing...' . $eol . $eol;
        flush();
        @ob_flush();

        if (!$this->conf->FacebookPullUpdates) {
            echo 'Sync disabled ' . $eol . $eol;
            return;
        }

        $this->doSync(null);
    }

    public function run(InputInterface $input, PolyOutput $output): int
    {

        if (!$this->conf || !$this->facebook) $this->__construct();

        $output->writeln('');
        $output->writeln('Syncing...');
        $output->writeln('');

        if (!$this->conf->FacebookPullUpdates) {
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
        $params['since'] = ($lastUpdate = DataObject::get_one(FBUpdate::class, '', '', 'UpdateID DESC')) ? $lastUpdate->UpdateID  : 1;

        // set the number of hits
        $params['limit'] = 200;

        // if there was no last tweet we need to go into initial population
        $initPop = $lastUpdate ? false : true;

        // get updates
        try {
            $resp = (object) $this->facebook->sendRequest('get', '/' . $this->conf->FacebookPageId . '/' . $this->conf->FacebookPageFeedType)->getDecodedBody();
        } catch (\Exception $e) {
            if ($output) {
                $output->writeln('Caught FB Error:');
                $output->writeln($e->getMessage());
            } else {
                echo 'Caught FB Error: ' . $eol;
                echo $e->getMessage();
            }
            return;
        }

        // only proceed if we have results to work with
        if (count($resp->data)) {

            // process the response
            $this->processResponse($resp->data, $output);

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
                                    '/' . $this->conf->FacebookPageFeedType . '?limit=25&until=' . $until
                            )->getDecodedBody();

                            // only proceed if we have results to work with
                            if (count($resp->data)) {

                                // process the response
                                $noNew = $this->processResponse($resp->data, $output);

                                // break if we haven't added anything
                                if ($noNew) break;
                            }
                        } else {
                            if ($output) {
                                $output->writeln('Encountered Error with : ' . print_r($resp, 1));
                            } else {
                                echo 'Encountered Error with : ' . print_r($resp, 1);
                            }
                        }
                    } else {
                        if ($output) {
                            $output->writeln('No more pages');
                        } else {
                            echo 'No more pages' . $eol . $eol;
                            flush();
                            @ob_flush();
                        }
                        break;
                    }
                }

                if ($output) {
                    $output->writeln('Finished');
                } else {
                    echo 'Finished' . $eol . $eol;
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
    }

    public function processResponse(array $resp, ?PolyOutput $output = null): bool
    {

        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';
        $noNew = true;

        // look at data
        foreach ($resp as $data) {

            // type cast
            $data = (object) $data;

            if (!$savedUpdate = FBUpdate::get()->filter('UpdateID', $data->id)->first()) {
                if (!$pubUpdate = PublicationFBUpdate::get()->filter('FBUpdateID', $data->id)->first()) {

                    if ($output) {
                        $output->writeln('Adding Update ' . $data->id);
                    } else {
                        echo 'Adding Update ' . $data->id . $eol . $eol;
                        flush();
                        @ob_flush();
                    }

                    // create the FBUpdate Page
                    $update = new FBUpdate;
                    if ($update->updateFromUpdate($data)) {
                        if ($update->write() && $update->doRestoreToStage() && $update->publishRecursive()) {
                            if ($output) {
                                $output->writeln('Successfully created' . $update->Title);
                            } else {
                                echo 'Successfully created' . $update->Title . $eol . $eol;
                            }
                        } else {
                            throw new \RuntimeException('Failed to Publish ' . $update->Title);
                        }
                    }

                    // set no new flag
                    $noNew = false;
                } else {

                    if ($output) {
                        $output->writeln('Update ' . $data->id . 'came from the website');
                    } else {
                        echo 'Update ' . $data->id . 'came from the website' . $eol;
                        flush();
                        @ob_flush();
                    }
                }
            } else {

                if ($output) {
                    $output->writeln('Already added Update ' . $data->id);
                } else {
                    echo 'Already added Update ' . $data->id . $eol;
                    flush();
                    @ob_flush();
                }
            }
        }

        return $noNew;
    }
}
