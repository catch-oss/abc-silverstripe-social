<?php

namespace Azt3k\SS\Social\BuildTasks;

use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Azt3k\SS\Social\SiteTree\InstagramUpdate;
use SilverStripe\Versioned\Versioned;


/**
 * @author AzT3k
 */
class PurgeInstagram extends PolyCommand
{

    protected static string $commandName = 'social:purge-instagram';
    protected string $title = 'Purge Instagram';
    protected static string $description = 'Purges all Instagram update pages';

    public function run(InputInterface $input, PolyOutput $output): int
    {

        $output->writeln('');
        $output->writeln('Purging...');
        $output->writeln('');

        foreach (InstagramUpdate::get() as $k => $page) {

            $output->writeln("Deleting " . $page->Title);
            $page->delete();
        }

        foreach (Versioned::get_by_stage(InstagramUpdate::class, 'Stage') as $k => $page) {

            $output->writeln("Deleting From Stage: " . $page->Title);
            $page->deleteFromStage('Stage');
        }

        foreach (Versioned::get_by_stage(InstagramUpdate::class, 'Live') as $k => $page) {

            $output->writeln("Deleting From Live: " . $page->Title);
            $page->deleteFromStage('Live');
        }

        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [];
    }
}
