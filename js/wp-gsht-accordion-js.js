window.onload = function () {
jQuery(document).ready(function($) {
	$(".gsht-accordion").accordion({heightStyle: "content", autoHeight: false});
	$('.gsht-accordion .ui-accordion-header').click(function() {
      $('.gsht-accordion .ui-accordion-content' ).resize();
    });
});
}(jQuery);
