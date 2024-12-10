# Chinese to English Slug Converter / zh-to-en-slug
Chinese to English Slug Converter is a WordPress plugin that automatically converts Chinese post titles into English slugs. It uses the Google Cloud Translation API to provide accurate translations and creates clean, SEO-friendly URLs for your content.
=== Chinese to English Slug Converter ===
Contributors: Ivan Lin
Tags: chinese, english, slug, translation, permalink
Requires at least: 6.0
Tested up to: 6.7.1
Requires PHP: 7.4
Stable tag: 1.0.0
Plugin URI: https://yblog.org/zh-to-en-slug
Version: 1.0.0
Author: Ivan Lin
Author URI: https://yblog.org/
License: Apache-2.0
License URI: https://opensource.org/license/apache-2-0

Automatically converts Chinese post titles to English slugs using Google Translate API.

== Description ==

Chinese to English Slug Converter is a WordPress plugin that automatically converts Chinese post titles into English slugs. It uses the Google Cloud Translation API to provide accurate translations and creates clean, SEO-friendly URLs for your content.

= Key Features =

* Automatic translation of Chinese titles to English slugs
* Configurable maximum slug length
* Clean and SEO-friendly URL structure
* Easy-to-use settings interface
* API connection testing tool
* Support for both Traditional and Simplified Chinese

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* Google Cloud Platform account
* Google Cloud Translation API key

= How to Use =

1. Install and activate the plugin
2. Go to Settings > Chinese to English Slug
3. Enter your Google Cloud Translation API key
4. Set your preferred maximum slug length
5. Test your API connection using the test button
6. Start creating posts with Chinese titles - slugs will be automatically converted

= Getting a Google Cloud Translation API Key =

1. Go to Google Cloud Console
2. Create a new project or select an existing one
3. Enable the Cloud Translation API
4. Create credentials (API key)
5. Set usage restrictions for your API key
6. Copy the API key to your plugin settings

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/chinese-to-english-slug` directory, or install the plugin through the WordPress plugins screen directly
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Chinese to English Slug to configure the plugin
4. Enter your Google Cloud Translation API key
5. Configure your preferred maximum slug length

== Frequently Asked Questions ==

= Does this plugin require an API key? =

Yes, you need a Google Cloud Translation API key to use this plugin. The API key is required to translate Chinese text to English.

= Is there a limit to how many translations I can do? =

The number of translations you can perform depends on your Google Cloud Platform account quota. The free tier includes a generous monthly allowance, but you may need to upgrade for high-volume sites.

= What happens if the translation fails? =

If the translation fails for any reason, the plugin will fall back to WordPress's default slug generation behavior.

= Can I modify an automatically generated slug? =

Yes, you can edit the slug manually after it's generated, just like any other WordPress post slug.

= Does this work with custom post types? =

Yes, the plugin works with all post types that use slugs in WordPress.

== Screenshots ==

1. Plugin settings page
2. API key configuration
3. Example of translated slug

== Changelog ==

= 1.0.0 =
* Initial release

== Privacy Policy ==

This plugin sends post titles to Google Cloud Translation API for translation. Please ensure this complies with your privacy policy and data protection requirements.

The following data is sent to Google's servers:
* Post titles for translation
* Your API credentials

No personal data is stored or tracked by this plugin.

For more information about Google's privacy policy, visit: https://cloud.google.com/privacy

== Credits ==

* Developed by [Ivan Lin]
* Uses Google Cloud Translation API
