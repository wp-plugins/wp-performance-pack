jQuery(document).ready(function($){
	$( ".accordion" ).accordion({
		collapsible: false,
		heightStyle: "content"
	});

	var lastL10nSetting = null;
	var lastL10nDesc = null;
	var lastL10nHint = null;

	wpppData['l10nAllSettings'] = wpppData['l10nAllSettings'].split(",");
	wpppData['l10nStableSettings'] = wpppData['l10nStableSettings'].split(",");
	wpppData['l10nSpeedSettings'] = wpppData['l10nSpeedSettings'].split(",");
	wpppData['l10nCustomSettings'] = wpppData['l10nCustomSettings'].split(",");

	function displayL10nSetting ( level ) {
		if ( lastL10nSetting != null ) {
			$(lastL10nSetting).css({"font-weight": "normal", "font-size": "100%"});
			$(lastL10nDesc).hide();
			$(lastL10nHint).hide();
		}

		lastL10nSetting = $("#l10n-slider .ui-slider-label:eq("+level+")");
		lastL10nDesc = $(".wppp-l10n-desc:eq("+level+")");
		lastL10nHint = $(".wppp-l10n-hint:eq("+level+")");

		$(lastL10nDesc).show();
		$(lastL10nHint).show();
		$(lastL10nSetting).css({"font-weight": "bold", "font-size": "120%"});
	}

	function setL10nSettingInputValues ( level ) {
		if ( level == 0 ) {
			for ( i = 0; i < wpppData['l10nAllSettings'].length; i++ ) {
				$( "#wppp-settings input[name='wppp_option[" + wpppData['l10nAllSettings'][i] + "]']").val("false");
			}
		} else {
			var selSettings = [];
			switch ( level ) {
				case 1 :	selSettings = wpppData['l10nStableSettings'];
							break;
				case 2 :	selSettings = wpppData['l10nSpeedSettings'];
							break;
				case 3 :	selSettings = wpppData['l10nCustomSettings'];
							break;
			}
			for ( i = 0; i < wpppData['l10nAllSettings'].length; i++ ) {
				$( "#wppp-settings input[name='wppp_option[" + wpppData['l10nAllSettings'][i] + "]']").val(
					$.inArray( wpppData['l10nAllSettings'][i], selSettings ) >= 0 ? "true" : "false"
				);
			}
		}
	}

	$( "#l10n-slider" ).slider({
		orientation: "vertical",
		value: wpppData["l10nSetting"],
		min: 0,
		max: 3,
		step: 1,
		slide: function( event, ui ) {
			displayL10nSetting(ui.value);
			setL10nSettingInputValues(ui.value);
		}
	}).slider( 'pips', {
		rest: 'label',
		labels: [ 	wpppData['l10nLabelOff'],
					wpppData['l10nLabelStable'],
					wpppData['l10nLabelSpeed'],
					wpppData['l10nLabelCustom']
				]
	});

	displayL10nSetting( parseInt( wpppData["l10nSetting"] ) );
});