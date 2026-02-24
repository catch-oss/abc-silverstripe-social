<?php

namespace Azt3k\SS\Social\BuildTasks;

use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Azt3k\SS\Social\SiteTree\FBUpdate;
use SilverStripe\Versioned\Versioned;


/**
 * @author AzT3k
 */
class PurgeFBUpdate extends PolyCommand
{

    protected static string $commandName = 'social:purge-fb-updates';
    protected static string $description = 'Purges all Facebook update pages';

    public function getTitle(): string
    {
        return 'Purge FB Updates';
    }

    public function run(InputInterface $input, PolyOutput $output): int
    {

        $output->writeln('');
        $output->writeln('Purging...');
        $output->writeln('');

        foreach (FBUpdate::get() as $k => $page) {

            $output->writeln("Deleting " . $page->Title);
            $page->delete();
        }

        foreach (Versioned::get_by_stage(FBUpdate::class, 'Stage') as $k => $page) {

            $output->writeln("Deleting From Stage: " . $page->Title);
            $page->deleteFromStage('Stage');
        }

        foreach (Versioned::get_by_stage(FBUpdate::class, 'Live') as $k => $page) {

            $output->writeln("Deleting From Live: " . $page->Title);
            $page->deleteFromStage('Live');
        }

        return Command::SUCCESS;
    }
}
