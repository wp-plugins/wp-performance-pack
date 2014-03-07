=== WP Performance Pack ===
Contributors: greencp, linushoppe
Tags: performance, speed, optimize, optimization, tuning, i18n, internationalization, translation, translate, l10n, localization, localize, language, languages, mo, gettext
Requires at least: 3.6
Tested up to: 3.8.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A collection of performance optimizations for WordPress. As of now it features options to improve performance of translated WordPress installations.

== Description ==

WP Performance Pack is your first choice for speeding up WordPress core the easy way. WP Performance Pack is a collection of performance optimizations for WordPress which don't need 
patching of core files. As of now it features options to improve performance of translated WordPress installations.

= Features =

* Simple user interface to automaticall set best available settings
* Dynamic loading of translation files, only loading and translating used strings.
* Use of PHP gettext extension if available.
* Disable back end translation while maintaining front end translations.
* Allow individual users to reactivate Dashboard translation via profile setting.
* Just in time localization of javascripts (requires WordPress version 3.8.1).
* [Debug Bar](http://wordpress.org/plugins/debug-bar/) integration
* Caching of translations to further improve translation performance. A persistent object cache has to be installed for this to be effective.

== Screenshots ==

1. MO-Dynamic benchmark: Comparing front page of a "fresh" WordPress 3.8.1 installation with active apc cache using different configurations. As you can see, using MO-Dynamic with active caching is just as fast as not translating the blog or using native gettext. Benchmarked version 0.6, times are mean of four test runs measured using XDebug.
2. Settings, advanced view (v1.0)
3. Debug Bar integration (v1.0)
4. Settings, simple view (v1.0)

== Installation ==

* Download, install and activate. Usage of MO-Dynamic is enabled by default.
* Gettext support requires PHP Gettext extension and the languages folder (*wp-content/languages*) must be writeable for php.
* Caching is only effective if a persisten object cache is installed
* JIT requires PHP >= 5.3 and WordPress >= 3.8.1
* Debugging requires [Debug Bar](http://wordpress.org/plugins/debug-bar/) to be installed and activated

== Frequently Asked Questions ==

= How do I check if caching works? =

Caching only works when using alternative MO implementation. To check if tha cache works, activate WPPP debugging (requires [Debug Bar](http://wordpress.org/plugins/debug-bar/)) Plugin). This adds the panel *WP Performance Pack* to the Debug Bar. Textdomains using *MO_dynamic* implementation show information about translations loaded from cache. If no translations are getting loaded from cache cache persistence isn't working.

= Which persisten object cache plugins are recommended? =

Any persisten object cache will do, but it has to be supported in your hosting environment. Check if any caches like APC, XCache, Memcache, etc. are installed on your webserver and select a suitable cache plugin respectively. File based object caches should work always and might improve performance, same goes for data base based caches. Performance gains depend on the available caching method and its configuration.

= Does WPPP support multisite? =

Yes, when installed network wide only the network admin can see and edit WPPP options.

== How translation improvements work == 

WPPP overrides WordPress' default implementation by using the *override_load_textdomain* hook. The fastest way for translations is using the native gettext implementation. This requires the PHP Gettext extension to be installed on the server. WPPPs gettext implementation is based on *Bernd Holzmuellers* [Translate_GetText_Native](http://oss.tiggerswelt.net/wordpress/3.3.1/) implementation (slightly modified). Gettext support is still a bit tricky and having the gettext extension installed doesn't mean it will work. 

As second option WPPP features a complete rewrite of WordPress MO imlementation: MO_dynamic (the alternative MO reader). The default WordPress implementaion loads the complete mo file right after a call to *load_textdomain*, whether any transaltions of this textdomain are needed or not. This needs quite some time and even more memory. Mo_dynamic features on demand loading. It doesn't load any mo file until the first translation call to that specific textdomain. And it doesn't load the entire mo file either, only the requested transaltion. Though the (highly optimized) search for an individual translation is slower, the vastly improved loading time and reduced memory foot print result in an overall performance gain.

Caching can further improve performance. When using MO_dynamic with activated caching, translations get cached using WordPress Object Cache API. Front end pages usually don't use many translations, so for all front end pages one cache is used per textdomain. Back end pages on the other hand use many translations. So back end pages get each their own individual translation cache with one *base cache* for each textdomain. This *base cache* consists of those translations that are used on all back end pages (i.e. they have been used up to *admin_init* hook). Later used translations are cached for each page. All this is to reduce cache size, which is very limited on many caching methods like APC. To even further reduce cache size, the transaltions get compressed before being saved to cache.

== Changelog ==

= 1.0 =

* [mo-dynamic] cache soft expire
* [mo-dynamic] optimizations (faster hash calculation) and code cleanup
* [mo-dynamic] object cache test now checks existence of object-cache.php and class name of wp_object_cache
* [override textdomain] bugfix so alternative folders for theme and plugin translations are searched again
* [l10n] textdomain added to plugin description
* [native gettext] bugfix in native gettext test
* [debug] reworked display of loaded textdomains
* [debug] show cached translation count when using mo-dynamic and caching
* [general] added uninstall to clean up created translations from native gettext

= 0.9 =

* [mo-dynamic] mo table caching removed (small speed improvement vs. big cache usage)
* [mo-dynamic] reduced cache space usage (reused admin "base" translations, data compression)
* [mo-dynamic] some small fixes
* [general] more refactoring to reduce loaded code
* [l10n] texts and translations updated

= 0.8 =

* [jit] fixed broken file upload (e.g. when editing posts)
* [general] code refactoring to reduce loaded code
* [general] selectable user default for backend transaltion if allow override is enabled
* [l10n] translations updated

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
