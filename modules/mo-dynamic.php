<?php

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
		$moitem->originals = new SplFixedArray ( $total );
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
		$moitem->originals_table = SplFixedArray::fromArray( unpack ( $endian.($total * 2), $str ), false );

		// read translations' indices
		$translations_lenghts_length = $hash_addr - $translations_lenghts_addr;
		if ( $translations_lenghts_length != $total * 8 ) {
			return false;
		}

		$str = $moitem->reader->read( $translations_lenghts_length );
		if ( $moitem->reader->strlen( $str ) != $translations_lenghts_length ) {
			return false;
		}
		$moitem->translations_table = SplFixedArray::fromArray( unpack ( $endian.($total * 2), $str ), false );

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

	static function &make_entry( $original, $translation ) {
		$entry = new Translation_Entry ();
		
		// look for context
		$idx = strpos( $original, 4 );
		if ( $idx !== false ) {
			if ( $idx < strlen( $original ) - 1 ) {
				$entry->context = substr( $original, 0, $idx);
				$original = substr( $original, $idx + 1 );
			}
		}

		// look for plural original
		$idx = strpos( $original, 0 );
		if ( $idx !== false ) {
			$entry->singular = substr( $original, 0, $idx );
			if ( $idx < strlen( $original ) - 1) {
				$entry->is_plural = true;
				$entry->plural = substr( $original, $idx + 1 );
			}
		} else {
			$entry->singular = $original;
		}

		// plural translations are also separated by \0
		$idx = strpos( $translation, 0 );
		if ( $idx === false ) {
			$entry->translations[] = $translation;
		} else {
			$entry->translations[] = substr( $translation, 0, $idx );
			$entry->translations[] = substr( $translation, $idx + 1 );
		}
		return $entry;
	}

	function update_entry ( &$entry, $mo_original, $translation ) {
		// singular and context are already set, as these form the key which is used for search
		
		// look for plural original
		$idx = strpos( $mo_original, 0 );
		if ( $idx !== false && $idx < strlen( $mo_original ) - 1 ) {
			$entry->is_plural = true;
			$entry->plural = substr( $mo_original, $idx + 1 );
		}

		// plural translations are also separated by \0
		$idx = strpos( $translation, 0 );
		if ( $idx === false ) {
			$entry->translations[] = $translation;
		} else {
			$entry->translations[] = substr( $translation, 0, $idx );
			if ( $idx < strlen ( $translation ) - 1 ) {
				$entry->translations[] = substr( $translation, $idx + 1 );
			}
		}
	}

	function translate_entry( &$entry ) {
		$key = $entry->key();
		if ( isset( $this->entries[$key] ) ) {
			return $this->entries[$key];
		} else {
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
						// If key was found create a new translation entry.
						
						// read translation string
						$moitem->reader->seekto( $moitem->translations_table[$pos+1] );
						$translation = $moitem->reader->read( $moitem->translations_table[$pos] );

						//$newentry = &$this->make_entry( $mo_original, $translation );
						//$this->entries[$key] = &$newentry;
						$this->update_entry( $entry, $mo_original, $translation );
						$this->entries[$key] = &$entry;

						if ( $j > 0 ) {
							// Assuming frequent subsequent translations from the same file resort MOs by access time to avoid unnecessary in the wrong files.
							$moitem->last_access=time();
							usort( $this->MOs, function ($a, $b) {return ($b->last_access - $a->last_access);} );
						}

						//return $newentry;
						return $entry;
					} else if ( $cmpval < 0 ) {
						$right = $pivot - 1;
					} else { // if ($cmpval>0) 
						$left = $pivot + 1;
					}
				}
			}
		}
		
		// No translation found. Create dummy entry to prevent repeated searches for the missing translation
		$translation = $entry->singular;
		if ( $entry->plural ) {
			$translation .= chr(0).$entry->plural;
		}
		$this->update_entry ( $entry, $key, $translation);
		$this->entries[$key] = &$entry;
		return true;
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

function load_textdomain_override( $retval, $domain, $mofile ) {
	global $l10n, $wp_performance_pack;

	if ( $wp_performance_pack->options['disable_backend_translation'] && is_admin() && !defined( 'DOING_AJAX' ) ) {
		$l10n[$domain] = new NOOP_Translations();
		return true;
	} else {
		do_action( 'load_textdomain', $domain, $mofile );
		$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );
		if ( isset( $l10n[$domain] ) ) {
			$mo = $l10n[$domain];
			if ( $mo instanceof MO_dynamic && $mo->Mo_file_loaded( $mofile ) ) return true;
		}
		if ( ! is_readable( $mofile ) ) return false;

		$mo = new MO_dynamic ();
		if ( !$mo->import_from_file( $mofile ) ) return false; 
		if ( isset( $l10n[$domain] ) )
			$mo->merge_with( $l10n[$domain] );
		$l10n[$domain] = &$mo;
		
		return true;
	}
}

// TODO: override_unload_textdomain?

add_filter( 'override_load_textdomain', 'load_textdomain_override', 0, 3 );
?>