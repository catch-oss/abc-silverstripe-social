<?php

namespace Azt3k\SS\Social\BuildTasks;

use JanuSoftware\Facebook\Facebook;
use Azt3k\SS\Classes\DataObjectHelper;
use Azt3k\SS\Social\SiteTree\FBUpdate;
use SilverStripe\Core\Environment;
use SilverStripe\CronTask\Interfaces\CronTask;
use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use SilverStripe\SiteConfig\SiteConfig;


/**
 * Facebook images are not always instantly available so this is a rety job that should look for any
 */
class RetrySyncFacebookImages extends PolyCommand implements CronTask
{

    protected static string $commandName = 'social:retry-sync-facebook-images';
    protected static string $description = 'Retries syncing Facebook images that were not immediately available';

    private static string $schedule = '*/15 * * * *';

    protected static $conf_instance;
    protected $conf;

    public function getTitle(): string
    {
        return 'Retry Sync Facebook Images';
    }

    public function getSchedule(): string
    {
        return static::config()->get('schedule');
    }

    public function getConf(): mixed
    {
        if (!static::$conf_instance) static::$conf_instance = SiteConfig::current_site_config();
        return static::$conf_instance;
    }

    /**
     * adapter for cron task
     */
    public function process(): void
    {
        if (!Environment::getEnv('SS_SOCIAL_SYNC_ENABLED')) {
            return;
        }

        $this->conf = $this->getConf();
        echo "\n\nSyncing\n\n";

        if (!$this->conf->FacebookPullUpdates) {
            echo "Sync disabled\n\n";
            return;
        }

        // find any updates that are less than a week old with no image
        $updates = FBUpdate::get()
            ->where('
                UNIX_TIMESTAMP(OriginalCreated) > ' . (time() - (60 * 60 * 24 * 14)) . ' AND (' .
                DataObjectHelper::versioned_table('Page') . '.PrimaryImageID IS NULL OR ' .
                DataObjectHelper::versioned_table('Page') . '.PrimaryImageID = \'\' OR ' .
                DataObjectHelper::versioned_table('Page') . '.PrimaryImageID = 0
                )
            ');

        echo 'Processing ' . $updates->count() . " updates...\n\n";

        foreach ($updates as $k => $update) {
            $update->updateFromUpdate((object) json_decode($update->OriginalUpdate));
        }
    }

    public function run(InputInterface $input, PolyOutput $output): int
    {

        if (!Environment::getEnv('SS_SOCIAL_SYNC_ENABLED')) {
            $output->writeln('Social sync disabled (SS_SOCIAL_SYNC_ENABLED is not set)');
            return Command::SUCCESS;
        }

        $this->conf = $this->getConf();

        $output->writeln('');
        $output->writeln('Syncing');
        $output->writeln('');

        if (!$this->conf->FacebookPullUpdates) {
            $output->writeln('Sync disabled');
            return Command::SUCCESS;
        }

        // find any updates that are less than a week old with no image
        $updates = FBUpdate::get()
            ->where('
                UNIX_TIMESTAMP(OriginalCreated) > ' . (time() - (60 * 60 * 24 * 14)) . ' AND (' .
                DataObjectHelper::versioned_table('Page') . '.PrimaryImageID IS NULL OR ' .
                DataObjectHelper::versioned_table('Page') . '.PrimaryImageID = \'\' OR ' .
                DataObjectHelper::versioned_table('Page') . '.PrimaryImageID = 0
                )
            ');

        // helpful output
        $output->writeln('Processing ' . $updates->count() . ' updates...');
        $output->writeln('');

        // loop the loop
        foreach ($updates as $k => $update) {
            $update->updateFromUpdate((object) json_decode($update->OriginalUpdate));
        }

        return Command::SUCCESS;
    }
}
