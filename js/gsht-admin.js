(function (window, $)
{
	$(document).ready(function ()
	{
		$(document).on('wp-before-tinymce-init.gsht-media_button', function (event, init)
			{
				$media_buttons = $(init.selector).closest('.wp-editor-wrap').find('.wp-media-buttons');
				if (!$media_buttons.find('.gsht_media_link').length)
				{
					$media_buttons.append('<a href="#TB_inline?width=680&height=500&inlineId=wp_gsht_doin_div_shortcode" class="thickbox button gsht_media_link" title="Google Spreadsheet to Accordion"><span></span> GSAccordion</a>');

				}
			});
	}); // end ready
})(window, jQuery);