<?php

namespace Azt3k\SS\Social\BuildTasks;

use Azt3k\SS\Social\Clients\InstagramBasicDisplayClient;
use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use Azt3k\SS\Social\DataObjects\PublicationInstagramUpdate;
use Azt3k\SS\Social\Objects\SocialHelper;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\DataObject;


// need to update to this
// https://developers.facebook.com/docs/instagram-basic-display-api - get posts
// https://developers.facebook.com/docs/instagram-api - make posts

class SyncInstagram extends PolyCommand implements CronTask
{

    protected static string $commandName = 'social:sync-instagram';
    protected string $title = 'Sync Instagram';
    protected static string $description = 'Syncs Instagram updates from a configured account';

    protected static $conf_instance;
    protected static $instagram_instance;
    protected $conf;
    protected $instagram;
    protected $errors = array();
    protected $messages = array();

    public function __construct()
    {

        $this->conf = static::get_conf();
        $this->instagram = static::get_instagram();

        parent::__construct();
    }

    public function getSchedule(): string
    {
        return "*/5 * * * *";
    }

    public static function get_conf(): mixed
    {
        if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
        return static::$conf_instance;
    }

    public static function get_instagram(): InstagramBasicDisplayClient
    {
        if (!static::$instagram_instance) {
            $conf = static::get_conf();
            static::$instagram_instance = new InstagramBasicDisplayClient(
                (string) $conf->InstagramApiKey,
                (string) $conf->InstagramApiSecret,
                SocialHelper::php_self()
            );
        }

        return static::$instagram_instance;
    }

    /**
     * adapter for cron task
     */
    public function process(): void
    {
        if (!$this->conf || !$this->instagram) $this->__construct();

        $eol = php_sapi_name() === 'cli' ? "\n" : '<br>';

        echo $eol . $eol . 'Syncing...' . $eol . $eol;
        flush();
        @ob_flush();

        if (!$this->conf->InstagramPullUpdates) {
            echo 'Sync disabled' . $eol . $eol;
            return;
        }

        if (!$this->conf->InstagramOAuthToken) {
            echo 'No Instagram OAuth token configured' . $eol . $eol;
            return;
        }

        $this->refreshAccessTokenIfNeeded();
        $this->doSync(null);
    }

    public function run(InputInterface $input, PolyOutput $output): int
    {

        if (!$this->conf || !$this->instagram) $this->__construct();

        $output->writeln('');
        $output->writeln('Syncing...');
        $output->writeln('');

        if (!$this->conf->InstagramPullUpdates) {
            $output->writeln('Sync disabled');
            return Command::SUCCESS;
        }

        if (!$this->conf->InstagramOAuthToken) {
            $output->writeln('No Instagram OAuth token configured');
            return Command::SUCCESS;
        }

        $this->refreshAccessTokenIfNeeded();
        $this->doSync($output);

        return Command::SUCCESS;
    }

    /**
     * Shared sync logic used by both run() and process()
     */
    protected function doSync(?PolyOutput $output): void
    {
        // grab the most recent InstagramUpdate
        $lastInstagramUpdate = DataObject::get_one(InstagramUpdate::class);

        // if there was no last InstagramUpdate we need to go into initial population
        $initPop = $lastInstagramUpdate ? false : true;

        // get the first 90
        $updates = $this->instagram->getUserMedia((string) $this->conf->InstagramOAuthToken, 90);

        if (!empty($updates['data'])) {

            // process the response
            $this->processResponse($updates['data'], $output);

            // check if we need to do an initial population
            if ($initPop) {

                if ($output) {
                    $output->writeln('');
                    $output->writeln('Doing initial Population');
                    $output->writeln('');
                } else {
                    echo "<br />\n<br />\nDoing initial Population<br />\n<br />\n";
                    flush();
                    @ob_flush();
                }

                $next = $updates['paging']['next'] ?? null;
                while ($next) {
                    $updates = $this->instagram->getMediaPage($next);
                    if (!empty($updates['data'])) {
                        $this->processResponse($updates['data'], $output);
                    }
                    $next = $updates['paging']['next'] ?? null;
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
                echo "No hits <br />\n<br />\n";
                flush();
                @ob_flush();
            }
        }
    }

    protected function refreshAccessTokenIfNeeded(): void
    {
        if (!$this->conf->InstagramOAuthToken || !$this->conf->InstagramOAuthTokenExpires) {
            return;
        }

        $expiresAt = strtotime($this->conf->InstagramOAuthTokenExpires);
        if ($expiresAt && $expiresAt <= time() + 604800) {
            $refresh = $this->instagram->refreshAccessToken((string) $this->conf->InstagramOAuthToken);
            if (!empty($refresh['access_token'])) {
                $this->conf->InstagramOAuthToken = $refresh['access_token'];
                $this->conf->InstagramOAuthTokenExpires = !empty($refresh['expires_in'])
                    ? date('Y-m-d H:i:s', time() + (int) $refresh['expires_in'])
                    : $this->conf->InstagramOAuthTokenExpires;
                $this->conf->write();
            }
        }
    }

    public function processResponse(array $updates, ?PolyOutput $output = null): bool
    {

        $noNew = true;

        foreach ($updates as $data) {
            if (is_array($data)) {
                $data = json_decode(json_encode($data));
            }
            if (!$savedInstagramUpdate = DataObject::get_one(InstagramUpdate::class, "UpdateID='" . $data->id . "'")) {
                if (!$pubInstagramUpdate = DataObject::get_one(PublicationInstagramUpdate::class, "InstagramUpdateID='" . $data->id . "'")) {

                    if ($output) {
                        $output->writeln("Adding InstagramUpdate " . $data->id);
                    } else {
                        echo "Adding InstagramUpdate " . $data->id . "<br />\n";
                        flush();
                        @ob_flush();
                    }

                    // create the InstagramUpdate Page
                    $update = new InstagramUpdate;

                    // try to update
                    if ($update->updateFromUpdate($data)) {
                        if ($update->write() && $update->doRestoreToStage() && $update->publishRecursive()) {
                            if ($output) {
                                $output->writeln('Successfully created' . $update->Title);
                            } else {
                                echo 'Successfully created' . $update->Title . "<br />\n";
                            }
                        } else {
                            die('Failed to Publish ' . $update->Title);
                        }
                    }

                    // set no new flag
                    $noNew = false;
                } else {

                    if ($output) {
                        $output->writeln("InstagramUpdate " . $data->id . " came from the website");
                    } else {
                        echo "InstagramUpdate " . $data->id . " came from the website<br />\n";
                        flush();
                        @ob_flush();
                    }
                }
            } else {

                if ($output) {
                    $output->writeln("Already added InstagramUpdate " . $data->id);
                } else {
                    echo "Already added InstagramUpdate " . $data->id . "<br />\n";
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
