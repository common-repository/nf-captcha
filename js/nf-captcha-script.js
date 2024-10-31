(function( $ ) {
    $( document ).ready( function() {
		$(document).on("click", ".nf-edit-field", function(e) {
			var settingBox = $(this);
			setTimeout(function() {
				settingBox.closest("li").find(".color-field").wpColorPicker();
			}, 500);

		});
    } );
} )( jQuery );