# TODO: TinyMCE 6 Social Embed Editor Plugin

## Context

The old TinyMCE 3/4 editor plugin was removed during the SS6 migration because it used
incompatible APIs (`tinymce.create()`, `ed.addButton()`, `tinyMCEPopup`). Silverstripe 6
offers TinyMCE as an optional package via `silverstripe/htmleditor-tinymce` which ships
TinyMCE 6. A rewrite using the modern TinyMCE 6 API would restore the "Social Embed"
toolbar button for CMS authors.

The `[social_embed]` shortcode handler and `OEmbedCacheItem` already work — this is
purely the editor UX layer.

## Files to Create

### `client/dist/js/social-embed-plugin.js`

Vanilla JS, no build step, no jQuery. TinyMCE 6 functional plugin API:

- **Button**: `editor.ui.registry.addButton('social_embed', {...})` with `onAction`
  opening a dialog
- **Dialog**: `editor.windowManager.open()` with a URL input field. On submit, inserts
  `[social_embed,url="..."]` wrapped in a
  `<div class="social-embed" contenteditable="false" data-shortcode="...">` placeholder
- **BeforeSetContent hook**: regex-replaces `[social_embed,url="..."]` shortcodes with
  styled placeholder divs when loading content into the editor
- **GetContent hook**: replaces placeholder divs back to raw shortcodes when saving
- **Context toolbar**: edit/delete buttons that appear when clicking an embed placeholder
- No live preview in dialog (see TODO below). Placeholder shows the URL in a styled
  box — same approach SS6's own `ssembed` plugin uses
- Handle old `data-shortcode` format (single quotes) for backwards compat with legacy content

### `tests/JS/social-embed-plugin.test.js` (or equivalent)

Unit tests for the plugin JS logic where possible:

- [ ] `shortcodeToPlaceholder()` — generates correct HTML from shortcode string
- [ ] `placeholderToShortcode()` — extracts shortcode from wrapper element `data-shortcode`
- [ ] `BeforeSetContent` regex — correctly finds and replaces all shortcode patterns
- [ ] `GetContent` DOM replacement — correctly converts placeholder divs back to shortcodes
- [ ] Handles old single-quote `data-shortcode` format from legacy content
- [ ] Handles multiple embeds in the same content
- [ ] Handles empty/missing `data-shortcode` attribute gracefully
- [ ] Rejects empty/whitespace-only URLs in dialog submit

These can be run with a lightweight JS test runner (e.g. vitest, jest) without needing
a full TinyMCE instance for the pure helper functions. The TinyMCE integration parts
(button registration, dialog open/close) would need E2E tests (see below).

## Files to Modify

### `_config.php`

Add conditional TinyMCE registration block after the existing shortcode registration:

- Check `ModuleLoader::inst()->getManifest()->moduleExists('silverstripe/htmleditor-tinymce')`
- Check `HTMLEditorConfig::get('cms') instanceof TinyMCEConfig`
- Call `$editorConfig->enablePlugins(['social_embed' => $module->getResource('client/dist/js/social-embed-plugin.js')])`
- Call `$editorConfig->insertButtonsAfter('charmap', 'social_embed')`
- Wrapped in `call_user_func()` to avoid polluting global scope

### `composer.json`

Update `extra.expose` from `["css", "img", "js"]` to `["client/dist", "img"]` — the old
`css/` and `js/` dirs no longer exist, and `client/dist` needs to be exposed for the
plugin JS to be served via `_resources/`.

### `README.md`

Replace the "Future enhancements" section with documentation of the new plugin:

- How to install: `composer require silverstripe/htmleditor-tinymce`
- What it provides: toolbar button, insert dialog, placeholder rendering, context toolbar
- Still works without TinyMCE (shortcodes can be typed manually)

### `CHANGELOG.md`

Add entry under `### Added`:

- TinyMCE 6 editor plugin for inserting `[social_embed]` shortcodes (optional, requires
  `silverstripe/htmleditor-tinymce`)

## Key API Mappings (Old -> New)

| TinyMCE 3/4 (old) | TinyMCE 6 (new) |
|--------------------|-----------------|
| `tinymce.create('tinymce.plugins.x', {init(ed){}})` | `tinymce.PluginManager.add('x', (editor) => {})` |
| `ed.addButton('x', {onclick})` | `editor.ui.registry.addButton('x', {onAction})` |
| `ed.addCommand('cmd', fn)` | Use `onAction` callback directly |
| `ed.windowManager.open({url: '/popup'})` | `editor.windowManager.open({body: {type:'panel'}})` |
| `tinyMCEPopup.execCommand('mceInsertContent')` | `editor.insertContent()` from dialog `onSubmit` |
| `ed.on('SetContent')` + `ed.setContent()` | `editor.on('BeforeSetContent')` (avoids recursion) |
| `ed.on('SaveContent')` | `editor.on('GetContent')` |
| `getInfo()` method | `getMetadata()` return value |

## Key Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Live preview in dialog? | No (see TODO) | TinyMCE 6 `htmlpanel` is static; would need `redial()` + server endpoint + AJAX |
| Preview endpoint? | Skip for now (see TODO) | Avoids extra controller, route, and AJAX |
| Build tooling? | None | Vanilla JS, ~120 lines. No webpack/vite needed |
| jQuery dependency? | None | Pure DOM APIs |
| Widget scripts in editor? | No | Old approach caused iframe artifacts. Placeholder is cleaner |

## Future TODOs

### Live Preview in Dialog

The old plugin showed a live oEmbed preview as the user typed a URL. To restore this:

- [ ] Create `src/Controllers/SocialEmbedController.php` with a `preview` action
  - CMS_ACCESS permission check
  - Accepts `?url=...` query param
  - Calls `OEmbedCacheItem::fetch(['url' => $url])` and returns `$data->html`
  - Returns `HTTPResponse` with `Content-Type: text/html`
- [ ] Add route in `_config/routes.yml`: `social-embed-api: Azt3k\SS\Social\Controllers\SocialEmbedController`
- [ ] Update the plugin JS dialog to use `redial()` for dynamic content updates
  - On URL input change (debounced), fetch preview from `/social-embed-api/preview?url=...`
  - Use `redial()` to rebuild dialog body with an `htmlpanel` containing the preview HTML
- [ ] Add PHPUnit tests for the controller (`SocialEmbedControllerTest.php`)

### E2E / Integration Tests

For testing the full plugin in a CMS environment:

- [ ] Set up Behat or Playwright test harness
- [ ] Test: toolbar button appears when `silverstripe/htmleditor-tinymce` is installed
- [ ] Test: click button -> dialog opens with URL input
- [ ] Test: enter URL, click Insert -> placeholder appears in editor
- [ ] Test: save page -> raw shortcode stored in database (not placeholder HTML)
- [ ] Test: reload page in CMS -> placeholder renders from stored shortcode
- [ ] Test: click placeholder -> context toolbar shows edit/delete buttons
- [ ] Test: edit via context toolbar -> dialog pre-fills existing URL
- [ ] Test: delete via context toolbar -> placeholder removed
- [ ] Test: view page on frontend -> oEmbed HTML renders via shortcode handler
- [ ] Test: no errors when `silverstripe/htmleditor-tinymce` is NOT installed

### Other

- [ ] Consider adding `silverstripe/htmleditor-tinymce` as a `suggest` in `composer.json`
- [ ] Consider a SVG icon instead of reusing the built-in `embed` icon for better branding
