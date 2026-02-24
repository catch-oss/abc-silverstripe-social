<?php

namespace Azt3k\SS\Social\BuildTasks;

use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Azt3k\SS\Social\SiteTree\Tweet;
use SilverStripe\Versioned\Versioned;

/**
 * @author AzT3k
 */
class PurgeTwitter extends PolyCommand
{

    protected static string $commandName = 'social:purge-twitter';
    protected static string $description = 'Purges all Tweet pages';

    public function getTitle(): string
    {
        return 'Purge Twitter';
    }

    public function run(InputInterface $input, PolyOutput $output): int
    {

        $output->writeln('');
        $output->writeln('Purging...');
        $output->writeln('');

        foreach (Tweet::get() as $k => $page) {

            $output->writeln("Deleting " . $page->Title);
            $page->delete();
        }

        foreach (Versioned::get_by_stage(Tweet::class, 'Stage') as $k => $page) {

            $output->writeln("Deleting From Stage: " . $page->Title);
            $page->deleteFromStage('Stage');
        }

        foreach (Versioned::get_by_stage(Tweet::class, 'Live') as $k => $page) {

            $output->writeln("Deleting From Live: " . $page->Title);
            $page->deleteFromStage('Live');
        }

        return Command::SUCCESS;
    }
}
