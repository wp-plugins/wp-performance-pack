jQuery(document).ready(function($){

	function lastDisplayedObject () {
		this.Label = null;
		this.Desc = null;
		this.Hint = null;
	}

	var lastL10n = new lastDisplayedObject();
	var lastDynimg = new lastDisplayedObject();
	wpppData = $.parseJSON( wpppData );

	function displaySetting ( last, idPrefix, level ) {
		if ( last.Label != null ) {
			$(last.Label).css({"font-weight": "normal", "font-size": "100%", "color": ""});
			$(last.Desc).hide();
			$(last.Hint).hide();
		}

		last.Label = $("#"+idPrefix+"-slider .ui-slider-label:eq("+level+")");
		last.Desc = $(".wppp-"+idPrefix+"-desc:eq("+level+")");
		last.Hint = $(".wppp-"+idPrefix+"-hint:eq("+level+")");

		$(last.Desc).show();
		$(last.Hint).show();
		$(last.Label).css({"font-weight": "bold", "font-size": "120%", "color": "#222"});
	}

	function setSettingInputValues ( settings, level ) {
		for ( var option in settings ) {
			$( "#wppp-settings input[name='wppp_option[" + option + "]']").val( level == 0 ? "false" : settings[option][level-1] );
		}
	}

	$( "#l10n-slider" ).slider({
		orientation: "vertical",
		value: wpppData.l10n.current,
		min: 0,
		max: 3,
		step: 1,
		slide: function( event, ui ) {
			displaySetting(lastL10n, 'l10n', ui.value);
			setSettingInputValues( wpppData.l10n.settings, ui.value);
		}
	}).slider( 'pips', {
		rest: 'label',
		labels: [ 	wpppData.labels.Off,
					wpppData.labels.Stable,
					wpppData.labels.Speed,
					wpppData.labels.Custom
				]
	});

	$( "#dynimg-slider" ).slider({
		orientation: "vertical",
		value: wpppData.dynimg.current,
		min: 0,
		max: 4,
		step: 1,
		slide: function( event, ui ) {
			displaySetting(lastDynimg, 'dynimg', ui.value);
			setSettingInputValues( wpppData.dynimg.settings, ui.value);
		}
	}).slider( 'pips', {
		rest: 'label',
		labels:	[	wpppData.labels.Off,
					wpppData.labels.Stable,
					wpppData.labels.Speed,
					wpppData.labels.Webspace,
					wpppData.labels.Custom,
				]
	});

	displaySetting( lastL10n, "l10n", wpppData.l10n.current );
	displaySetting ( lastDynimg, "dynimg", wpppData.dynimg.current );
});