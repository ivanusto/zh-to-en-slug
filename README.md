# Chinese to English Slug Converter

A WordPress plugin that automatically converts Chinese post titles into English slugs using the Google Cloud Translation API, creating clean and SEO-friendly URLs for your content.

![Version](https://img.shields.io/badge/version-1.2.1-blue) ![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b) ![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4) ![License](https://img.shields.io/badge/license-Apache--2.0-green)

## Key Features

- Automatic translation of Chinese titles to English slugs
- Post ID appended to slug on updates; WordPress core guarantees uniqueness for new posts
- Translation results cached for 7 days to reduce API usage
- Configurable maximum slug length
- Clean and SEO-friendly URL structure
- Easy-to-use settings interface
- API connection testing tool
- Support for both Traditional and Simplified Chinese

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Google Cloud Platform account with Cloud Translation API enabled

## Installation

1. Upload the plugin files to `/wp-content/plugins/chinese-to-english-slug`, or install through the WordPress plugins screen
2. Activate the plugin through the **Plugins** screen in WordPress
3. Go to **Settings > Chinese to English Slug** to configure the plugin
4. Enter your Google Cloud Translation API key and configure your preferred maximum slug length

### Getting a Google Cloud Translation API Key

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Cloud Translation API
4. Create credentials (API key)
5. Set usage restrictions for your API key
6. Copy the API key to your plugin settings

## How to Use

1. Install and activate the plugin
2. Go to **Settings > Chinese to English Slug**
3. Enter your Google Cloud Translation API key
4. Set your preferred maximum slug length
5. Test your API connection using the test button
6. Start creating posts with Chinese titles — slugs will be automatically converted

## FAQ

**Does this plugin require an API key?**
Yes, a Google Cloud Translation API key is required to translate Chinese text to English.

**Is there a limit to how many translations I can do?**
The number of translations depends on your Google Cloud Platform account quota. The free tier includes a generous monthly allowance, but you may need to upgrade for high-volume sites.

**What happens if the translation fails?**
The plugin falls back to WordPress's default slug generation behavior.

**Can I modify an automatically generated slug?**
Yes, you can edit the slug manually after it's generated, just like any other WordPress post slug.

**Does this work with custom post types?**
Yes, the plugin works with all post types that use slugs in WordPress.

## Changelog

### 1.2.1
- Fix: guard against duplicate class declaration when two copies of the plugin are installed side by side
- Fix: bump "Tested up to" to WordPress 7.0
- Dev: ship a proper distribution zip (top-level folder `zh-to-en-slug`) as a release asset

### 1.2.0
- Fix: request plain-text translations (`format=text`) so HTML entities no longer leak into slugs
- Fix: prevent empty slugs when max length is set too low (clamped to 20–200 with a hard floor)
- Fix: PHP notice when saved options were missing a key (defaults now merged via `wp_parse_args`)
- Improve: widen Chinese detection to cover CJK Extension A and the full basic CJK block
- Performance: cache translation results in transients for 7 days to reduce API calls
- Performance: lower API timeout from 15s to 8s so saving never hangs on a slow API
- Security: add capability check to the API test AJAX endpoint
- Security: API key field now uses a password input
- Add: `uninstall.php` cleans up options and cached translations on plugin removal
- Add: `cts_allowed_statuses` filter; scheduled, pending, and private posts are now processed too
- Dev: modernize `register_setting` to the args-array signature

### 1.1.0
- Append post ID to generated slug to guarantee uniqueness across posts
- Refactor internal API call logic to eliminate code duplication
- Fix admin script version to match plugin version
- Improve JavaScript i18n: pass status strings through `wp_localize_script`
- Security: use `.text()` instead of `.html()` when displaying API test results

### 1.0.0
- Initial release

## Privacy Policy

This plugin sends post titles to Google Cloud Translation API for translation. Please ensure this complies with your privacy policy and data protection requirements.

The following data is sent to Google's servers:
- Post titles for translation
- Your API credentials

No personal data is stored or tracked by this plugin. For more information, visit [Google Cloud Privacy](https://cloud.google.com/privacy).

## Credits

- Developed by [Ivan Lin](https://yblog.org/)
- Uses [Google Cloud Translation API](https://cloud.google.com/translate)

## License

[Apache-2.0](LICENSE)
