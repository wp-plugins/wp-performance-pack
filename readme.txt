=== WP Performance Pack ===
Contributors: greencp, linushoppe
Tags: performance, speed, optimize, optimization, tuning, i18n, internationalization, translation, translate, l10n, localization, localize, language, languages, mo, gettext
Requires at least: 3.0
Tested up to: 3.8.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A collection of performance optimizations for wordpress. As of now it features options to 
improve performance of translated WordPress installations.

== Description ==

WP Performance Pack is a collection of performance optimizations for WordPress which don't need 
patching of core files. As of now it features options to improve performance of translated WordPress installations.

= Features =

* Simple user interface to automaticall set best available settings
* Dynamic loading of translation files, only loading and translating used strings.
* Use of native gettext if available.
* Disable backend translation while maintaining frontend translations.
* Allow individual users to reactivate Dashboard translation via profile setting.
* Just in time localization of javascripts (requires WordPress version 3.8.1).
* [Debug Bar](http://wordpress.org/plugins/debug-bar/) integration
* Caching of translations to further improve translation performance. A persistent object cache has to be installed for this to be effective.

== Screenshots ==

1. MO-Dynamic benchmark: Comparing front page of a "fresh" WordPress 3.8.1 installation with active apc cache using different configurations. As you can see, using MO-Dynamic with active caching is just as fast as not translating the blog or using native gettext. Benchmarked version 0.6, times are mean of four test runs measured using XDebug.
2. Settings
3. Debug Bar integration

== Installation ==

Download, install and activate. Usage of MO-Dynamic is enabled by default.

== Frequently Asked Questions ==

= Requirements =

PHP >= 5.3 required

For native gettext support:

* installed gettext extension
* languages folder (*wp-content/languages*) must be writeable

For debugging [Debug Bar](http://wordpress.org/plugins/debug-bar/) needs to be installed

= Limitations =

MO-Dynamic doesn't implement any saving related methods from the *Translations* base class. It's a read only implementation.

= Multisite support =

When installed network wide only the network admin can see and edit WPPP options.

= Optimal settings =

* Native gettext **enabled** (if available)
* MO-Dynamic **enabled**
* JIT localize **enabled** (disable if this causes trouble with javascripts)

= Caching =

When using MO-Dynamic optinal caching of translations can be enabled. This uses the WordPress Cache API so a peristent object cache of your choice has to be installed to get any performance improvements from caching. For front end pages one cache per text domain is used to keep the cache rather small (else cache size on blogs with many posts would get quite big). Each front end cache is kept for 60 minutes. For backend pages one cache per page and text domain is used (there are limited back end pages and more translating is going on in the back end). These are kept for 30 minutes.

== Details == 

= Dynamic loading of translation files, only loading and translating used strings. =

Improves performance and reduces memory consumption. The default WordPress MO implementation loads the complete 
MO files (e.g. when loaded via load_textdomain) into memory. As a result translation of individual strings is 
quite fast, but loading times and memory consumption are high. Most of the time only a few strings from a mo file 
are required within a single page request. Activating translation almost doubles execution time in a typical WordPress 
installation.

WPPP uses MO-Dynamic, a complete rewrite of the MO implementation, to speed up translations. Firstly 
mo files are only loaded if needed. On installations with many translated plugins this alone can dramatically 
reduce execution time. Furthermore it doesn't load the complete translations into memory, only required ones.
This on demand translation is more expensive than translations on fully loaded mo files but the performance
gain by not loading and parsing the complete file outweighs this.

= Use of native gettext if available =

There is probably no faster way for translations than using the native gettext implementation. This requires 
the php_gettext extension to be installed on the server. Version 0.2 supports the use of native gettext if it is 
available. This is implemented using *Bernd Holzmuellers* [Translate_GetText_Native](http://oss.tiggerswelt.net/wordpress/3.3.1/)
implementation (slightly modified). For now WPPP only checks if the gettext extension is available, which might 
not suffice to use native gettext. Further checks will follow.

== Changelog ==

= 0.8 =

* [jit] fixed broken file upload (e.g. when editing posts)
* [general] code refactoring to reduce loaded code
* [general] selectable user default for backend transaltion if allow override is enabled

= 0.7.3 =

* [general] file encoding could cause problems

= 0.7.2 =

* [general] script bugfix in simple view

= 0.7.1 =

* [general] bugfix: save settings changed view

= 0.7 =

* [general] new user interface with simple and advanced view
* [general] extended tests for support of gettext, object cache and jit
* [mo-dynamic] bugfix: removed HTML illegal chars from some translations

= 0.6.2 =

* [jit] script l10n now works with bwp minify, and hopefully other script minify plugins as well

= 0.6.1 =

* [jit] no jit when *IFRAME_REQUEST* is defined (broke theme customize)
* [jit] fixed multiple localizations per handle

= 0.6 =

* [mo-dynamic] use hash table if mo file contains one
* [mo-dynamic] optional caching implemented

= 0.5.2 =

* [debug] show translation calls when using MO-Dynamic
* [debug] test if WPPP is loaded as first plugin
* [l10n] translations updated

= 0.5.1 =

* [debug] show class used for textdomain
* [debug] added debugging option, so WP_DEBUG isn't required anymore
* [l10n] translations updated

= 0.5 =

* [native gettext] langage directory set to WP_LANG_DIR
* [general] allow user override to reactivate backend translation
* [debug] Debug Bar integration for debugging
* [l10n] translations updated

= 0.4 =

* [l10n] german translation added
* [general] admin interface reworked
* [native gettext] use of LC_MESSAGES instead of LC_ALL
* [native gettext] append codeset to locale

= 0.3 =

* [general] added multisite support (network installation)

= 0.2.4 =

* [jit] bugfixs in LabelsObject and WP_Scripts_override

= 0.2.3 =

* [jit] complete rework of JIT localize - it shouldn't break scripts anymore
* [general] bugfix in changing plugin load order (WPPP has to be the first plugin to be loaded)

= 0.2.2 =

* [general] bugfix in form validation
* [native gettext] test if *putenv* is disabled

= 0.2.1 =

* [mo-dynamic] bugfix - empty string got translated to headers
* [mo-dynamic] performance tweaking
* [native gettext] possible multisite fix - using get_locale instead of global $locale

= 0.2 =

* [general] added native gettext support using *Bernd Holzmuellers* [Translate_GetText_Native](http://oss.tiggerswelt.net/wordpress/3.3.1/) implementation 
* [general] Just in time script localization (WP 3.6 and 3.8.1 supported)

= 0.1 =

* Initial release
