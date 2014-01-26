=== WP Performance Pack ===
Contributors: greencp
Tags: performance, i18n, translation, i10n, mo
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

**Disable backend translation while maintaining frontend translations.**

Speed up the backend by disabling dashboard-translations. Useful if you don't mind using an english backend.
AJAX requests on backend pages will still be translated, as I haven't figured out how to distinguish requests 
originating backend pages and requests from frontend pages.

= Planned future features =

* JIT localization to reduce number of possibly unnecessary translation calls (of which there are quite a few in wordpress core).
* Optional use of native gettext implementation if available.
* Caching using WP_Object_Cache.
* much more...

== Screenshots ==

Benchmark screenshots will follow.

== Installation ==

Download, install and activate. Usage of MO-Dynamic is enabled by default.

== Frequently Asked Questions ==

= Requirements =
PHP 5.3.0 required

= Limitations =
MO-Dynamic doesn't implement any saving related methods from the *Translations* base class. It's a read only implementation.

== Changelog ==

= 0.1 =
Initial release
