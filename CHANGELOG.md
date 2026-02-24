# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Changed
- Upgraded to Silverstripe 6 compatibility
- Updated PHP requirement to ^8.5
- Migrated test suite to PHPUnit 11
- Renamed source directory from `code/` to `src/` (PSR-4 convention)
- BuildTask classes migrated to PolyCommand (SS6 replacement)
- DataExtension references migrated to Extension
- SiteConfig extension typo fixed (Silverstripe -> SilverStripe)
- `addFieldsToTab()` single-field calls changed to `addFieldToTab()` (SS6 API)
- Guzzle 3.x `resolveUrl()` rewritten with Guzzle 7 redirect tracking
- `doPublish()` calls replaced with `publishRecursive()`
- Extension registration moved from `_config.php` to YAML with named keys
- **Breaking**: `SocialMediaConfig` and `SocialMediaPageExtension` no longer auto-applied to `SiteConfig`/`Page` — projects must opt in via YAML (see README)
- TinyMCE editor plugin removed (TinyMCE no longer bundled in SS6)
- All `die()` calls replaced with exceptions (BuildTasks) and `httpError()` (Controllers)
- CSRF token generation upgraded to `bin2hex(random_bytes(32))`

### Added
- Catch logging standard integration (Monolog 3.2+ with `Azt3k.SS.Social` channel)
- MIGRATION-PLAN.md documenting all changes
- 166 PHPUnit 11 tests across 21 test files
- `.gitignore` for vendor, recipe-generated files
- `phpunit.xml.dist` with SS framework bootstrap

### Fixed
- SQL injection risk: raw WHERE clauses replaced with ORM `filter()` across 9 call sites
- `header()`/`exit` in authenticators replaced with SS framework `$this->redirect()`
- Variable shadowing: error display used wrong variable in SocialMediaConfig
- File path sanitization added to image download operations
- PHP 8.5 compatibility (implicit nullable params, return types on ~100 methods)
- PSR-4 class/file name mismatch (`PurgeFBUpdates.php` -> `PurgeFBUpdate.php`)
- Logic error in `AssociatedImage()`: `&&` changed to `||` for null check
- `SocialHelper::link()` now returns null for null/empty IDs
- Upload field name mismatch (`DefaultInstagramImage` -> `DefaultInstagramUpdateImage`)
- `InstagramUpdateHolder` class renamed from `InstagramHolder` (kept `$table_name` for DB compat)
- `FBUpdate_Controller` removed (SS3 artifact)
