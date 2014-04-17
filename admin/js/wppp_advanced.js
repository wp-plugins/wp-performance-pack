jQuery(document).ready(function($){

	function setDynimgQuality ( value ) {
		$( "#wppp-settings input[name='wppp_option[dynimg_quality]']").val(value);
	}

	$( "#dynimg-quality-slider" ).slider({
		orientation: "horizontal",
		value: wpppData["dynimg-quality"],
		min: 10,
		max: 100,
		step: 10,
		slide: function( event, ui ) {
			setDynimgQuality(ui.value);
		}
	}).slider( 'pips', {
		rest: "label",
		suffix: "%",
	});
});