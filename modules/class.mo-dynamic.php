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
	var $originals = array();
	var $originals_table;
	var $translations_table;
	var $last_access;

	var $hash_table;
	var $hash_length = 0;

	var $is_cached = false;
}

/**
 * Class for working with MO files
 * Translation entries are created dynamically.
 * Due to this export and save functions are not implemented.
 */
class MO_dynamic extends Gettext_Translations {
	private $domain = '';
	private $caching = false;
	private $modified = false;

	protected $_nplurals = 2;
	protected $MOs = array();
	protected $translations = NULL;

	function __construct( $domain, $caching = false ) {
		$this->domain = $domain;
		$this->caching = $caching;
	}

	function __destruct() {
		foreach ( $this->MOs as &$moitem ) {
			if ( $moitem->reader!=NULL ) {
				$moitem->reader->close();
				$moitem->reader=NULL;
			}
		}
	}

	function get_current_url () {
		$current_url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		if ( ($len = strlen( $_SERVER['QUERY_STRING'] ) ) > 0)
			$current_url = substr ( $current_url, 0, strlen($current_url) - $len - 1 );
		if (isset ($_GET['page']))
			$current_url .= '?page='.$_GET['page'];
		return $current_url;
	}

	function import_from_file( $filename ) {
		$moitem = new MO_item();
		$moitem->mofile = $filename;
		$this->MOs[] = $moitem;
		
		// because only a reference to the MO file is created, at this point there is no information if $filename is a valid MO file, so the return value is always true
		return true;
	}

	function import_domain_from_cache () {
		// build cache key from domain and request uri
		if ( $this->caching ) {
			if ( is_admin() ) {
				$key = md5( $this->domain . $this->get_current_url() );
			} else {
				$key = 'frontend_'.$this->domain;
			}
			$this->translations = wp_cache_get( 'domain_'.$key, 'wppp_translations' );
			if ( $this->translations === false ) {
				$this->translations = array();
			}
		} else {
			$this->translations = array();
		}
	}

	function import_file_from_cache ( $moitem ) {
		if ( $this->caching ) {
			return false;
		}

		$key = md5_file ( $moitem->mofile );

		$arr = wp_cache_get( 'origtbl'.$key, 'wppp_translations' );
		if ( $arr === false ) {
			return false;
		}

		if ( class_exists ( 'SplFixedArray' ) )
			$moitem->originals_table = SplFixedArray::fromArray( $arr, false );
		else
			$moitem->originals_table = $arr;

		$arr = wp_cache_get ( 'transtbl'.$key, 'wppp_translations' );
		if ( class_exists ( 'SplFixedArray' ) )
			$moitem->translations_table = SplFixedArray::fromArray( $arr, false );
		else
			$moitem->translations_table = $arr;

		$arr = wp_cache_get ( 'hashtbl'.$key, 'wppp_translations' );
		if ($arr !== false) {
			if ( class_exists ( 'SplFixedArray' ) )
				$moitem->hash_table = SplFixedArray::fromArray( $arr, false );
			else
				$moitem->hash_table = $arr;
			$hash_lenght = count( $arr );
		}

		$moitem->total = count ($moitem->originals_table);
		$moitem->is_cached = true;
		return true;
	}

	function save_to_cache () {
		if ( $this->caching && $this->modified ) {
			if ( is_admin() ) {
				$key = md5( $this->domain . $this->get_current_url() );
				wp_cache_set( 'domain_'.$key, $this->translations, 'wppp_translations', 1800 ); // keep cache for 30 minutes
			} else {
				$key = 'frontend_'.$this->domain;
				wp_cache_set( 'domain_'.$key, $this->translations, 'wppp_translations', 3600 ); // keep cache for 60 minutes
			}
		}

		foreach ( $this->MOs as $moitem ) {
			if ( ! $moitem->is_cached && $moitem->reader !== NULL ) {
				$key = md5_file ( $moitem->mofile );
				if ( class_exists ( 'SplFixedArray' ) ) {
					wp_cache_set( 'origtbl'.$key, $moitem->originals_table->toArray(), 'wppp_translations' );
					wp_cache_set( 'transtbl'.$key, $moitem->translations_table->toArray(), 'wppp_translations' );
					if ( $moitem->hash_length > 0 ) {
						wp_cache_set( 'hashtbl'.$key, $moitem->hash_table->toArray(), 'wppp_translations' );
					}
				} else {
					wp_cache_set( 'origtbl'.$key, $moitem->originals_table, 'wppp_translations' );
					wp_cache_set( 'transtbl'.$key, $moitem->translations_table, 'wppp_translations' );
					if ( $moitem->hash_length > 0 ) {
						wp_cache_set( 'hashtbl'.$key, $moitem->hash_table, 'wppp_translations' );
					}
				}
			}
		}
	}

	function import_from_reader( &$moitem ) {
		if ( $moitem->reader !== NULL) {
			return true;
		}
		
		$moitem->reader=new POMO_FileReader( $moitem->mofile );

		$endian_string = MO::get_byteorder( $moitem->reader->readint32() );
		if ( false === $endian_string ) {
			$moitem->reader->close();
			$moitem->reader = NULL;
			return false;
		}
		$moitem->reader->setEndian( $endian_string );
		$endian = ( 'big' == $endian_string ) ? 'N' : 'V';

		if ( $this->import_file_from_cache( $moitem ) ) {
			return true;
		}

		$header = $moitem->reader->read( 24 );
		if ( $moitem->reader->strlen( $header ) != 24 ) {
			$moitem->reader->close();
			$moitem->reader = NULL;
			return false;
		}

		// parse header
		$header = unpack( "{$endian}revision/{$endian}total/{$endian}originals_lenghts_addr/{$endian}translations_lenghts_addr/{$endian}hash_length/{$endian}hash_addr", $header );
		if ( !is_array( $header ) ) {
			$moitem->reader->close();
			$moitem->reader = NULL;
			return false;
		}
		extract( $header );

		// support revision 0 of MO format specs, only
		if ( $revision != 0 ) {
			$moitem->reader->close();
			$moitem->reader = NULL;
			return false;
		}

		$moitem->total = $total;

		// read hashtable
		$moitem->hash_length = $hash_length;
		if ( $hash_length > 0 ) {
			$moitem->reader->seekto ( $hash_addr );
			$str = $moitem->reader->read( $hash_length * 4 );
			if ( $moitem->reader->strlen( $str ) != $hash_length * 4 ) {
				$moitem->reader->close();
				$moitem->reader = NULL;
				return false;
			} 
			if ( class_exists ( 'SplFixedArray' ) )
				$moitem->hash_table = SplFixedArray::fromArray( unpack ( $endian.$hash_length, $str ), false );
			else
				$moitem->hash_table = array_slice( unpack ( $endian.$hash_length, $str ), 0 ); // force zero based index
		}

		// read originals' indices
		if ( class_exists( 'SplFixedArray' ) )
			$moitem->originals = new SplFixedArray ( $total );
		else
			$moitem->originals = array();
		$moitem->reader->seekto( $originals_lenghts_addr );
		$originals_lengths_length = $translations_lenghts_addr - $originals_lenghts_addr;
		if ( $originals_lengths_length != $total * 8 ) {
			$moitem->reader->close();
			$moitem->reader = NULL;
			return false;
		}
		$str = $moitem->reader->read( $originals_lengths_length );
		if ( $moitem->reader->strlen( $str ) != $originals_lengths_length ) {
			$moitem->reader->close();
			$moitem->reader = NULL;
			return false;
		}
		if ( class_exists ( 'SplFixedArray' ) )
			$moitem->originals_table = SplFixedArray::fromArray( unpack ( $endian.($total * 2), $str ), false );
		else
			$moitem->originals_table = array_slice( unpack ( $endian.($total * 2), $str ), 0 ); // force zero based index

		// read translations' indices
		$translations_lenghts_length = $hash_addr - $translations_lenghts_addr;
		if ( $translations_lenghts_length != $total * 8 ) {
			$moitem->reader->close();
			$moitem->reader = NULL;
			return false;
		}
		$str = $moitem->reader->read( $translations_lenghts_length );
		if ( $moitem->reader->strlen( $str ) != $translations_lenghts_length ) {
			$moitem->reader->close();
			$moitem->reader = NULL;
			return false;
		}
		if ( class_exists ( 'SplFixedArray' ) )
			$moitem->translations_table = SplFixedArray::fromArray( unpack ( $endian.($total * 2), $str ), false );
		else
			$moitem->translations_table = array_slice( unpack ( $endian.($total * 2), $str ), 0 ); // force zero based index

		// read headers
		for ( $i = 0, $max = $total * 2; $i < $max; $i+=2 ) {
			if ( $moitem->originals_table[$i] > 0 ) {
				$moitem->reader->seekto( $moitem->originals[$i+1] );
				$original = $moitem->reader->read( $moitem->originals_table[$i] );
						
				$j = strpos( $original, 0 );
				if ( $j !== false )
					$original = substr( $original, 0, $i );
			} else
				$original = '';

			if ( $original === '' ) {
				if ( $moitem->translations_table[$i] > 0 ) {
					$moitem->reader->seekto( $moitem->translations_table[$i+1] );
					$translation = $moitem->reader->read( $moitem->translations_table[$i] );
				} else
					$translation = '';
				
				$this->set_headers( $this->make_headers( $translation ) );
			} else
				return true;
		}
		return true;
	}

	protected function search_translation ( $key ) {
		static $hash_val; 	// declare hash_val as static. this way it can be calculated only if needed, i.e. when a moitem has a hash table. if it would be precalculated
							// then it would require either a "pre-check" for hash table existence or the hash value could be calculated although not needed.
							// by declaring it static and setting it to NULL with each call, it is guaranteed to be calculated maximal once per call.
		$hash_val = NULL;
		$key_len = strlen( $key );

		for ( $j = 0, $max = count ( $this->MOs ); $j < $max; $j++ ) {
			$moitem = $this->MOs[$j];
			if ( $moitem->reader == NULL ) {
				if ( !$this->import_from_reader( $moitem ) ) {
					// Error reading MO file, so delete it from MO list to prevent subsequent access
					unset( $this->MOs[$j] );
					return false; // return or continue?
				}
			}

			if ($moitem->hash_length>0) {
				/* Use mo file hash table to search translation */

				// calculate hash value
				// hashpjw function by P.J. Weinberger from gettext hash-string.c
				// adapted to php and its quirkiness caused by missing unsigned ints and shift operators...
				if ( $hash_val === NULL) {
					$hash_val = 0;
					for ($i = 0; $i < $key_len; $i++ ) {
						$hash_val = ( $hash_val << 4 ) + ord( $key{$i} );
						$g = $hash_val & 0xF0000000;
						if( $g !== 0 ){
							if ( $g < 0 )
								$hash_val ^= ( ( ($g & 0x7FFFFFFF) >> 24 ) | 0x80 ); // wordaround: php operator >> is arithmetic, not logic, so shifting negative values gives unexpected results. Cut sign bit, shift right, set sign bit again.
								/* 
								workaround based on this function (adapted to actual used parameters):
								
								function shr($var,$amt) {
									$mask = 0x40000000;
									if($var < 0) {
										$var &= 0x7FFFFFFF;
										$mask = $mask >> ($amt-1);
										return ($var >> $amt) | $mask;
									}
									return $var >> $amt;
								} 
								*/
							else
								$hash_val ^= ( $g >> 24 );
							$hash_val ^= $g;
						}
					}
				}

				// calculate hash table index and increment
				if ( $hash_val >= 0 ) {
					$idx = $hash_val % $moitem->hash_length;
					$incr = 1 + ($hash_val % ($moitem->hash_length - 2));
				} else {
					$hash_val = (float) sprintf('%u', $hash_val); // workaround php not knowing unsigned int - %u outputs $hval as unsigned, then cast to float 
					$idx = fmod( $hash_val, $moitem->hash_length);
					$incr = 1 + fmod ($hash_val, ($moitem->hash_length - 2));
				}

				$orig_idx = $moitem->hash_table[$idx];
				while ( $orig_idx != 0 ) {
					$orig_idx--; // ? taken from original gettext function, doesn't work without, don't understand right now... possibly some index based 0 and 1 stuff

					if ( $orig_idx < $moitem->total // orig_idx must be in range
						 && $moitem->originals_table[$orig_idx * 2] >= $key_len ) { // and original length must be equal or greater as key length (original can contain plural forms)
						$pos = $orig_idx * 2;

						if ( isset( $moitem->originals[$orig_idx] ) ) {
							$mo_original = $moitem->originals[$orig_idx];
						} else {
							// read and "cache" original string to improve performance of subsequent searches
							if ( $moitem->originals_table[$pos] > 0 ) {
								$moitem->reader->seekto( $moitem->originals_table[$pos+1] );
								$mo_original = $moitem->reader->read( $moitem->originals_table[$pos] );
							} else
								$mo_original = '';
							$moitem->originals[$orig_idx] = $mo_original;
						}

						if ( $moitem->originals_table[$pos] == $key_len
							 || ord( $mo_original{$key_len} ) == 0 ) {
							// strings can only match if they have the same length, no need to inspect otherwise

							$i = strpos( $mo_original, 0 );
							if ( $i !== false )
								$cmpval = strncmp( $key, $mo_original, $i );
							else 
								$cmpval = strcmp( $key, $mo_original );

							if ( $cmpval === 0 ) {
								// key found, read translation string
								$moitem->reader->seekto( $moitem->translations_table[$pos+1] );
								$translation = $moitem->reader->read( $moitem->translations_table[$pos] );
								if ( $j > 0 ) {
									// Assuming frequent subsequent translations from the same file resort MOs by access time to avoid unnecessary in the wrong files.
									$moitem->last_access=time();
									usort( $this->MOs, function ($a, $b) {return ($b->last_access - $a->last_access);} );
								}
								return $translation;
							}
						}
					}

					if ($idx >= $moitem->hash_length - $incr)
						$idx -= ($moitem->hash_length - $incr);
					else
						$idx += $incr;
					$orig_idx = $moitem->hash_table[$idx];
				}
			} else {
				/* No hash-table, do binary search for matching originals entry */
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
					if ( $i !== false )
						$cmpval = strncmp( $key, $mo_original, $i );
					else
						$cmpval = strcmp( $key, $mo_original );

					if ( $cmpval === 0 ) {
						// key found read translation string
						$moitem->reader->seekto( $moitem->translations_table[$pos+1] );
						$translation = $moitem->reader->read( $moitem->translations_table[$pos] );
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

		if ( $this->translations === NULL ) {
			$this->import_domain_from_cache();
		}

		if ( isset( $this->translations[$s] ) ) {
			$t = $this->translations[$s];
		} else {
			$t = $this->search_translation( $s );
			$this->translations[$s] = $t;
			$this->modified = true;
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
			$this->modified = true;
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

		if ( $this->translations === NULL ) {
			$this->import_domain_from_cache();
		}

		if ( isset( $this->translations[$s] ) ) {
			$t = $this->translations[$s];
		} else {
			$t = $this->search_translation( $s );
			$this->translations[$s] = $t;
			$this->modified = true;
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
			$this->modified = true;
			return $default;
		}
	}

	function merge_with( &$other ) {
		if ( $other instanceof MO_dynamic ) {
			if ( $other->translations !== NULL ) {
				foreach( $other->translations as $key => $translation ) {
					$this->entries[$key] = $translation;
				}
			}

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
		} else if ( ! $other instanceof NOOP_Translations ) {
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

	function merge_with( &$other ) {
		if ( $other instanceof MO_dynamic_Debug ) {
			$this->translate_hits += $other->translate_hits;
			$this->translate_plural_hits += $other->translate_plural_hits;
			$this->search_translation_hits += $other->search_translation_hits;
		}
		parent::merge_with( $other );
	}
}

?>