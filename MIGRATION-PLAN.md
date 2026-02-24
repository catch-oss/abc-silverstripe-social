# Migration Plan: abc-silverstripe-social

## Summary

- **Package**: azt3k/abc-silverstripe-social
- **Type**: B (Silverstripe module)
- **Tier**: 6
- **Risk Level**: High
- **Estimated Scope**: 27 PHP files, 27 classes, 2 YAML configs, 2 templates, 0 existing tests
- **Source Directory**: `code/` (needs rename to `src/`)

## Change Inventory

### Namespace Renames Required

| Old Namespace | New Namespace | Files Affected |
|---|---|---|
| `SilverStripe\ORM\DataExtension` | `SilverStripe\Core\Extension` | `SocialMediaConfig.php`, `SocialMediaPageExtension.php`, `SocialUpdatePageExtension.php` (3 files) |
| `SilverStripe\Dev\BuildTask` | `SilverStripe\PolyExecution\PolyCommand` | All 7 BuildTask files |
| `Silverstripe\SiteConfig\SiteConfig` (typo) | `SilverStripe\SiteConfig\SiteConfig` | 13 files (see SiteConfig Typo section) |
| `Guzzle\Http\Client` | `GuzzleHttp\Client` | `FBUpdate.php` |
| `Guzzle\Plugin\History\HistoryPlugin` | Remove (no equivalent in Guzzle 7) | `FBUpdate.php` |

### SiteConfig Typo Fix (`Silverstripe` -> `SilverStripe`)

13 files use `Silverstripe\SiteConfig\SiteConfig` (lowercase 'S') instead of `SilverStripe\SiteConfig\SiteConfig`:

1. `code/BuildTasks/RetrySyncFacebookImages.php`
2. `code/BuildTasks/SyncFacebook.php`
3. `code/BuildTasks/SyncInstagram.php`
4. `code/BuildTasks/SyncTwitter.php`
5. `code/Controllers/FBAuthenticator.php`
6. `code/Controllers/InstagramAuthenticator.php`
7. `code/Controllers/PostToSocialMedia.php`
8. `code/Controllers/SocialAdmin.php`
9. `code/Controllers/TwitterAuthenticator.php`
10. `code/Extensions/SocialMediaPageExtension.php`
11. `code/Extensions/SocialUpdatePageExtension.php`
12. `code/Objects/SocialHelper.php`
13. `_config.php`

### Composer Dependency Changes

| Package | Current Version | Target Version |
|---|---|---|
| `php` | `^8.1` | `^8.5` |
| `silverstripe/framework` | `^5` | `^6.0` |
| `silverstripe/cms` | `^5` | `^6.0` |
| `silverstripe/crontask` | `^3.0` | `^4.0` |
| `composer/installers` | `^2.2` | `^2.2` (keep) |
| `azt3k/abc-silverstripe` | `dev-feature/ss5-update` | `dev-release/6` |
| `azt3k/abc-silverstripe-taggable` | `dev-feature/ss5-update` | `dev-release/6` |
| `themattharris/tmhoauth` | `dev-feature/ss5-update` | `dev-release/6` |
| `janu-software/facebook-php-sdk` | `dev-fix/ss5-compatibility` | `dev-release/6` |
| `guzzlehttp/guzzle` | `>=7.4.5` | `^7.4.5` |
| `composer-fallback/php-http.client-implementation.guzzle7` | `*@stable` | Remove if unused |
| (NEW) `silverstripe/vendor-plugin` | - | `^3.0` (add to require) |
| (NEW) `phpunit/phpunit` | - | `^11.0` (add to require-dev) |
| (NEW) `silverstripe/recipe-cms` | - | `^6.0` (add to require-dev) |

### API Changes Required

| Pattern | Migration | Files Affected |
|---|---|---|
| `BuildTask` -> `PolyCommand` | Convert 7 build tasks to PolyCommand with `run(InputInterface, PolyOutput): int` | 7 BuildTask files |
| `CronTask` interface | Update to `silverstripe/crontask: ^4.0` interface (verify API compatibility) | `RetrySyncFacebookImages`, `SyncFacebook`, `SyncInstagram`, `SyncTwitter` |
| `DataExtension` -> `Extension` | Change base class import and extends | 3 Extension files |
| `::add_extension()` in `_config.php` | Move to YAML `_config/extensions.yml` with named keys | `_config.php` |
| `Guzzle 3.x` API in `FBUpdate::resolveUrl()` | Rewrite with `GuzzleHttp\Client` (Guzzle 7) | `FBUpdate.php` |
| `FBUpdate_Controller` (SS3 class) | Remove or migrate to proper namespaced controller | `FBUpdate.php` (line 244) |
| `$model` param in `__construct()` | Remove third `$model` parameter (removed in SS6) | `FBUpdate.php`, `InstagramUpdate.php`, `Tweet.php` |
| `doPublish()` | Replace with `publishRecursive()` | `FBUpdate.php`, `InstagramUpdate.php`, `Tweet.php` and their BuildTasks |
| `doRestoreToStage()` | Verify SS6 API and update if needed | `SyncFacebook.php`, `SyncInstagram.php`, `SyncTwitter.php` |
| `ASSETS_PATH` / `ASSETS_DIR` constants | Verify still available in SS6 or replace with `Director::publicFolder()` / `ASSETS_PATH` | `FBUpdate.php`, `InstagramUpdate.php`, `Tweet.php` |
| `ABC_SOCIAL_DIR` constant | Replace with `ModuleLoader` or `ModuleManifest` API | `_config.php`, `SocialAdmin.php` |
| `Config::inst()->get('SocialGlobalConf', ...)` | Use FQCN: `SocialGlobalConf::config()->get(...)` | `_config.php` |
| `SocialAdmin.ss` compat3x tinymce plugin | Remove or replace with SS6-compatible TinyMCE reference | `templates/SocialAdmin.ss` |
| Direct `$_SESSION` usage | Migrate to `$request->getSession()` | `FBAuthenticator.php`, `TwitterAuthenticator.php` |
| Direct `$_REQUEST` usage | Migrate to `$request->getVar()` / `$request->postVar()` | `FBAuthenticator.php`, `InstagramAuthenticator.php`, `TwitterAuthenticator.php` |
| `header()` + `exit` redirects | Use `HTTPResponse` redirect | `FBAuthenticator.php`, `InstagramAuthenticator.php`, `TwitterAuthenticator.php` |
| Missing `$allowed_actions` | Add config for security | `InstagramAuthenticator.php`, `TwitterAuthenticator.php` |

### PHP 8.5 Compatibility Fixes

| Issue | Fix | Files Affected |
|---|---|---|
| Implicit nullable: `\stdClass $update = null` | Add `?` prefix: `?\stdClass $update = null` | `FBUpdate.php`, `InstagramUpdate.php`, `Tweet.php` |
| Implicit nullable: `$member = null` | Add `?` prefix where typed | `FBUpdate.php`, `InstagramUpdate.php`, `Tweet.php` |
| Implicit nullable: `$request = null` | Add `?` prefix where typed | 5 BuildTask `run()` methods |
| Missing return type declarations | Add return types to all methods | ~25 files (virtually every file) |
| Missing visibility modifiers | Add `public` to unmodified methods | `RetrySyncFacebookImages::init()`, `SyncFacebook::run()`, `SyncFacebook::init()` |
| `@@ob_flush()` double suppression | Fix to `@ob_flush()` | `RetrySyncFacebookImages.php` |

### PHPUnit Migration

| Issue | Fix | Files Affected |
|---|---|---|
| No test suite exists | Create test suite from scratch | New `tests/` directory |
| No `phpunit.xml.dist` | Create with SS6 bootstrap | New file |
| No `autoload-dev` for tests | Add PSR-4 mapping for `Azt3k\SS\Social\Tests\` | `composer.json` |

### Config Changes

| File | Change Required |
|---|---|
| `_config.php` | Move `add_extension()` calls to YAML; fix SiteConfig typo; use FQCN for `SocialGlobalConf`; remove `ABC_SOCIAL_DIR` constant |
| `_config/legacy.yml` | Keep as-is (SS3->SS4 migration mapping still needed) |
| `_config/routes.yml` | Keep as-is (routes are valid) |
| (NEW) `_config/extensions.yml` | Create with named extension keys for SiteConfig, Page, FBUpdate, Tweet, InstagramUpdate |

### Directory Structure Changes

| Change | Details |
|---|---|
| Rename `code/` to `src/` | Update PSR-4 autoload path in `composer.json` |
| Fix class/file mismatch | `InstagramUpdateHolder.php` contains class `InstagramHolder` — rename class or file for consistency |

## Risk Assessment

| Area | Risk | Notes |
|---|---|---|
| Namespace renames | Medium | 3 DataExtension + 7 BuildTask renames; SiteConfig typo in 13 files |
| API changes | **High** | 7 BuildTask->PolyCommand conversions (4 with CronTask); Guzzle 3.x rewrite; FBUpdate_Controller removal; `$_SESSION`/`$_REQUEST` cleanup |
| PHP 8.5 compat | Medium | ~16 implicit nullable params; return types needed on ~100+ methods |
| Test migration | **High** | No tests exist — entire test suite must be created from scratch for 80% coverage |
| Config changes | Medium | `_config.php` needs significant rework (imperative -> YAML); compat3x tinymce removal |
| Dependencies | Medium | 4 internal deps (abc-silverstripe, abc-silverstripe-taggable, tmhOAuth, facebook-php-sdk) must be on release/6 first |
| Template changes | Low | Only 2 templates; `SocialAdmin.ss` needs tinymce compat3x fix |

## Migration Steps (Ordered)

### Phase 1: composer.json & Directory Structure
- [ ] Rename `code/` to `src/`
- [ ] Update `composer.json` autoload PSR-4: `"Azt3k\\SS\\Social\\": "src/"`
- [ ] Update PHP requirement to `^8.5`
- [ ] Update `silverstripe/framework` to `^6.0`
- [ ] Update `silverstripe/cms` to `^6.0`
- [ ] Update `silverstripe/crontask` to `^4.0`
- [ ] Update `azt3k/abc-silverstripe` to `dev-release/6`
- [ ] Update `azt3k/abc-silverstripe-taggable` to `dev-release/6`
- [ ] Update `themattharris/tmhoauth` to `dev-release/6`
- [ ] Update `janu-software/facebook-php-sdk` to `dev-release/6`
- [ ] Add `silverstripe/vendor-plugin: ^3.0` to require
- [ ] Add `phpunit/phpunit: ^11.0` to require-dev
- [ ] Add `silverstripe/recipe-cms: ^6.0` to require-dev
- [ ] Add `silverstripe/recipe-plugin: true` to allow-plugins
- [ ] Add `autoload-dev` with PSR-4 for tests and classmap for `app/src/Page.php`, `app/src/PageController.php`
- [ ] Tighten `guzzlehttp/guzzle` to `^7.4.5`
- [ ] Evaluate and remove `composer-fallback/php-http.client-implementation.guzzle7` if unused
- [ ] Run `composer validate`

### Phase 2: Namespace Renames
- [ ] Fix SiteConfig typo (`Silverstripe` -> `SilverStripe`) in 13 files
- [ ] Rename `SilverStripe\ORM\DataExtension` -> `SilverStripe\Core\Extension` in 3 extension files
- [ ] Rename `SilverStripe\Dev\BuildTask` -> `SilverStripe\PolyExecution\PolyCommand` in 7 BuildTask files
- [ ] Replace `Guzzle\Http\Client` and `Guzzle\Plugin\History\HistoryPlugin` with `GuzzleHttp\Client` in `FBUpdate.php`

### Phase 3: API Changes
- [ ] Convert 7 BuildTask classes to PolyCommand API (`run(InputInterface, PolyOutput): int`)
- [ ] Update 4 CronTask implementations for `silverstripe/crontask: ^4.0` interface
- [ ] Change 3 extension base classes from `DataExtension` to `Extension`
- [ ] Rewrite `FBUpdate::resolveUrl()` using Guzzle 7 API
- [ ] Remove `FBUpdate_Controller` class (line 244 of `FBUpdate.php`)
- [ ] Remove `$model` parameter from `__construct()` in `FBUpdate`, `InstagramUpdate`, `Tweet`
- [ ] Replace `doPublish()` with `publishRecursive()` in SiteTree classes and BuildTasks
- [ ] Verify/update `doRestoreToStage()` calls for SS6
- [ ] Verify `ASSETS_PATH` / `ASSETS_DIR` constants in SS6; update if needed
- [ ] Move `_config.php` extension registrations to `_config/extensions.yml` with named keys
- [ ] Replace `ABC_SOCIAL_DIR` constant with `ModuleLoader` API
- [ ] Use FQCN `Azt3k\SS\Social\Objects\SocialGlobalConf::class` in config checks
- [ ] Fix class/file mismatch: `InstagramUpdateHolder.php` / `InstagramHolder` class name
- [ ] Fix `$allowed_children` in `InstagramHolder` to use `InstagramUpdate::class`
- [ ] Add `$allowed_actions` to `InstagramAuthenticator` and `TwitterAuthenticator`
- [ ] Migrate `$_SESSION` -> `$request->getSession()` in `FBAuthenticator`, `TwitterAuthenticator`
- [ ] Migrate `$_REQUEST` -> `$request->getVar()` in `FBAuthenticator`, `InstagramAuthenticator`, `TwitterAuthenticator`
- [ ] Replace `header()` + `exit` with `HTTPResponse` redirects
- [ ] Fix `SocialAdmin.ss` tinymce compat3x reference

### Phase 4: PHP 8.5 Compatibility
- [ ] Add `?` prefix to all implicit nullable parameters (~16 instances)
- [ ] Add return type declarations to all methods (~100+ methods across 25 files)
- [ ] Add missing `public` visibility modifiers to `init()` and `run()` methods
- [ ] Fix `@@ob_flush()` -> `@ob_flush()` in `RetrySyncFacebookImages.php`
- [ ] Fix `PurgeInstagram.php` line 60 typo: `'L$eolive'` should be `'Live'`

### Phase 5: Logging Integration
- [ ] Add `Psr\Log\LoggerInterface` injection via YAML config
- [ ] Replace any `echo`/`print` output in BuildTasks with logger calls (Catch format)
- [ ] Configure Monolog channel `Azt3k.SS.Social` with Catch log format

### Phase 6: Config Updates
- [ ] Create `_config/extensions.yml` with named extension keys:
  ```yaml
  SilverStripe\SiteConfig\SiteConfig:
    extensions:
      social-media-config: Azt3k\SS\Social\Extensions\SocialMediaConfig
  Page:
    extensions:
      social-media-page: Azt3k\SS\Social\Extensions\SocialMediaPageExtension
  Azt3k\SS\Social\SiteTree\FBUpdate:
    extensions:
      social-update: Azt3k\SS\Social\Extensions\SocialUpdatePageExtension
  Azt3k\SS\Social\SiteTree\Tweet:
    extensions:
      social-update: Azt3k\SS\Social\Extensions\SocialUpdatePageExtension
  Azt3k\SS\Social\SiteTree\InstagramUpdate:
    extensions:
      social-update: Azt3k\SS\Social\Extensions\SocialUpdatePageExtension
  ```
- [ ] Slim down `_config.php` to shortcode/HtmlEditorConfig only
- [ ] Verify `_config/legacy.yml` mapping still works in SS6
- [ ] Verify `_config/routes.yml` is valid for SS6

### Phase 7: Test Suite (New — Silverstripe Best Practices)
- [ ] Add `silverstripe/recipe-cms: ^6.0` to require-dev (provides Page/PageController)
- [ ] Add `silverstripe/recipe-plugin: true` to allow-plugins
- [ ] Create `phpunit.xml.dist` with bootstrap `vendor/silverstripe/framework/tests/bootstrap.php`
- [ ] Add recipe-generated files to `.gitignore`: `app/`, `public/`, `.htaccess`, `index.php`, `web.config`
- [ ] Create test directory structure: `tests/`
- [ ] Write tests for DataObjects (`OEmbedCacheItem`, `PublicationFBUpdate`, `PublicationInstagramUpdate`, `PublicationTweet`)
- [ ] Write tests for Extensions (`SocialMediaConfig`, `SocialMediaPageExtension`, `SocialUpdatePageExtension`)
- [ ] Write tests for SiteTree classes (`FBUpdate`, `InstagramUpdate`, `Tweet` and their Holders)
- [ ] Write tests for Controllers (`SocialAdmin`, `FBAuthenticator`, `InstagramAuthenticator`, `TwitterAuthenticator`, `PostToSocialMedia`)
- [ ] Write tests for Objects (`SocialGlobalConf`, `SocialHelper`)
- [ ] Write tests for Client (`InstagramBasicDisplayClient`)
- [ ] Write tests for BuildTasks/PolyCommands (7 classes)
- [ ] All tests use GIVEN/WHEN/THEN comments
- [ ] Use `$usesDatabase = false` for tests that don't need ORM
- [ ] Use `Page::create()` not `SiteTree::create()` for test page instances
- [ ] Extend `SapphireTest` or `FunctionalTest` for all SS-dependent tests
- [ ] Target 80% line coverage minimum
- [ ] Remove `project-files-installed` / `public-files-installed` from composer.json extra if added

## Dependencies

- **Depends on** (must be on release/6 first):
  - Tier 4: `azt3k/abc-silverstripe` (PR_OPEN)
  - Tier 5: `azt3k/abc-silverstripe-taggable` (CI_READY — needs plan + migration)
  - Tier 5: `janu-software/facebook-php-sdk` (PR_OPEN)
  - Tier 1: `themattharris/tmhoauth` (COMPLETE)
- **Blocks**:
  - None (Tier 6 is near the top of the dependency graph)

## Notes

- This is a large, complex module with deep SS integration (SiteTree pages, CMS extensions, CronTasks, admin interface, 3 social media platform integrations)
- The Guzzle 3.x code in `FBUpdate::resolveUrl()` is a significant rewrite
- The `FBUpdate_Controller` at line 244 is an SS3 artifact — verify it's not referenced anywhere before removal
- The `_config.php` is doing substantial imperative work that should move to YAML
- 4 of 7 BuildTasks implement `CronTask` — verify `silverstripe/crontask: ^4.0` API is compatible with PolyCommand
- The InstagramHolder / InstagramUpdateHolder class-file mismatch should be resolved carefully (check for references in config, templates, and DB)
- Direct `$_SESSION` / `$_REQUEST` usage needs careful migration to use SS request/session objects
- No tests exist at all — building to 80% coverage will be the biggest effort item
