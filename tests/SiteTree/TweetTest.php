<?php

namespace Azt3k\SS\Social\Tests\SiteTree;

use Azt3k\SS\Social\SiteTree\Tweet;
use Azt3k\SS\Social\SiteTree\TweetHolder;
use SilverStripe\Assets\Image;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\SiteConfig\SiteConfig;

class TweetTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        SiteConfig::class => [\Azt3k\SS\Social\Extensions\SocialMediaConfig::class],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static conf between tests
        Tweet::set_conf([]);
    }

    public function testTableName(): void
    {
        // GIVEN a Tweet class
        // WHEN we check the table name
        $tableName = Tweet::config()->get('table_name');

        // THEN it should be 'Tweet'
        $this->assertSame('Tweet', $tableName);
    }

    public function testDbFieldsAreDefined(): void
    {
        // GIVEN a Tweet class
        // WHEN we check the db fields
        $db = Tweet::config()->get('db');

        // THEN TweetID, OriginalCreated, and OriginalTweet should be defined
        $this->assertArrayHasKey('TweetID', $db);
        $this->assertArrayHasKey('OriginalCreated', $db);
        $this->assertArrayHasKey('OriginalTweet', $db);
        $this->assertSame('Varchar(255)', $db['TweetID']);
        $this->assertSame('Datetime', $db['OriginalCreated']);
        $this->assertSame('Text', $db['OriginalTweet']);
    }

    public function testHasOneIncludesPrimaryImage(): void
    {
        // GIVEN a Tweet class
        // WHEN we check the has_one relations
        $hasOne = Tweet::config()->get('has_one');

        // THEN PrimaryImage should point to Image class
        $this->assertArrayHasKey('PrimaryImage', $hasOne);
        $this->assertSame(Image::class, $hasOne['PrimaryImage']);
    }

    public function testGetConfReturnsObject(): void
    {
        // GIVEN a fresh Tweet with default conf
        // WHEN we call get_conf
        $conf = Tweet::get_conf();

        // THEN it should return an object with defaults merged in
        $this->assertIsObject($conf);
        $this->assertSame(TweetHolder::class, $conf->holder_class);
    }

    public function testSetConfMergesConfiguration(): void
    {
        // GIVEN a custom configuration array
        $customConf = ['holder_class' => 'CustomHolder', 'extra_key' => 'extra_value'];

        // WHEN we set it via set_conf
        Tweet::set_conf($customConf);
        $conf = Tweet::get_conf();

        // THEN the configuration should be merged
        $this->assertSame('CustomHolder', $conf->holder_class);
        $this->assertSame('extra_value', $conf->extra_key);
    }

    public function testSetConfAcceptsObject(): void
    {
        // GIVEN a configuration as an object
        $customConf = (object) ['holder_class' => 'ObjectHolder'];

        // WHEN we set it via set_conf
        Tweet::set_conf($customConf);
        $conf = Tweet::get_conf();

        // THEN the configuration should be applied
        $this->assertSame('ObjectHolder', $conf->holder_class);
    }

    public function testOriginalLinkReturnsExpectedFormat(): void
    {
        // GIVEN a Tweet with a TweetID and a TwitterUsername configured
        $config = SiteConfig::current_site_config();
        $config->TwitterUsername = 'testuser';
        $config->write();

        $tweet = Tweet::create();
        $tweet->TweetID = '123456789';

        // WHEN we call OriginalLink
        $link = $tweet->OriginalLink();

        // THEN it should return a Twitter status URL
        $this->assertSame('https://twitter.com/testuser/status/123456789', $link);
    }

    public function testExpandTweetDataReturnsSelf(): void
    {
        // GIVEN a Tweet instance with OriginalTweet JSON
        $tweet = Tweet::create();
        $tweet->OriginalTweet = json_encode(['text' => 'Hello world', 'id_str' => '123']);

        // WHEN we call expandTweetData
        $result = $tweet->expandTweetData();

        // THEN it should return the same instance (static)
        $this->assertInstanceOf(Tweet::class, $result);
        $this->assertSame($tweet, $result);
    }

    public function testExpandTweetDataWithExplicitData(): void
    {
        // GIVEN a Tweet instance and explicit tweet data
        $tweet = Tweet::create();
        $data = new \stdClass();
        $data->text = 'Explicit tweet';
        $data->id_str = '456';

        // WHEN we call expandTweetData with data
        $result = $tweet->expandTweetData($data);

        // THEN it should return self
        $this->assertSame($tweet, $result);
    }

    public function testGetCMSFieldsReturnsFieldList(): void
    {
        // GIVEN a Tweet instance
        $tweet = Tweet::create();

        // WHEN we call getCMSFields
        $fields = $tweet->getCMSFields();

        // THEN it should return a FieldList
        $this->assertInstanceOf(FieldList::class, $fields);
    }

    public function testGetCMSFieldsIncludesOriginalCreated(): void
    {
        // GIVEN a Tweet instance
        $tweet = Tweet::create();

        // WHEN we call getCMSFields
        $fields = $tweet->getCMSFields();

        // THEN it should contain the OriginalCreated field
        $this->assertNotNull($fields->dataFieldByName('OriginalCreated'));
    }

    public function testContentMethodReturnsDBField(): void
    {
        // GIVEN a Tweet instance with content containing a URL
        $tweet = Tweet::create();
        $tweet->Content = 'Check out https://example.com for details';

        // WHEN we call Content()
        $result = $tweet->Content();

        // THEN it should return a DBField instance
        $this->assertInstanceOf(DBField::class, $result);
    }

    public function testContentMethodLinksUrls(): void
    {
        // GIVEN a Tweet instance with content containing a URL
        $tweet = Tweet::create();
        $tweet->Content = 'Visit https://example.com now';

        // WHEN we call Content()
        $result = $tweet->Content();

        // THEN the URL should be wrapped in an anchor tag
        $this->assertStringContainsString('<a href="https://example.com">https://example.com</a>', (string) $result);
    }

    public function testContentMethodLinksUserMentions(): void
    {
        // GIVEN a Tweet instance with an @mention
        $tweet = Tweet::create();
        $tweet->Content = 'Hello @testuser check this';

        // WHEN we call Content()
        $result = $tweet->Content();

        // THEN the mention should be wrapped in a Twitter link
        $this->assertStringContainsString('<a href="http://twitter.com/testuser">@testuser</a>', (string) $result);
    }

    public function testContentMethodLinksHashtags(): void
    {
        // GIVEN a Tweet instance with a hashtag
        $tweet = Tweet::create();
        $tweet->Content = 'Great day #sunshine ahead';

        // WHEN we call Content()
        $result = $tweet->Content();

        // THEN the hashtag should be wrapped in a search link
        $this->assertStringContainsString('<a href="http://search.twitter.com/search?q=%23sunshine">#sunshine</a>', (string) $result);
    }
}
