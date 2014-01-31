=== WP Performance Pack ===
Contributors: greencp
Tags: performance, i18n, translation, i10n, mo, gettext, localize, speed, optimize
Requires at least: 3.0
Tested up to: 3.8.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A collection of performance optimizations for wordpress. As of now it features options to 
improve performance of translated WordPress installations.

== Description ==

WP Performance Pack is intended to be a collection of performance optimizations for WordPress which don't need 
patching of core files. As of now it features options to improve performance of translated WordPress installations.

= Current features =

**Dynamic loading of translation files, only loading and translating used strings.**

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

**Use of native gettext if available**

There is probably no faster way for translations than using the native gettext implementation. This requires 
the php_gettext extension to be installed on the server. Version 0.2 supports the use of native gettext if it is 
available. This is implemented using *Bernd Holzmuellers* [Translate_GetText_Native](http://oss.tiggerswelt.net/wordpress/3.3.1/)
implementation (slightly modified). For now WPPP only checks if the gettext extension is available, which might 
not suffice to use native gettext. Further checks will follow.

**Disable backend translation while maintaining frontend translations.**

Speed up the backend by disabling dashboard-translations. Useful if you don't mind using an english backend.
AJAX requests on backend pages will still be translated, as I haven't figured out how to distinguish requests 
originating backend pages and requests from frontend pages.


**Just in time localization**

Localization of scripts only if needed to reduce unnecessary translations. Currently requires WordPress version 3.8.1.

== Screenshots ==

1. MO-Dynamic benchmark: Comparing front page of a "fresh" WordPress 3.8.1 installation (plain) and a "complex" installation (22 active plugins) using default MO implementation (*DE / MO* and *EN / MO*) and MO-Dynamic (*DE / MO-Dynamic* and *EN / MO-Dynamic*). Tested with (*DE...*) and without (*EN...*) translating using XDebug. Results are executiuon time in ms. The benchmarks show usage of MO-Dynamic improves performance when translating a blog and doesn't really impact it, when not. (Benchmarked version: 0.1)

== Installation ==

Download, install and activate. Usage of MO-Dynamic is enabled by default.

== Frequently Asked Questions ==

= Requirements =

PHP 5.3.0 required

For native gettext support:

* installed gettext extension
* languages folder must be writeable

= Limitations =

MO-Dynamic doesn't implement any saving related methods from the *Translations* base class. It's a read only implementation.

= Multisite support =

When installed network wide only the network admin can see and edit WPPP options.

== Changelog ==

= 0.4 =

* [i10n] german translation added
* [general] admin interface reworked
* [native gettext] use of LC_MESSAGES instead of LC_ALL
* [bative gettext] append codeset to locale

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

* [MO-Dynamic] bugfix - empty string got translated to headers
* [MO-Dynamic] performance tweaking
* [native gettext] possible multisite fix - using get_locale instead of global $locale

= 0.2 =

* [general] added native gettext support using *Bernd Holzmuellers* [Translate_GetText_Native](http://oss.tiggerswelt.net/wordpress/3.3.1/) implementation 
* [general] Just in time script localization (WP 3.6 and 3.8.1 supported)

= 0.1 =

* Initial release
