<?php

namespace Azt3k\SS\Social\Tests\BuildTasks;

use Azt3k\SS\Social\BuildTasks\RetrySyncFacebookImages;
use Azt3k\SS\Social\BuildTasks\SyncFacebook;
use Azt3k\SS\Social\BuildTasks\SyncInstagram;
use Azt3k\SS\Social\BuildTasks\SyncTwitter;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class SyncTasksTest extends SapphireTest
{
    protected $usesDatabase = false;

    /**
     * Store the original env value so we can restore it after each test
     */
    private string|false|null $originalEnvValue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalEnvValue = Environment::getEnv('SS_SOCIAL_SYNC_ENABLED');
    }

    protected function tearDown(): void
    {
        // Restore original env value
        if ($this->originalEnvValue === false) {
            Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', '');
        } else {
            Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', $this->originalEnvValue);
        }
        parent::tearDown();
    }

    // ── Environment guard tests (run()) ──

    public function testSyncFacebookRunExitsWhenEnvDisabled(): void
    {
        Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', '');
        $task = new SyncFacebook();
        $result = $this->executeRun($task);

        $this->assertSame(Command::SUCCESS, $result['code']);
        $this->assertStringContainsString('SS_SOCIAL_SYNC_ENABLED is not set', $result['output']);
    }

    public function testRetrySyncFacebookImagesRunExitsWhenEnvDisabled(): void
    {
        Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', '');
        $task = new RetrySyncFacebookImages();
        $result = $this->executeRun($task);

        $this->assertSame(Command::SUCCESS, $result['code']);
        $this->assertStringContainsString('SS_SOCIAL_SYNC_ENABLED is not set', $result['output']);
    }

    public function testSyncTwitterRunExitsWhenEnvDisabled(): void
    {
        Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', '');
        $task = new SyncTwitter();
        $result = $this->executeRun($task);

        $this->assertSame(Command::SUCCESS, $result['code']);
        $this->assertStringContainsString('SS_SOCIAL_SYNC_ENABLED is not set', $result['output']);
    }

    public function testSyncInstagramRunExitsWhenEnvDisabled(): void
    {
        Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', '');
        $task = new SyncInstagram();
        $result = $this->executeRun($task);

        $this->assertSame(Command::SUCCESS, $result['code']);
        $this->assertStringContainsString('SS_SOCIAL_SYNC_ENABLED is not set', $result['output']);
    }

    // ── Environment guard tests (process()) ──

    public function testSyncFacebookProcessExitsWhenEnvDisabled(): void
    {
        Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', '');
        $task = new SyncFacebook();

        ob_start();
        $task->process();
        $output = ob_get_clean();

        // Should produce no output — exits before any echo
        $this->assertEmpty($output);
    }

    public function testRetrySyncFacebookImagesProcessExitsWhenEnvDisabled(): void
    {
        Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', '');
        $task = new RetrySyncFacebookImages();

        ob_start();
        $task->process();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testSyncTwitterProcessExitsWhenEnvDisabled(): void
    {
        Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', '');
        $task = new SyncTwitter();

        ob_start();
        $task->process();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testSyncInstagramProcessExitsWhenEnvDisabled(): void
    {
        Environment::setEnv('SS_SOCIAL_SYNC_ENABLED', '');
        $task = new SyncInstagram();

        ob_start();
        $task->process();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    // ── Configurable schedule tests ──

    public function testSyncFacebookDefaultSchedule(): void
    {
        $task = new SyncFacebook();
        $this->assertSame('*/5 * * * *', $task->getSchedule());
    }

    public function testRetrySyncFacebookImagesDefaultSchedule(): void
    {
        $task = new RetrySyncFacebookImages();
        $this->assertSame('*/15 * * * *', $task->getSchedule());
    }

    public function testSyncTwitterDefaultSchedule(): void
    {
        $task = new SyncTwitter();
        $this->assertSame('*/5 * * * *', $task->getSchedule());
    }

    public function testSyncInstagramDefaultSchedule(): void
    {
        $task = new SyncInstagram();
        $this->assertSame('*/5 * * * *', $task->getSchedule());
    }

    public function testScheduleIsConfigurable(): void
    {
        $customSchedule = '0 */2 * * *';
        SyncFacebook::config()->set('schedule', $customSchedule);

        $task = new SyncFacebook();
        $this->assertSame($customSchedule, $task->getSchedule());
    }

    // ── CronTask interface ──

    public function testAllTasksImplementCronTask(): void
    {
        $classes = [
            SyncFacebook::class,
            RetrySyncFacebookImages::class,
            SyncTwitter::class,
            SyncInstagram::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(
                is_subclass_of($class, \SilverStripe\CronTask\Interfaces\CronTask::class),
                "$class should implement CronTask"
            );
        }
    }

    // ── Helpers ──

    private function executeRun(object $task): array
    {
        $input = new ArrayInput([]);
        $buffered = new BufferedOutput();
        $output = new PolyOutput(
            PolyOutput::FORMAT_ANSI,
            OutputInterface::VERBOSITY_NORMAL,
            false,
            $buffered
        );

        $code = $task->run($input, $output);

        return [
            'code' => $code,
            'output' => $buffered->fetch(),
        ];
    }
}
