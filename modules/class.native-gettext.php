<?PHP

  /**
   * Native GetText-Support for WordPress
   * ------------------------------------
   * The Patch enhanced WordPress with native support for gettext.
   * Actually WP is shipped with an own implementation called "PoMo"
   * that uses a lot of resources like CPU-Time and Memory.
   * Using gettext turned out to be much faster and efficient.
   * 
   * Copyright (C) 2012 Bernd Holzmueller <bernd@quarxconnect.de>
   *
   * This program is free software: you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation, either version 3 of the License, or
   * (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   *
   * You should have received a copy of the GNU General Public License
   * along with this program.  If not, see <http://www.gnu.org/licenses/>.
   *
   * @revision 02
   * @author Bernd Holzmueller <bernd@tiggerswelt.net>
   * @url http://oss.tiggerswelt.net/wordpress/3.3.1/
   **/
  
  // Check if gettext-support is available
  if (!extension_loaded ('gettext'))
    return;
  
  class Translate_GetText_Native extends Gettext_Translations {
    // Our default domain
    private $Domain = null;
    
    // Merged domains
    private $pOthers = array ();
    private $sOthers = array ();
    
    // Some Dummy-Function just to be API-compatible
    function add_entry ($entry) { return false; }
    function add_entry_or_merge ($entry) { return false; }
    function set_header ($header, $value) { return false; }
    function set_headers (&$headers) { return false; }
    function get_header ($header) { return false; }
    function translate_entry (&$entry) { return false; }
    
    // {{{ select_plural_form
    /**
     * Given the number of items, returns the 0-based index of the plural form to use
     *
     * Here, in the base Translations class, the common logic for English is implemented:
     *      0 if there is one element, 1 otherwise
     *
     * This function should be overrided by the sub-classes. For example MO/PO can derive the logic
     * from their headers.
     *
     * @param integer $count number of items
     **/
    function select_plural_form ($count) {
      return (1 == $count? 0 : 1);
    }
    // }}}
    
    function get_plural_forms_count () { return 2; }
    
    // {{{ merge_with
    /**
     * Merge this translation with another one, the other one takes precedence
     * 
     * @param object $other
     * 
     * @access public
     * @return void
     **/
    function merge_with (&$other) {
		if ( !( $other instanceof NOOP_Translations ) ) {
			$this->pOthers [] = $other;
		}
    }
    // }}}
    
    // {{{ merge_originals_with
    /**
     * Merge this translation with another one, this one takes precedence
     * 
     * @param object $other
     * 
     * @access public
     * @return void  
     **/
    function merge_originals_with (&$other) {
		if ( !( $other instanceof NOOP_Translations ) ) {
			$this->sOthers [] = $Other;
		}
	  }
    // }}}
    
    // {{{ translate
    /**
     * Try to translate a given string
     * 
     * @param string $singular
     * @param string $context (optional)
     * 
     * @access public
     * @return string
     **/
    function translate ($singular, $context = null) {
      // Check for an empty string
      if (strlen ($singular) == 0)
        return $singular;
      
      // Check other domains that take precedence
      foreach ($this->pOthers as $o)
        if (($t = $o->translate ($singular, $context)) != $singular)
          return $t;
      
      // Make sure we have a domain assigned
      if ($this->Domain === null)
        return $singular;
      
      // Translate without a context
      if ($context === null) {
        if (($t = dgettext ($this->Domain, $singular)) != $singular)
          return $t;
      
      // Translate with a given context
      } else {
        $T = $context . "\x04" . $singular;
        $t = dgettext ($this->Domain, $T);
        
        if ($T != $t)
          return $t;
      }
      
      // Check for other domains
      foreach ($this->sOthers as $o)
        if (($t = $o->translate ($singular, $context)) != $singular)
          return $t;
      
      return $singular;
    }
    // }}}
    
    // {{{ translate_plural
    /**
     * Try to translate a plural string
     * 
     * @param string $singular Singular version
     * @param string $plural Plural version
     * @param int $count Number of "items"
     * @param string $context (optional)
     * 
     * @access public
     * @return string
     **/
    function translate_plural ($singular, $plural, $count, $context = null) {
      // Check for an empty string
      if (strlen ($singular) == 0)
        return $singular;
      
      // Get the "default" return-value
      $default = ($count == 1 ? $singular : $plural);
      
      // Check other domains that take precedence
      foreach ($this->pOthers as $o)
        if (($t = $o->translate_plural ($singular, $plural, $count, $context)) != $default)
          return $t;
      
      // Make sure we have a domain assigned
      if ($this->Domain === null)
        return $default;
      
      // Translate without context
      if ($context === null) {
        $t = dngettext ($this->Domain, $singular, $plural, $count);
        
        if (($t != $singular) && ($t != $plural))
          return $t;
      
      // Translate using a given context
      } else {
        $T = $context . "\x04" . $singular;
        $t = dngettext ($this->Domain, $T, $plural, $count);
        
        if (($T != $t) && ($t != $plural))
          return $t;
      }
      
      // Check other domains
      foreach ($this->sOthers as $o)
        if (($t = $o->translate_plural ($singular, $plural, $count, $context)) != $default)
          return $t;
      
      return $default;
    }
    // }}}
    
	static function isAvailable($func) {
		if (ini_get('safe_mode')) return false;
		$disabled = ini_get('disable_functions');
		if ($disabled) {
			$disabled = explode(',', $disabled);
			$disabled = array_map('trim', $disabled);
			return !in_array($func, $disabled);
		}
		return true;
	}
	
    // {{{ import_from_file
    /**
     * Fills up with the entries from MO file $filename
     *
     * @param string $filename MO file to load
     **/
    function import_from_file ($filename) {
      // Make sure that the locale is set correctly in environment
      $locale=get_locale();
      
/*	  if ( self::isAvailable( 'putenv' ) ) {
		putenv ('LC_ALL=' . $locale);
	  }
      setlocale (LC_ALL, $locale); */
      
		if( !defined( 'LC_MESSAGES' ) ) {
			define( 'LC_MESSAGES', 6 );
		}
		
		if ( self::isAvailable( 'putenv' ) ) {
			putenv('LC_MESSAGES='.$locale.'.UTF-8' );
		}
		setlocale( LC_MESSAGES, $locale . '.UTF-8' );
	  
      // Retrive MD5-hash of the file
      # DIRTY! But there is no other way at the moment to make this work
      if (!($Domain = md5_file ($filename)))
        return false;
      
      // Make sure that the language-directory exists
      $Path = './wp-lang/' . $locale . '/LC_MESSAGES';
      
      if (!wp_mkdir_p ($Path))
        return false;
      
      // Make sure that the MO-File is existant at the destination
      $fn = $Path . '/' . $Domain . '.mo';
      
      if (!is_file ($fn) && !@copy ($filename, $fn))
        return false;
      
      // Setup the "domain" for gettext
      bindtextdomain ($Domain, './wp-lang/');
      bind_textdomain_codeset ($Domain, 'UTF-8');
      
      // Do the final stuff and return success
      $this->Domain = $Domain;
      
      return true;
    }
    // }}}
  }
  
?>