<?php
/**
 * Class for CDN support for front end and back end and dynamic image links.
 *
 * Dynamic image links substitute the wp-content baseurl part in urls to images 
 * in a and img tags with {{wpppdynamic}} and replaces that substitue back to 
 * the baseurl or a cdn url via get_content filter
 *
 * @author Björn Ahrens
 * @package WP Performance Pack
 * @since 1.7
 */

class WPPP_CDN_Support extends WPPP_CDN_Support_Skeleton {
	private $cdn_fallback = false;

	function init () {
		if ( $this->wppp->options['dyn_links'] ) {
			// url substitution only if dynamic image links are activated
			add_filter( 'content_save_pre', array( $this, 'content_substitute_uploadbase' ), 99 );

			add_filter( 'the_content', array( $this, 'front_end_rewrite_url' ), 99 );
			add_filter( 'the_editor_content', array( $this, 'back_end_rewrite_url' ), 99 );
			add_filter( 'the_content_rss', array( $this, 'front_end_rewrite_url' ), 99 );
			add_filter( 'the_content_feed', array( $this, 'front_end_rewrite_url' ), 99 );
		}


		if ( $this->wppp->options['cdn'] !== false ) {
			$cdn_test = get_transient( 'wppp_cdntest' );
			if ( false === $cdn_test ) {
				$result = $this->verify_working_cdn();
				if ( true === $result ) {
					set_transient( 'wppp_cdntest', 'ok', 6 * HOUR_IN_SECONDS );
					$cdn_test = 'ok';
				} else {
					set_transient( 'wppp_cdntest', $result->get_error_message(), 15 * MINUTE_IN_SECONDS );
					$cdn_test = 'failed';
				}
			}

			if ( 'ok' !== $cdn_test ) {
				$this->cdn_fallback = true;
				if ( is_admin() && current_user_can( 'manage_options' ) ) {
					add_action( 'admin_notices', array( $this, 'cdn_failed_notice') );
				}
			}

			if ( !$this->cdn_fallback ) {
				// activate cdn in get attachment url
				add_filter( 'wp_get_attachment_url', array ( $this, 'cdn_get_attachment_url' ), 10, 2 );
			}
		}
	}

	static function restore_static_links ( $output_status = false ) {
		// restore links
		if ( $output_status ) {
			echo '<p>Restoring dynamic links...<br/> This may take a while, depending on the number of posts.</p>';
			flush();
		}

		$uploads = wp_upload_dir();
		$upbase = $uploads['baseurl'];

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE wp_posts SET post_content = REPLACE ( post_content, %s, %s );",
				'{{wpppdynamic}}',
				$upbase
			)
		);

		// delete meta data
		if ( $output_status ) {
			echo '<p>Deleting wppp meta data...</p>';
			flush();
		}

		$wpdb->query( 
			$wpdb->prepare(
				"DELETE FROM wp_postmeta WHERE meta_key = %s;",
				'wpppdynamic'
			)
		);

		if ( $output_status ) {
			echo '<p>Finished.</p>';
			flush();
		}
	}

	function cdn_failed_notice () {
		?>
		<div class="error"> 
			<h4>WPPP CDN Support - Availability test failed!</h4>
			<p>
				Either the CDN is down or CDN configuration isn't working. Fallback to local serving is active. This check will be repeated every 15 minutes until the configuration is changed or the CDN is back up.
			</p>
			<?php
				$error = get_transient( 'wppp_cdntest' );
				if ( false !== $error ) : ?>
					<p>Error message: <em><?php echo $error ?></em></p>
				<?php endif;
			?>
			<p>
				<a href="options-general.php?page=wppp_options_page">Check WPPP CDN Support settings</a>
			</p> 
		</div>
		<?php
	}

	function verify_working_cdn () {
		$url = plugins_url( 'cdn_test.php', dirname( __FILE__ ) );
		$cdn_parsed = $this->get_cdn_parsed();
		if ( NULL !== $cdn_parsed ) {
			$url_parsed = parse_url( $url );
			$new_parsed = array_merge( $url_parsed, $cdn_parsed );
			$url = $this->unparse_url( $new_parsed );

			$response = wp_remote_get( $url );

			if ( !is_wp_error( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				if ( $body !== 'WPPP CDN TEST ' . site_url() ) {
					return new WP_Error( 'wrongresult', 'Test data mismatch. Expected: "WPPP CDN TEST ' . site_url() . '" / Received: "' . wp_strip_all_tags( $body ) .'"' );
				} else {
					return true;
				}
			} else {
				return $response;
			}
		} else {
			return new WP_Error( 'cdnconfigerror', 'CDN configuration error. Probably missing CDN URL.' );
		}
		return false;
	}

	function unparse_url($parsed_url) { 
		$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
		$host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
		$port     = isset($parsed_url['port']) && !empty($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
		$user     = isset($parsed_url['user']) && !empty($parsed_url['user']) ? $parsed_url['user'] : ''; 
		$pass     = isset($parsed_url['pass']) && !empty($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
		$pass     = ($user || $pass) ? "$pass@" : ''; 
		$path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
		$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
		$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
		return "$scheme$user$pass$host$port$path$query$fragment"; 
	}

	function get_cdn_parsed () {
		switch ( $this->wppp->options['cdn'] ) {
			case 'maxcdn'		:
			case 'customcdn'	: $cdn_parsed = parse_url( $this->wppp->options['cdnurl'] );
								if ( !isset( $cdn_parsed['host'] ) || empty( $cdn_parsed['host'] ) ) {
									return NULL;
								}
								break;
			case 'coralcdn'		: $cdn_parsed = parse_url( site_url() );
								$cdn_parsed['host'] .= '.nyud.net';
								$cdn_parsed['port'] = '';
								$cdn_parsed['pass'] = '';
								$cdn_parsed['user'] = '';
								unset( $cdn_parsed['path'] );
								break;
			default				: $cdn_parsed = NULL;
								break;
		}

		if ( NULL !== $cdn_parsed ) {
			if ( !isset( $cdn_parsed['scheme'] ) ) { $cdn_parsed['scheme'] = 'http'; }
			if ( !isset( $cdn_parsed['port'] ) ) { $cdn_parsed['port'] = ''; }
			if ( !isset( $cdn_parsed['user'] ) ) { $cdn_parsed['user'] = ''; }
			if ( !isset( $cdn_parsed['pass'] ) ) { $cdn_parsed['pass'] = ''; }
		}
		return $cdn_parsed;
	}

	function content_substitute_uploadbase ($content) {
		// replace "wp_conent_url/path/to/image-file.ext" with "{{wpppdynamic}}/path/to/image-file.ext"

		// get upload and cdn base
		$uploads = wp_upload_dir();
		$upbase = $uploads['baseurl'];

		$cdn_parsed = $this->get_cdn_parsed();
		if ( NULL !== $cdn_parsed ) {
			$upload_parsed = parse_url( $upbase );
			$new_parsed = array_merge( $upload_parsed, $cdn_parsed );
			$cdnbase = $this->unparse_url( $new_parsed );
		} else {
			$cdnbase = NULL;
		}

		// search links to images
		// content is escaped!
		$pattern ="/<a(.*?)href=\\\\('|\")(.*?).(gif|jpeg|jpg|png)\\\\('|\")(.*?)>/i";
		$content = preg_replace_callback( $pattern, 
										function ( $m ) use ( $upbase, $cdnbase ) {
											// test for upload base, cdn url, etc. and replace with {{wpppdynamic}}
											$part1 = "<a{$m[1]}href=\\{$m[2]}";
											$link = $m[3] . '.' . $m[4];
											$part2 = "\\{$m[5]}{$m[6]}>";

											if ( strncmp( $upbase, $link, strlen( $upbase ) ) === 0 ) {
												$link = '{{wpppdynamic}}' . substr( $link, strlen( $upbase ) );
											} else if ( NULL !== $cdnbase && strncmp( $cdnbase, $link, strlen( $cdnbase ) ) === 0 ) {
												$link = '{{wpppdynamic}}' . substr( $link, strlen( $cdnbase ) );
											}

											return $part1 . $link . $part2;
										},
										$content );

		// repeat with img src's...
		$pattern ="/<img(.*?)src=\\\\('|\")(.*?).(gif|jpeg|jpg|png)\\\\('|\")(.*?)>/i";
		$content = preg_replace_callback( $pattern, 
										function ( $m ) use ( $upbase, $cdnbase ) {
											// test for upload base, cdn url, etc. and replace with {{wpppdynamic}}
											$part1 = "<img{$m[1]}src=\\{$m[2]}";
											$link = $m[3] . '.' . $m[4];
											$part2 = "\\{$m[5]}{$m[6]}>";

											if ( strncmp( $upbase, $link, strlen( $upbase ) ) === 0 ) {
												$link = '{{wpppdynamic}}' . substr( $link, strlen( $upbase ) );
											} else if ( NULL !== $cdnbase && strncmp( $cdnbase, $link, strlen( $cdnbase ) ) === 0 ) {
												$link = '{{wpppdynamic}}' . substr( $link, strlen( $cdnbase ) );
											}

											return $part1 . $link . $part2;
										},
										$content );

		// mark post content as substituted
		global $post;
		if ( $post ) {
			update_post_meta( $post->ID, 'wpppdynamic', '1' );
		}
		return $content;
	}

	function content_set_uploadbase ( $content, $force_upbase ) {
		if ( $this->wppp->options['dyn_links'] ) {
			// check, if this posts content is already substituted
			// only do so if dyn_links is enabled. substitution will be done always (for now)
			// so once modified posts will still get displayed correctly, even when dyn_links is disabled
			// (at least while WPPP is installed and activated).
			// TODO: implement tools to (re)substitute upload base on all posts
			global $post;
			if ( '1' !== get_post_meta( $post->ID, 'wpppdynamic', true ) ) {
				// if not update post, which in turn causes content_substitute_uploadbase to be called
				wp_update_post( $post ); 
			}
		}
		
		// get upload and cdn base
		$uploads = wp_upload_dir();
		$upbase = $uploads['baseurl'];

		if ( !$this->cdn_fallback && !$force_upbase && false !== $this->wppp->options['cdn'] ) {
			$cdn_parsed = $this->get_cdn_parsed();
			if ( NULL !== $cdn_parsed ) {
				$upload_parsed = parse_url( $upbase );
				$new_parsed = array_merge( $upload_parsed, $cdn_parsed );
				$upbase = $this->unparse_url( $new_parsed );
			}
		}

		$content = str_replace( '{{wpppdynamic}}', $upbase, $content );

		return $content;
	}

	function front_end_rewrite_url ( $content ) {
		// force uploadbase if cdn is only enabled for back end
		return $this->content_set_uploadbase( $content, $this->wppp->options['cdn_images'] === 'back' );
	}

	function back_end_rewrite_url ( $content ) {
		// force uploadbase if cdn is only enabled for front end
		return $this->content_set_uploadbase( $content, $this->wppp->options['cdn_images'] === 'front' );
	}

	function cdn_get_attachment_url( $url, $post_id ) {
		if ( wp_attachment_is_image( $post_id ) ) {
			$cdn_parsed = $this->get_cdn_parsed();
			if ( NULL !== $cdn_parsed ) {
				$url_parsed = parse_url( $url );
				$new_parsed = array_merge( $url_parsed, $cdn_parsed );
				return $this->unparse_url( $new_parsed );
			}
		}
		return $url;
	}
}

?>