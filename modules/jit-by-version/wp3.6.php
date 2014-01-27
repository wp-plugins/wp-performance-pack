<?php

function wp_jit_default_scripts( &$scripts ) {
	if ( !$guessurl = site_url() )
		$guessurl = wp_guess_url();

	$scripts->base_url = $guessurl;
	$scripts->content_url = defined('WP_CONTENT_URL')? WP_CONTENT_URL : '';
	$scripts->default_version = get_bloginfo( 'version' );
	$scripts->default_dirs = array('/wp-admin/js/', '/wp-includes/js/');

	$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

	$scripts->add( 'utils', "/wp-includes/js/utils$suffix.js" );
	$scripts->add( 'common', "/wp-admin/js/common$suffix.js", array('jquery', 'hoverIntent', 'utils'), false, 1 );
	$scripts->add( 'sack', "/wp-includes/js/tw-sack$suffix.js", array(), '1.6.1', 1 );
	$scripts->add( 'quicktags', "/wp-includes/js/quicktags$suffix.js", array(), false, 1 );
	$scripts->add( 'colorpicker', "/wp-includes/js/colorpicker$suffix.js", array('prototype'), '3517m' );
	$scripts->add( 'editor', "/wp-admin/js/editor$suffix.js", array('utils','jquery'), false, 1 );
	$scripts->add( 'wp-fullscreen', "/wp-admin/js/wp-fullscreen$suffix.js", array('jquery'), false, 1 );
	$scripts->add( 'wp-ajax-response', "/wp-includes/js/wp-ajax-response$suffix.js", array('jquery'), false, 1 );
	$scripts->add( 'wp-pointer', "/wp-includes/js/wp-pointer$suffix.js", array( 'jquery-ui-widget', 'jquery-ui-position' ), '20111129a', 1 );
	$scripts->add( 'autosave', "/wp-includes/js/autosave$suffix.js", array('schedule', 'wp-ajax-response'), false, 1 );
	$scripts->add( 'heartbeat', "/wp-includes/js/heartbeat$suffix.js", array('jquery'), false, 1 );
	$scripts->add( 'wp-auth-check', "/wp-includes/js/wp-auth-check$suffix.js", array('heartbeat'), false, 1 );
	$scripts->add( 'wp-lists', "/wp-includes/js/wp-lists$suffix.js", array( 'wp-ajax-response', 'jquery-color' ), false, 1 );

	// WordPress no longer uses or bundles Prototype or script.aculo.us. These are now pulled from an external source.
	$scripts->add( 'prototype', '//ajax.googleapis.com/ajax/libs/prototype/1.7.1.0/prototype.js', array(), '1.7.1');
	$scripts->add( 'scriptaculous-root', '//ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/scriptaculous.js', array('prototype'), '1.9.0');
	$scripts->add( 'scriptaculous-builder', '//ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/builder.js', array('scriptaculous-root'), '1.9.0');
	$scripts->add( 'scriptaculous-dragdrop', '//ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/dragdrop.js', array('scriptaculous-builder', 'scriptaculous-effects'), '1.9.0');
	$scripts->add( 'scriptaculous-effects', '//ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/effects.js', array('scriptaculous-root'), '1.9.0');
	$scripts->add( 'scriptaculous-slider', '//ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/slider.js', array('scriptaculous-effects'), '1.9.0');
	$scripts->add( 'scriptaculous-sound', '//ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/sound.js', array( 'scriptaculous-root' ), '1.9.0' );
	$scripts->add( 'scriptaculous-controls', '//ajax.googleapis.com/ajax/libs/scriptaculous/1.9.0/controls.js', array('scriptaculous-root'), '1.9.0');
	$scripts->add( 'scriptaculous', false, array('scriptaculous-dragdrop', 'scriptaculous-slider', 'scriptaculous-controls') );

	// not used in core, replaced by Jcrop.js
	$scripts->add( 'cropper', '/wp-includes/js/crop/cropper.js', array('scriptaculous-dragdrop') );

	// jQuery
	$scripts->add( 'jquery', false, array( 'jquery-core', 'jquery-migrate' ), '1.10.2' );
	$scripts->add( 'jquery-core', '/wp-includes/js/jquery/jquery.js', array(), '1.10.2' );
	$scripts->add( 'jquery-migrate', "/wp-includes/js/jquery/jquery-migrate$suffix.js", array(), '1.2.1' );

	// full jQuery UI
	$scripts->add( 'jquery-ui-core', '/wp-includes/js/jquery/ui/jquery.ui.core.min.js', array('jquery'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-core', '/wp-includes/js/jquery/ui/jquery.ui.effect.min.js', array('jquery'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-blind', '/wp-includes/js/jquery/ui/jquery.ui.effect-blind.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-bounce', '/wp-includes/js/jquery/ui/jquery.ui.effect-bounce.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-clip', '/wp-includes/js/jquery/ui/jquery.ui.effect-clip.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-drop', '/wp-includes/js/jquery/ui/jquery.ui.effect-drop.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-explode', '/wp-includes/js/jquery/ui/jquery.ui.effect-explode.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-fade', '/wp-includes/js/jquery/ui/jquery.ui.effect-fade.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-fold', '/wp-includes/js/jquery/ui/jquery.ui.effect-fold.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-highlight', '/wp-includes/js/jquery/ui/jquery.ui.effect-highlight.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-pulsate', '/wp-includes/js/jquery/ui/jquery.ui.effect-pulsate.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-scale', '/wp-includes/js/jquery/ui/jquery.ui.effect-scale.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-shake', '/wp-includes/js/jquery/ui/jquery.ui.effect-shake.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-slide', '/wp-includes/js/jquery/ui/jquery.ui.effect-slide.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-effects-transfer', '/wp-includes/js/jquery/ui/jquery.ui.effect-transfer.min.js', array('jquery-effects-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-accordion', '/wp-includes/js/jquery/ui/jquery.ui.accordion.min.js', array('jquery-ui-core', 'jquery-ui-widget'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-autocomplete', '/wp-includes/js/jquery/ui/jquery.ui.autocomplete.min.js', array('jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-position', 'jquery-ui-menu'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-button', '/wp-includes/js/jquery/ui/jquery.ui.button.min.js', array('jquery-ui-core', 'jquery-ui-widget'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-datepicker', '/wp-includes/js/jquery/ui/jquery.ui.datepicker.min.js', array('jquery-ui-core'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-dialog', '/wp-includes/js/jquery/ui/jquery.ui.dialog.min.js', array('jquery-ui-resizable', 'jquery-ui-draggable', 'jquery-ui-button', 'jquery-ui-position'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-draggable', '/wp-includes/js/jquery/ui/jquery.ui.draggable.min.js', array('jquery-ui-core', 'jquery-ui-mouse'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-droppable', '/wp-includes/js/jquery/ui/jquery.ui.droppable.min.js', array('jquery-ui-draggable'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-menu', '/wp-includes/js/jquery/ui/jquery.ui.menu.min.js', array( 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-position' ), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-mouse', '/wp-includes/js/jquery/ui/jquery.ui.mouse.min.js', array('jquery-ui-widget'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-position', '/wp-includes/js/jquery/ui/jquery.ui.position.min.js', array('jquery'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-progressbar', '/wp-includes/js/jquery/ui/jquery.ui.progressbar.min.js', array('jquery-ui-widget'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-resizable', '/wp-includes/js/jquery/ui/jquery.ui.resizable.min.js', array('jquery-ui-core', 'jquery-ui-mouse'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-selectable', '/wp-includes/js/jquery/ui/jquery.ui.selectable.min.js', array('jquery-ui-core', 'jquery-ui-mouse'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-slider', '/wp-includes/js/jquery/ui/jquery.ui.slider.min.js', array('jquery-ui-core', 'jquery-ui-mouse'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-sortable', '/wp-includes/js/jquery/ui/jquery.ui.sortable.min.js', array('jquery-ui-core', 'jquery-ui-mouse'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-spinner', '/wp-includes/js/jquery/ui/jquery.ui.spinner.min.js', array( 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-button' ), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-tabs', '/wp-includes/js/jquery/ui/jquery.ui.tabs.min.js', array('jquery-ui-core', 'jquery-ui-widget'), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-tooltip', '/wp-includes/js/jquery/ui/jquery.ui.tooltip.min.js', array( 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-position' ), '1.10.3', 1 );
	$scripts->add( 'jquery-ui-widget', '/wp-includes/js/jquery/ui/jquery.ui.widget.min.js', array('jquery'), '1.10.3', 1 );

	// deprecated, not used in core, most functionality is included in jQuery 1.3
	$scripts->add( 'jquery-form', "/wp-includes/js/jquery/jquery.form$suffix.js", array('jquery'), '2.73', 1 );

	// jQuery plugins
	$scripts->add( 'jquery-color', "/wp-includes/js/jquery/jquery.color.min.js", array('jquery'), '2.1.1', 1 );
	$scripts->add( 'suggest', "/wp-includes/js/jquery/suggest$suffix.js", array('jquery'), '1.1-20110113', 1 );
	$scripts->add( 'schedule', '/wp-includes/js/jquery/jquery.schedule.js', array('jquery'), '20m', 1 );
	$scripts->add( 'jquery-query', "/wp-includes/js/jquery/jquery.query.js", array('jquery'), '2.1.7', 1 );
	$scripts->add( 'jquery-serialize-object', "/wp-includes/js/jquery/jquery.serialize-object.js", array('jquery'), '0.2', 1 );
	$scripts->add( 'jquery-hotkeys', "/wp-includes/js/jquery/jquery.hotkeys$suffix.js", array('jquery'), '0.0.2m', 1 );
	$scripts->add( 'jquery-table-hotkeys', "/wp-includes/js/jquery/jquery.table-hotkeys$suffix.js", array('jquery', 'jquery-hotkeys'), false, 1 );
	$scripts->add( 'jquery-touch-punch', "/wp-includes/js/jquery/jquery.ui.touch-punch.js", array('jquery-ui-widget', 'jquery-ui-mouse'), '0.2.2', 1 );
	$scripts->add( 'jquery-masonry', "/wp-includes/js/jquery/jquery.masonry.min.js", array('jquery'), '2.1.05', 1 );
	$scripts->add( 'thickbox', "/wp-includes/js/thickbox/thickbox.js", array('jquery'), '3.1-20121105', 1 );
	$scripts->add( 'jcrop', "/wp-includes/js/jcrop/jquery.Jcrop.min.js", array('jquery'), '0.9.10');
	$scripts->add( 'swfobject', "/wp-includes/js/swfobject.js", array(), '2.2-20120417');

	// common bits for both uploaders
	$max_upload_size = ( (int) ( $max_up = @ini_get('upload_max_filesize') ) < (int) ( $max_post = @ini_get('post_max_size') ) ) ? $max_up : $max_post;
	if ( empty($max_upload_size) )
		$max_upload_size = __('not configured');
	$scripts->add( 'plupload', '/wp-includes/js/plupload/plupload.js', array(), '1.5.7' );
	$scripts->add( 'plupload-html5', '/wp-includes/js/plupload/plupload.html5.js', array('plupload'), '1.5.7' );
	$scripts->add( 'plupload-flash', '/wp-includes/js/plupload/plupload.flash.js', array('plupload'), '1.5.7' );
	$scripts->add( 'plupload-silverlight', '/wp-includes/js/plupload/plupload.silverlight.js', array('plupload'), '1.5.7' );
	$scripts->add( 'plupload-html4', '/wp-includes/js/plupload/plupload.html4.js', array('plupload'), '1.5.7' );

	// cannot use the plupload.full.js, as it loads browserplus init JS from Yahoo
	$scripts->add( 'plupload-all', false, array('plupload', 'plupload-html5', 'plupload-flash', 'plupload-silverlight', 'plupload-html4'), '1.5.7' );
	$scripts->add( 'plupload-handlers', "/wp-includes/js/plupload/handlers$suffix.js", array('plupload-all', 'jquery') );
	$scripts->add( 'wp-plupload', "/wp-includes/js/plupload/wp-plupload$suffix.js", array('plupload-all', 'jquery', 'json2', 'media-models'), false, 1 );

	// keep 'swfupload' for back-compat.
	$scripts->add( 'swfupload', '/wp-includes/js/swfupload/swfupload.js', array(), '2201-20110113');
	$scripts->add( 'swfupload-swfobject', '/wp-includes/js/swfupload/plugins/swfupload.swfobject.js', array('swfupload', 'swfobject'), '2201a');
	$scripts->add( 'swfupload-queue', '/wp-includes/js/swfupload/plugins/swfupload.queue.js', array('swfupload'), '2201');
	$scripts->add( 'swfupload-speed', '/wp-includes/js/swfupload/plugins/swfupload.speed.js', array('swfupload'), '2201');
	$scripts->add( 'swfupload-all', false, array('swfupload', 'swfupload-swfobject', 'swfupload-queue'), '2201');
	$scripts->add( 'swfupload-handlers', "/wp-includes/js/swfupload/handlers$suffix.js", array('swfupload-all', 'jquery'), '2201-20110524');
	$scripts->add( 'comment-reply', "/wp-includes/js/comment-reply$suffix.js" );
	$scripts->add( 'json2', "/wp-includes/js/json2$suffix.js", array(), '2011-02-23');
	$scripts->add( 'underscore', '/wp-includes/js/underscore.min.js', array(), '1.4.4', 1 );
	$scripts->add( 'backbone', '/wp-includes/js/backbone.min.js', array('underscore','jquery'), '1.0.0', 1 );
	$scripts->add( 'wp-util', "/wp-includes/js/wp-util$suffix.js", array('underscore', 'jquery'), false, 1 );
	$scripts->add( 'wp-backbone', "/wp-includes/js/wp-backbone$suffix.js", array('backbone', 'wp-util'), false, 1 );
	$scripts->add( 'revisions', "/wp-admin/js/revisions$suffix.js", array( 'wp-backbone', 'jquery-ui-slider', 'hoverIntent' ), false, 1 );
	$scripts->add( 'imgareaselect', "/wp-includes/js/imgareaselect/jquery.imgareaselect$suffix.js", array('jquery'), '0.9.8', 1 );
	$scripts->add( 'mediaelement', "/wp-includes/js/mediaelement/mediaelement-and-player.min.js", array('jquery'), '2.13.0', 1 );
	$scripts->add( 'wp-mediaelement', "/wp-includes/js/mediaelement/wp-mediaelement.js", array('mediaelement'), false, 1 );
	$scripts->add( 'password-strength-meter', "/wp-admin/js/password-strength-meter$suffix.js", array('jquery'), false, 1 );
	$scripts->add( 'user-profile', "/wp-admin/js/user-profile$suffix.js", array( 'jquery', 'password-strength-meter' ), false, 1 );
	$scripts->add( 'user-suggest', "/wp-admin/js/user-suggest$suffix.js", array( 'jquery-ui-autocomplete' ), false, 1 );
	$scripts->add( 'admin-bar', "/wp-includes/js/admin-bar$suffix.js", array(), false, 1 );
	$scripts->add( 'wplink', "/wp-includes/js/wplink$suffix.js", array( 'jquery', 'wpdialogs' ), false, 1 );
	$scripts->add( 'wpdialogs', "/wp-includes/js/tinymce/plugins/wpdialogs/js/wpdialog$suffix.js", array( 'jquery-ui-dialog' ), false, 1 );
	$scripts->add( 'wpdialogs-popup', "/wp-includes/js/tinymce/plugins/wpdialogs/js/popup$suffix.js", array( 'wpdialogs' ), false, 1 );
	$scripts->add( 'word-count', "/wp-admin/js/word-count$suffix.js", array( 'jquery' ), false, 1 );
	$scripts->add( 'media-upload', "/wp-admin/js/media-upload$suffix.js", array( 'thickbox', 'shortcode' ), false, 1 );
	$scripts->add( 'hoverIntent', "/wp-includes/js/hoverIntent$suffix.js", array('jquery'), 'r7', 1 );
	$scripts->add( 'customize-base',     "/wp-includes/js/customize-base$suffix.js",     array( 'jquery', 'json2' ), false, 1 );
	$scripts->add( 'customize-loader',   "/wp-includes/js/customize-loader$suffix.js",   array( 'customize-base' ), false, 1 );
	$scripts->add( 'customize-preview',  "/wp-includes/js/customize-preview$suffix.js",  array( 'customize-base' ), false, 1 );
	$scripts->add( 'customize-controls', "/wp-admin/js/customize-controls$suffix.js", array( 'customize-base' ), false, 1 );
	$scripts->add( 'accordion', "/wp-admin/js/accordion$suffix.js", array( 'jquery' ), false, 1 );
	$scripts->add( 'shortcode', "/wp-includes/js/shortcode$suffix.js", array( 'underscore' ), false, 1 );
	$scripts->add( 'media-models', "/wp-includes/js/media-models$suffix.js", array( 'wp-backbone' ), false, 1 );

	// To enqueue media-views or media-editor, call wp_enqueue_media().
	// Both rely on numerous settings, styles, and templates to operate correctly.
	$scripts->add( 'media-views',  "/wp-includes/js/media-views$suffix.js",  array( 'utils', 'media-models', 'wp-plupload', 'jquery-ui-sortable' ), false, 1 );
	$scripts->add( 'media-editor', "/wp-includes/js/media-editor$suffix.js", array( 'shortcode', 'media-views' ), false, 1 );
	$scripts->add( 'mce-view', "/wp-includes/js/mce-view$suffix.js", array( 'shortcode', 'media-models' ), false, 1 );

	if ( is_admin() ) {
		$scripts->add( 'ajaxcat', "/wp-admin/js/cat$suffix.js", array( 'wp-lists' ) );
		$scripts->add_data( 'ajaxcat', 'group', 1 );
		$scripts->add( 'admin-tags', "/wp-admin/js/tags$suffix.js", array('jquery', 'wp-ajax-response'), false, 1 );
		$scripts->add( 'admin-comments', "/wp-admin/js/edit-comments$suffix.js", array('wp-lists', 'quicktags', 'jquery-query'), false, 1 );
		$scripts->add( 'xfn', "/wp-admin/js/xfn$suffix.js", array('jquery'), false, 1 );
		$scripts->add( 'postbox', "/wp-admin/js/postbox$suffix.js", array('jquery-ui-sortable'), false, 1 );
		$scripts->add( 'post', "/wp-admin/js/post$suffix.js", array('suggest', 'wp-lists', 'postbox', 'heartbeat'), false, 1 );
		$scripts->add( 'link', "/wp-admin/js/link$suffix.js", array( 'wp-lists', 'postbox' ), false, 1 );
		$scripts->add( 'comment', "/wp-admin/js/comment$suffix.js", array( 'jquery', 'postbox' ) );
		$scripts->add_data( 'comment', 'group', 1 );
		$scripts->add( 'admin-gallery', "/wp-admin/js/gallery$suffix.js", array( 'jquery-ui-sortable' ) );
		$scripts->add( 'admin-widgets', "/wp-admin/js/widgets$suffix.js", array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), false, 1 );
		$scripts->add( 'theme', "/wp-admin/js/theme$suffix.js", array( 'jquery' ), false, 1 );

		// @todo: Core no longer uses theme-preview.js. Remove?
		$scripts->add( 'theme-preview', "/wp-admin/js/theme-preview$suffix.js", array( 'thickbox', 'jquery' ), false, 1 );
		$scripts->add( 'inline-edit-post', "/wp-admin/js/inline-edit-post$suffix.js", array( 'jquery', 'suggest', 'heartbeat' ), false, 1 );
		$scripts->add( 'inline-edit-tax', "/wp-admin/js/inline-edit-tax$suffix.js", array( 'jquery' ), false, 1 );
		$scripts->add( 'plugin-install', "/wp-admin/js/plugin-install$suffix.js", array( 'jquery', 'thickbox' ), false, 1 );
		$scripts->add( 'farbtastic', '/wp-admin/js/farbtastic.js', array('jquery'), '1.2' );
		$scripts->add( 'iris', '/wp-admin/js/iris.min.js', array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), false, 1 );
		$scripts->add( 'wp-color-picker', "/wp-admin/js/color-picker$suffix.js", array( 'iris' ), false, 1 );
		$scripts->add( 'dashboard', "/wp-admin/js/dashboard$suffix.js", array( 'jquery', 'admin-comments', 'postbox' ), false, 1 );
		$scripts->add( 'list-revisions', "/wp-includes/js/wp-list-revisions$suffix.js" );
		$scripts->add( 'media', "/wp-admin/js/media$suffix.js", array( 'jquery-ui-draggable' ), false, 1 );
		$scripts->add( 'image-edit', "/wp-admin/js/image-edit$suffix.js", array('jquery', 'json2', 'imgareaselect'), false, 1 );
		$scripts->add( 'set-post-thumbnail', "/wp-admin/js/set-post-thumbnail$suffix.js", array( 'jquery' ), false, 1 );

		// Navigation Menus
		$scripts->add( 'nav-menu', "/wp-admin/js/nav-menu$suffix.js", array( 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable', 'wp-lists', 'postbox' ) );
		$scripts->add( 'custom-header', "/wp-admin/js/custom-header.js", array( 'jquery-masonry' ), false, 1 );
		$scripts->add( 'custom-background', "/wp-admin/js/custom-background$suffix.js", array( 'wp-color-picker', 'media-views' ), false, 1 );
		$scripts->add( 'media-gallery', "/wp-admin/js/media-gallery$suffix.js", array('jquery'), false, 1 );
	}
}

function wp_jit_localization() {
	global $wp_scripts;

	if ( !isset( $wp_scripts ) ) return;

	// lists all scripts including dependencies in to_do
	// does even seem to include footer-scripts
	$wp_scripts->all_deps( $wp_scripts->queue );
	foreach ($wp_scripts->to_do as $handle)
		$scripts[$handle]=1;
	
	
	if (isset($scripts['autosave']))
		wp_localize_script('autosave', 'autosaveL10n', array(
			'autosaveInterval' => AUTOSAVE_INTERVAL,
			'savingText' => __('Saving Draft&#8230;'),
			'saveAlert' => __('The changes you made will be lost if you navigate away from this page.'),
			'blog_id' => get_current_blog_id(),
		) );
	if (isset($scripts['utils']))
		wp_localize_script( 'utils', 'userSettings', array(
			'url' => (string) SITECOOKIEPATH,
			'uid' => (string) get_current_user_id(),
			'time' => (string) time(),
		) );
	if (isset($scripts['common'])) 
		wp_localize_script( 'common', 'commonL10n', array(
			'warnDelete' => __("You are about to permanently delete the selected items.\n  'Cancel' to stop, 'OK' to delete.")
		) );
	if (isset($scripts['quicktags']))
		wp_localize_script( 'quicktags', 'quicktagsL10n', array(
			'closeAllOpenTags' => esc_attr(__('Close all open tags')),
			'closeTags' => esc_attr(__('close tags')),
			'enterURL' => __('Enter the URL'),
			'enterImageURL' => __('Enter the URL of the image'),
			'enterImageDescription' => __('Enter a description of the image'),
			'fullscreen' => __('fullscreen'),
			'toggleFullscreen' => esc_attr( __('Toggle fullscreen mode') ),
			'textdirection' => esc_attr( __('text direction') ),
			'toggleTextdirection' => esc_attr( __('Toggle Editor Text Direction') )
		) );
	if (isset($scripts['wp-ajax-response']))
		wp_localize_script( 'wp-ajax-response', 'wpAjax', array(
			'noPerm' => __('You do not have permission to do that.'),
			'broken' => __('An unidentified error has occurred.')
		) );
	if (isset($scripts['wp-pointer']))
		wp_localize_script( 'wp-pointer', 'wpPointerL10n', array(
			'dismiss' => __('Dismiss'),
		) );
	if (isset($scripts['heartbeat']))
		wp_localize_script( 'heartbeat', 'heartbeatSettings',
			apply_filters( 'heartbeat_settings', array() )
		);
	if (isset($scripts['wp-auth-check']))
		wp_localize_script( 'wp-auth-check', 'authcheckL10n', array(
			'beforeunload' => __('Your session has expired. You can log in again from this page or go to the login page.'),
			'interval' => apply_filters( 'wp_auth_check_interval', 3 * MINUTE_IN_SECONDS ),
		) );
	if (isset($scripts['thickbox']))
		wp_localize_script( 'thickbox', 'thickboxL10n', array(
			'next' => __('Next &gt;'),
			'prev' => __('&lt; Prev'),
			'image' => __('Image'),
			'of' => __('of'),
			'close' => __('Close'),
			'noiframes' => __('This feature requires inline frames. You have iframes disabled or your browser does not support them.'),
			'loadingAnimation' => includes_url('js/thickbox/loadingAnimation.gif'),
			'closeImage' => includes_url('js/thickbox/tb-close.png')
		) );
	if (isset($scripts['plupload']) || isset($scripts['plupload-handlers']) || isset($scripts['swfupload'])) {
		// error message for both plupload and swfupload
		$uploader_l10n = array(
			'queue_limit_exceeded' => __('You have attempted to queue too many files.'),
			'file_exceeds_size_limit' => __('%s exceeds the maximum upload size for this site.'),
			'zero_byte_file' => __('This file is empty. Please try another.'),
			'invalid_filetype' => __('This file type is not allowed. Please try another.'),
			'not_an_image' => __('This file is not an image. Please try another.'),
			'image_memory_exceeded' => __('Memory exceeded. Please try another smaller file.'),
			'image_dimensions_exceeded' => __('This is larger than the maximum size. Please try another.'),
			'default_error' => __('An error occurred in the upload. Please try again later.'),
			'missing_upload_url' => __('There was a configuration error. Please contact the server administrator.'),
			'upload_limit_exceeded' => __('You may only upload 1 file.'),
			'http_error' => __('HTTP error.'),
			'upload_failed' => __('Upload failed.'),
			'big_upload_failed' => __('Please try uploading this file with the %1$sbrowser uploader%2$s.'),
			'big_upload_queued' => __('%s exceeds the maximum upload size for the multi-file uploader when used in your browser.'),
			'io_error' => __('IO error.'),
			'security_error' => __('Security error.'),
			'file_cancelled' => __('File canceled.'),
			'upload_stopped' => __('Upload stopped.'),
			'dismiss' => __('Dismiss'),
			'crunching' => __('Crunching&hellip;'),
			'deleted' => __('moved to the trash.'),
			'error_uploading' => __('&#8220;%s&#8221; has failed to upload.')
		);
		
		wp_localize_script( 'wp-plupload', 'pluploadL10n', $uploader_l10n );
		wp_localize_script( 'plupload-handlers', 'pluploadL10n', $uploader_l10n );
		wp_localize_script( 'swfupload-handlers', 'swfuploadL10n', $uploader_l10n );
	}
	if (isset($scripts['wp-util']))
		wp_localize_script( 'wp-util', '_wpUtilSettings', array(
			'ajax' => array(
				'url' => admin_url( 'admin-ajax.php', 'relative' ),
			),
		) );
	if (isset($scripts['mediaelement']))
		wp_localize_script( 'mediaelement', 'mejsL10n', array(
			'language' => get_bloginfo( 'language' ),
			'strings'  => array(
				'Close'               => __( 'Close' ),
				'Fullscreen'          => __( 'Fullscreen' ),
				'Download File'       => __( 'Download File' ),
				'Download Video'      => __( 'Download Video' ),
				'Play/Pause'          => __( 'Play/Pause' ),
				'Mute Toggle'         => __( 'Mute Toggle' ),
				'None'                => __( 'None' ),
				'Turn off Fullscreen' => __( 'Turn off Fullscreen' ),
				'Go Fullscreen'       => __( 'Go Fullscreen' ),
				'Unmute'              => __( 'Unmute' ),
				'Mute'                => __( 'Mute' ),
				'Captions/Subtitles'  => __( 'Captions/Subtitles' )
			),
		) );
	if (isset($scripts['wp-mediaelement']))
		wp_localize_script( 'wp-mediaelement', '_wpmejsSettings', array(
			'pluginPath' => includes_url( 'js/mediaelement/', 'relative' ),
		) );
	if (isset($scripts['password-strength-meter']))
		wp_localize_script( 'password-strength-meter', 'pwsL10n', array(
			'empty' => __('Strength indicator'),
			'short' => __('Very weak'),
			'bad' => __('Weak'),
			/* translators: password strength */
			'good' => _x('Medium', 'password strength'),
			'strong' => __('Strong'),
			'mismatch' => __('Mismatch')
		) );
	if (isset($scripts['wplink']))
		wp_localize_script( 'wplink', 'wpLinkL10n', array(
			'title' => __('Insert/edit link'),
			'update' => __('Update'),
			'save' => __('Add Link'),
			'noTitle' => __('(no title)'),
			'noMatchesFound' => __('No matches found.')
		) );
	if (isset($scripts['word-count']))
		wp_localize_script( 'word-count', 'wordCountL10n', array(
			/* translators: If your word count is based on single characters (East Asian characters),
				enter 'characters'. Otherwise, enter 'words'. Do not translate into your own language. */
			'type' => 'characters' == _x( 'words', 'word count: words or characters?' ) ? 'c' : 'w',
		) );
	if (isset($scripts['customize-controls']))
		wp_localize_script( 'customize-controls', '_wpCustomizeControlsL10n', array(
			'activate'  => __( 'Save &amp; Activate' ),
			'save'      => __( 'Save &amp; Publish' ),
			'saved'     => __( 'Saved' ),
			'cancel'    => __( 'Cancel' ),
			'close'     => __( 'Close' ),
			'cheatin'   => __( 'Cheatin&#8217; uh?' ),

			// Used for overriding the file types allowed in plupload.
			'allowedFiles' => __( 'Allowed Files' ),
		) );
	if (isset($scripts['media-models']))
		wp_localize_script( 'media-models', '_wpMediaModelsL10n', array(
			'settings' => array(
				'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
				'post' => array( 'id' => 0 ),
			),
		) );
		
	if ( is_admin() ) {
		if (isset($scripts['ajaxcat']))
			wp_localize_script( 'ajaxcat', 'catL10n', array(
				'add' => esc_attr(__('Add')),
				'how' => __('Separate multiple categories with commas.')
			) );
		if (isset($scripts['admin-tags']))
			wp_localize_script( 'admin-tags', 'tagsl10n', array(
				'noPerm' => __('You do not have permission to do that.'),
				'broken' => __('An unidentified error has occurred.')
			));
		if (isset($scripts['admin-comments']))
			wp_localize_script( 'admin-comments', 'adminCommentsL10n', array(
				'hotkeys_highlight_first' => isset($_GET['hotkeys_highlight_first']),
				'hotkeys_highlight_last' => isset($_GET['hotkeys_highlight_last']),
				'replyApprove' => __( 'Approve and Reply' ),
				'reply' => __( 'Reply' )
			) );
		if (isset($scripts['post']))
			wp_localize_script( 'post', 'postL10n', array(
				'ok' => __('OK'),
				'cancel' => __('Cancel'),
				'publishOn' => __('Publish on:'),
				'publishOnFuture' =>  __('Schedule for:'),
				'publishOnPast' => __('Published on:'),
				/* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
				'dateFormat' => __('%1$s %2$s, %3$s @ %4$s : %5$s'),
				'showcomm' => __('Show more comments'),
				'endcomm' => __('No more comments found.'),
				'publish' => __('Publish'),
				'schedule' => __('Schedule'),
				'update' => __('Update'),
				'savePending' => __('Save as Pending'),
				'saveDraft' => __('Save Draft'),
				'private' => __('Private'),
				'public' => __('Public'),
				'publicSticky' => __('Public, Sticky'),
				'password' => __('Password Protected'),
				'privatelyPublished' => __('Privately Published'),
				'published' => __('Published'),
				'comma' => _x( ',', 'tag delimiter' ),
			) );
		if (isset($scripts['comment']))
			wp_localize_script( 'comment', 'commentL10n', array(
				'submittedOn' => __('Submitted on:')
			) );
		if (isset($scripts['inline-edit-post']))
			wp_localize_script( 'inline-edit-post', 'inlineEditL10n', array(
				'error' => __('Error while saving the changes.'),
				'ntdeltitle' => __('Remove From Bulk Edit'),
				'notitle' => __('(no title)'),
				'comma' => _x( ',', 'tag delimiter' ),
			) );
		if (isset($scripts['inline-edit-tax']))
			wp_localize_script( 'inline-edit-tax', 'inlineEditL10n', array(
				'error' => __('Error while saving the changes.')
			) );
		if (isset($scripts['plugin-install']))
			wp_localize_script( 'plugin-install', 'plugininstallL10n', array(
				'plugin_information' => __('Plugin Information:'),
				'ays' => __('Are you sure you want to install this plugin?')
			) );
		if (isset($scripts['wp-color-picker']))
			wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', array(
				'clear' => __( 'Clear' ),
				'defaultString' => __( 'Default' ),
				'pick' => __( 'Select Color' ),
				'current' => __( 'Current Color' ),
			) );
		if (isset($scripts['image-edit']))
			wp_localize_script( 'image-edit', 'imageEditL10n', array(
				'error' => __( 'Could not load the preview image. Please reload the page and try again.' )
			));
		if (isset($scripts['set-post-thumbnail']))
			wp_localize_script( 'set-post-thumbnail', 'setPostThumbnailL10n', array(
				'setThumbnail' => __( 'Use as featured image' ),
				'saving' => __( 'Saving...' ),
				'error' => __( 'Could not set that as the thumbnail image. Try a different attachment.' ),
				'done' => __( 'Done' )
			) );
		if (isset($scripts['nav-menu']))
			wp_localize_script( 'nav-menu', 'navMenuL10n', array(
				'noResultsFound' => _x('No results found.', 'search results'),
				'warnDeleteMenu' => __( "You are about to permanently delete this menu. \n 'Cancel' to stop, 'OK' to delete." ),
				'saveAlert' => __('The changes you made will be lost if you navigate away from this page.')
			) );

	}
	
	$wp_scripts->reset();
}


?>