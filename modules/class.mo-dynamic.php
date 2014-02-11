<?php
/**
 * Dynamic loading and parsing of MO files
 *
 * @author Björn Ahrens <bjoern@ahrens.net>
 * @package WP Performance Pack
 * @since 0.1
 */

/**
 * Class holds information about a single MO file
 */
class MO_item {
	var $reader = NULL;
	var $mofile = '';
	var $total = 0;
	var $endian;
	var	$originals_offset;
	var $translations_offset;
	var $originals;
	var $originals_table;
	var $translations_table;
	var $last_access;
}

/**
 * Class for working with MO files
 * Translation entries are created dynamically.
 * Due to this export and save functions are not implemented.
 */
class MO_dynamic extends Gettext_Translations {
	var $_nplurals = 2;
	var $MOs;
	private $translations = array();

	function __construct() {
		$this->MOs = array();
	}

	function __destruct() {
		foreach ( $this->MOs as &$moitem ) {
			if ( $moitem->reader!=NULL ) {
				$moitem->reader->close();
				$moitem->reader=NULL;
			}
		}
	}
	
	function import_from_file( $filename ) {
		$moitem = new MO_item();
		$moitem->mofile = $filename;
		$this->MOs[] = $moitem;
		// because only a reference to the MO file is created, at this point there is no information if $filename is a valid MO file, so the return value is always true
		return true;
	}

	function import_from_reader(&$moitem) {
		if ( $moitem->reader==NULL ) {
			$moitem->reader=new POMO_FileReader( $moitem->mofile );
		}

		$endian_string = MO::get_byteorder( $moitem->reader->readint32() );
		if ( false === $endian_string ) {
			return false;
		}
		$moitem->reader->setEndian( $endian_string );

		$endian = ( 'big' == $endian_string ) ? 'N' : 'V';
		$moitem->endian = $endian;

		$header = $moitem->reader->read( 24 );
		if ( $moitem->reader->strlen( $header ) != 24 ) {
			return false;
		}

		// parse header
		$header = unpack( "{$endian}revision/{$endian}total/{$endian}originals_lenghts_addr/{$endian}translations_lenghts_addr/{$endian}hash_length/{$endian}hash_addr", $header );
		if ( !is_array( $header ) ) {
			return false;
		}
		extract( $header );

		// support revision 0 of MO format specs, only
		if ( $revision != 0 ) {
			return false;
		}

		$moitem->total = $total;
		if ( class_exists( 'SplFixedArray' ) ) {
			$moitem->originals = new SplFixedArray ( $total );
		} else {
			$moitem->originals = array();
		}
		$moitem->originals_offset = $originals_lenghts_addr;
		$moitem->translations_offset = $translations_lenghts_addr;

		// seek to data blocks
		$moitem->reader->seekto( $originals_lenghts_addr );

		// read originals' indices
		$originals_lengths_length = $translations_lenghts_addr - $originals_lenghts_addr;
		if ( $originals_lengths_length != $total * 8 ) {
			return false;
		}

		$str = $moitem->reader->read( $originals_lengths_length );
		if ( $moitem->reader->strlen( $str ) != $originals_lengths_length ) {
			return false;
		}
		if ( class_exists ( 'SplFixedArray' ) ) {
			$moitem->originals_table = SplFixedArray::fromArray( unpack ( $endian.($total * 2), $str ), false );
		} else {
			$moitem->originals_table = unpack ( $endian.($total * 2), $str );
		}

		// read translations' indices
		$translations_lenghts_length = $hash_addr - $translations_lenghts_addr;
		if ( $translations_lenghts_length != $total * 8 ) {
			return false;
		}

		$str = $moitem->reader->read( $translations_lenghts_length );
		if ( $moitem->reader->strlen( $str ) != $translations_lenghts_length ) {
			return false;
		}
		if ( class_exists ( 'SplFixedArray' ) ) {
			$moitem->translations_table = SplFixedArray::fromArray( unpack ( $endian.($total * 2), $str ), false );
		} else {
			$moitem->translations_table = unpack ( $endian.($total * 2), $str );
		}

		// read headers
		for ( $i = 0, $max = $total * 2; $i < $max; $i+=2 ) {
			if ( $moitem->originals_table[$i] > 0 ) {
				$moitem->reader->seekto( $moitem->originals[$i+1] );
				$original = $moitem->reader->read( $moitem->originals_table[$i] );
						
				$j = strpos( $original, 0 );
				if ( $j !== false ) {
					$original = substr( $original, 0, $i );
				}
			} else {
				$original = '';
			}

			if ( $original === '' ) {
				if ( $moitem->translations_table[$i] > 0 ) {
					$moitem->reader->seekto( $moitem->translations_table[$i+1] );
					$translation = $moitem->reader->read( $moitem->translations_table[$i] );
				} else
					$translation = '';
				
				$this->set_headers( $this->make_headers( $translation ) );
			} else {
				return true;
			}
		}
		return true;
	}

	protected function search_translation ( $key ) {
		for ( $j = 0, $max = count ( $this->MOs ); $j < $max; $j++ ) {
			$moitem = $this->MOs[$j];
			if ( $moitem->reader == NULL ) {
				if ( !$this->import_from_reader( $moitem ) ) {
					// Error reading MO file, so delete it from MO list to prevent subsequent access
					unset( $this->MOs[$j] );
					return false;
				}
			}

			// binary search for matching originals entry
			$left = 0;
			$right = $moitem->total-1;
			while ( $left <= $right ) {
				$pivot = $left + (int) ( ( $right - $left ) / 2 );
				$pos = $pivot * 2;
				if ( isset( $moitem->originals[$pivot] ) ) {
					$mo_original = $moitem->originals[$pivot];
				} else {
					// read and "cache" original string to improve performance of subsequent searches
					if ( $moitem->originals_table[$pos] > 0 ) {
						$moitem->reader->seekto( $moitem->originals_table[$pos+1] );
						$mo_original = $moitem->reader->read( $moitem->originals_table[$pos] );
					} else {
						$mo_original = '';
					}
					$moitem->originals[$pivot] = $mo_original;
				}

				$i = strpos( $mo_original, 0 );
				if ( $i !== false ) {
					$original = substr( $mo_original, 0, $i );
				} else {
					$original = $mo_original;
				}

				$cmpval = strcmp( $key, $original );
				if ( $cmpval === 0 ) {
					// key found

					// read translation string
					$moitem->reader->seekto( $moitem->translations_table[$pos+1] );
					$translation = $moitem->reader->read( $moitem->translations_table[$pos] );

					$this->translations[$key] = $translation;

					if ( $j > 0 ) {
						// Assuming frequent subsequent translations from the same file resort MOs by access time to avoid unnecessary in the wrong files.
						$moitem->last_access=time();
						usort( $this->MOs, function ($a, $b) {return ($b->last_access - $a->last_access);} );
					}

					return $translation;
				} else if ( $cmpval < 0 ) {
					$right = $pivot - 1;
				} else { // if ($cmpval>0) 
					$left = $pivot + 1;
				}
			}
		}
		// key not found
		return false;
	}

	function translate ($singular, $context = null) {
		if ( strlen( $singular ) == 0 ) return $singular;
		
		if ( $context == NULL ) {
			$s = $singular;
		} else {
			$s = $context . chr(4) . $singular;
		}

		if ( isset( $this->translations[$s] ) ) {
			$t = $this->translations[$s];
		} else {
			$t = $this->search_translation( $s );
		}
		
		if ( $t !== false ) {
			$i = strpos( $t, 0 );
			if ( $i !== false ) {
				return substr( $t, 0, $i );
			} else {
				return $t;
			}
		} else {
			if ( $context == NULL ) {
				$this->translations[$s] = $context . chr(4) . $singular;
			} else {
				$this->translations[$s] = $singular;
			}
			return $singular;
		}
	}

	function translate_plural ($singular, $plural, $count, $context = null) {
		if ( strlen( $singular ) == 0 ) return $singular;

		// Get the "default" return-value
		$default = ($count == 1 ? $singular : $plural);

		if ( $context == NULL ) {
			$s = $singular;
		} else {
			$s = $context . chr(4) . $singular;
		}

		if ( isset( $this->translations[$s] ) ) {
			$t = $this->translations[$s];
		} else {
			$t = $this->search_translation( $s );
		}
		
		if ( $t !== false ) {
			$i = strpos( $t, 0 );
			if ( $i !== false ) {
				if ( $count == 1 ) {
					return substr ( $t, 0, $i );
				} else {
					// only one plural form is assumed - needs improvement
					return substr( $t, $i+1 );
				}
			} else {
				return $default;
			}
		} else {
			if ( $context == NULL ) {
				$this->translations[$s] = $context . chr(4) . $singular . chr(0) . $plural;
			} else {
				$this->translations[$s] = $singular . chr(0) . $plural;
			}
			return $default;
		}
	}

	function merge_with( &$other ) {
		foreach( $other->entries as $entry ) {
			$this->entries[$entry->key()] = $entry;
		}
		
		if ( isset ( $other->MOs ) ) {
			foreach ( $other->MOs as $moitem ) {
					$i = 0;
					$c = count( $this->MOs );
					$found = false;
					while ( !$found && ( $i < $c ) ) {
						$found = $this->MOs[$i]->mofile == $moitem->mofile;
						$i++;
					}
					if ( !$found )
						$this->MOs[] = $moitem;
			}
		}
	}

	function MO_file_loaded ( $mofile ) {
		foreach ($this->MOs as $moitem) {
			if ($moitem->mofile == $mofile) {
				return true;
			}
		}
		return false;
	}
}

class MO_dynamic_Debug extends Mo_dynamic {
	public $translate_hits = 0;
	public $translate_plural_hits = 0;
	public $search_translation_hits = 0;
	
	function translate_plural ($singular, $plural, $count, $context = null) {
		$this->translate_plural_hits++;
		return parent::translate_plural($singular, $plural, $count, $context);
	}

	function translate ($singular, $context = null) {
		$this->translate_hits++;
		return parent::translate ($singular, $context);
	}
	
	protected function search_translation ( $key ) {
		$this->search_translation_hits++;
		return parent::search_translation( $key );
	}
}

?>