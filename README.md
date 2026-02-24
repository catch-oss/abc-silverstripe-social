# abc-silverstripe-social

<!-- PROJECT SHIELDS -->
[![SonarCloud](https://github.com/catch-oss/abc-silverstripe-social/actions/workflows/sonar.yml/badge.svg)](https://github.com/catch-oss/abc-silverstripe-social/actions/workflows/sonar.yml)
[![Test](https://github.com/catch-oss/abc-silverstripe-social/actions/workflows/test.yml/badge.svg)](https://github.com/catch-oss/abc-silverstripe-social/actions/workflows/test.yml)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=catch-design_catch-oss-abc-silverstripe-social)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=bugs)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=code_smells)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=coverage)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)
[![Duplicated Lines Density](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=duplicated_lines_density)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)
[![Lines of Code](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=ncloc)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=reliability_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=security_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=sqale_index)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=sqale_rating)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=catch-design_catch-oss-abc-silverstripe-social&metric=vulnerabilities)](https://sonarcloud.io/component_measures?id=catch-design_catch-oss-abc-silverstripe-social)

Library that adds some social media functionality to Silverstripe:

## Compatibility

| Version | Silverstripe | PHP |
|---------|-------------|-----|
| release/6 | ^6.0 | ^8.5 |
| release/5 | ^5.1 | ~8.4 |

## Features

- Downloads your Facebook, Instagram or Twitter feed and puts it somewhere of your choosing in your site tree
- Shares the current page to Facebook or Twitter when you publish it (WIP)
- Improves Page meta data with Twitter Cards, Open Graph and micro data
- Provides template helpers for generating share URLs

## Setup

This module does **not** automatically apply extensions to `SiteConfig` or `Page`. You must opt in by adding the extensions you need in your project's YAML config.

### SocialMediaConfig (on SiteConfig)

Adds Facebook, Twitter and Instagram API credentials, OAuth tokens, default images and push/pull toggles to the CMS Settings screen under a **Social Media** tab.

```yaml
# app/_config/social.yml
---
Name: project-social-extensions
---
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    social-media-config: Azt3k\SS\Social\Extensions\SocialMediaConfig
```

**What it adds to SiteConfig:**
- Facebook: App ID/Secret, User/Page access tokens, Page ID, feed type, push/pull toggles
- Twitter: Consumer Key/Secret, OAuth token/secret, username, push/pull toggles
- Instagram: API Key/Secret, OAuth token, username/user ID, push/pull toggles
- Default fallback images for each social network

### SocialMediaPageExtension (on Page)

Adds social media meta data, share URLs, publication tracking and auto-posting to every page.

```yaml
# app/_config/social.yml (append to same file)
Page:
  extensions:
    social-media-page: Azt3k\SS\Social\Extensions\SocialMediaPageExtension
```

**What it adds to Page:**
- `MetaTitle`, `MetaKeywords` fields and a **Meta** tab in the CMS
- `PrimaryImage` upload for social sharing image (with fallback to SiteConfig default)
- `ForceUpdateMode` (Default/Block/Force) to control auto-posting behaviour
- Publication tracking (`PublicationTweets`, `PublicationFBUpdates`, `PublicationInstagramUpdates`)
- `$Meta('Title')`, `$Meta('Description')`, `$Meta('Image')` etc. template helpers
- `$ShareUrl('facebook')`, `$ShareUrl('twitter')`, `$ShareUrl('linked_in')` template helpers
- Auto-post to Facebook/Twitter on publish (when push is enabled in SiteConfig)

### Both extensions together

For full functionality, enable both:

```yaml
# app/_config/social.yml
---
Name: project-social-extensions
---
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    social-media-config: Azt3k\SS\Social\Extensions\SocialMediaConfig

Page:
  extensions:
    social-media-page: Azt3k\SS\Social\Extensions\SocialMediaPageExtension
```

Then run `dev/build` to apply the database changes.

## Meta data

Include the `Meta` partial in your page template:

```html
<head>
    <% base_tag %>
    <title>$Meta('Title')</title>
    <%-- meta data --%>
    <% include Meta %>
</head>
```

Available meta keys: `Title`, `Description`, `Keywords`, `SiteName`, `Link`, `Image`, `TwitterCreator`, `TwitterPublisher`, `TimeModified`, `TimeCreated`

## Share URLs

In your template:

```html
<a href="$ShareUrl('facebook')">Share on Facebook</a>
<a href="$ShareUrl('twitter')">Share on Twitter</a>
<a href="$ShareUrl('linked_in')">Share on LinkedIn</a>
```


## License

Copyright (c) 2015, azt3k
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
