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
- All `die()` calls replaced with exceptions (BuildTasks) and `httpError()` (Controllers)
- CSRF token generation upgraded to `bin2hex(random_bytes(32))`
- `disable_wysiwyg_embed` config renamed to `disable_shortcode_embed` (legacy key still honoured)

### Removed
- **TinyMCE editor plugin and all associated frontend assets** — Silverstripe 6 no longer bundles
  TinyMCE by default (it is available as an optional package via `silverstripe/htmleditor-tinymce`).
  The old plugin code used TinyMCE 3/4 APIs (`tinymce.create()`, `tinymce.PluginManager`,
  `tinyMCEPopup`, `ed.addButton()`) that are incompatible with the TinyMCE 6 version shipped by
  the optional package. The following files have been deleted:
  - `js/editor-plugin.js` — TinyMCE plugin that added a "Social Embed" toolbar button, opened a
    popup dialog for URL entry, and handled shortcode-to-HTML conversion in the editor via
    `ed.on('SetContent')` / `ed.on('SaveContent')` hooks
  - `js/popup.js` — jQuery-based popup UI that called the `SocialAdmin` controller to fetch oEmbed
    previews and inserted `[social_embed]` shortcodes via `tinyMCEPopup.execCommand('mceInsertContent')`
  - `css/popup.css` — styles for the popup dialog
  - `templates/SocialAdmin.ss` — standalone HTML page served as the TinyMCE popup iframe
  - `src/Controllers/SocialAdmin.php` — controller that served the popup and the `htmlfragment`
    oEmbed preview endpoint (only called by the TinyMCE JS)
  - `_config/routes.yml` `abc-social-admin` route
  - `js/` and `css/` directories (now empty)
- The **`[social_embed]` shortcode handler is preserved** and continues to work. Existing content
  containing `[social_embed,url="..."]` shortcodes will render correctly. Authors can add new
  shortcodes by typing them directly in the HTML editor. See README for usage examples.
- A TinyMCE 6 or TipTap editor extension could be developed as a future enhancement if there is
  demand for a visual embed insertion workflow.

### Added
- Catch logging standard integration (Monolog 3.2+ with `Azt3k.SS.Social` channel)
- MIGRATION-PLAN.md documenting all changes
- Comprehensive `[social_embed]` shortcode documentation in README
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
