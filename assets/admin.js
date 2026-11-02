/**
 * Drift: Clearview settings screen. Turns the hover colour field into core's
 * colour picker, which takes typed or pasted hex values (e.g. #E95A3A) as
 * well as the swatch.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		if ( ! $.fn.wpColorPicker ) {
			return;
		}

		$( '.drift-clearview-color' ).wpColorPicker();
	} );
}( jQuery ) );
