/**
 * Drift: Clearview settings screen.
 *
 * - Turns the hover colour field into core's colour picker, which takes typed
 *   or pasted hex values (e.g. #E95A3A) as well as the swatch.
 * - Links list: drag to reorder (jQuery UI Sortable), up/down buttons for
 *   keyboard users, add/remove custom links, live icon preview, and the
 *   links-in-use limit.
 *
 * Row order is the DOM order: PHP keeps array keys in the order they appear in
 * the submitted form, so moving a row is enough and nothing is re-indexed.
 */
( function ( $ ) {
	'use strict';

	var l10n = window.driftClearviewAdmin || {};

	/**
	 * Replaces %1$d / %2$d placeholders in a translated string.
	 *
	 * @param {string} text   Translated string.
	 * @param {Array}  values Replacement values.
	 * @return {string}
	 */
	function driftClearviewFormat( text, values ) {
		return String( text || '' ).replace( /%(\d)\$d/g, function ( match, n ) {
			return values[ n - 1 ];
		} );
	}

	/**
	 * Announces a change to screen reader users, if wp.a11y is loaded.
	 *
	 * @param {string} message Message.
	 */
	function driftClearviewSpeak( message ) {
		if ( window.wp && wp.a11y && wp.a11y.speak && message ) {
			wp.a11y.speak( message );
		}
	}

	$( function () {
		if ( $.fn.wpColorPicker ) {
			$( '.drift-clearview-color' ).wpColorPicker();
		}

		var $list = $( '#drift-clearview-links' );
		if ( ! $list.length ) {
			return;
		}

		var max = parseInt( l10n.max, 10 ) || 8;
		var $add = $( '#drift-clearview-add-link' );
		var $count = $( '#drift-clearview-links-count' );
		var $library = $( '#drift-clearview-icon-library' );
		var template = $( '#drift-clearview-link-template' ).html() || '';
		var nextIndex = parseInt( $list.data( 'next-index' ), 10 ) || $list.children().length;

		/**
		 * Links in use: switched-on built-ins plus every custom row.
		 *
		 * @return {number}
		 */
		function driftClearviewInUse() {
			return $list.find( '.drift-clearview-link__toggle:checked' ).length +
				$list.children( '.drift-clearview-link--custom' ).length;
		}

		/**
		 * Updates the counter and stops more links being added or switched
		 * on once the limit is reached.
		 */
		function driftClearviewRefresh() {
			var inUse = driftClearviewInUse();
			var full = inUse >= max;

			$count.text( driftClearviewFormat( l10n.count, [ inUse, max ] ) );
			$add.prop( 'disabled', full );

			$list.find( '.drift-clearview-link__toggle' ).each( function () {
				var $toggle = $( this );
				$toggle.prop( 'disabled', full && ! $toggle.prop( 'checked' ) );
			} );
		}

		/**
		 * Tells screen reader users where a row ended up.
		 *
		 * @param {jQuery} $row Moved row.
		 */
		function driftClearviewAnnounceMove( $row ) {
			driftClearviewSpeak( driftClearviewFormat( l10n.moved, [ $row.index() + 1, $list.children().length ] ) );
		}

		if ( $.fn.sortable ) {
			$list.sortable( {
				handle: '.drift-clearview-link__handle',
				axis: 'y',
				placeholder: 'drift-clearview-link-placeholder',
				forcePlaceholderSize: true,
				update: function ( event, ui ) {
					driftClearviewAnnounceMove( ui.item );
				},
			} );
		}

		$list.on( 'click.drift_clearview', '.drift-clearview-link__up, .drift-clearview-link__down', function () {
			var $button = $( this );
			var $row = $button.closest( '.drift-clearview-link' );
			var up = $button.hasClass( 'drift-clearview-link__up' );
			var $target = up ? $row.prev() : $row.next();

			if ( ! $target.length ) {
				return;
			}

			if ( up ) {
				$row.insertBefore( $target );
			} else {
				$row.insertAfter( $target );
			}

			// Moving the row in the DOM drops focus; put it back on the same button.
			$button.trigger( 'focus' );
			driftClearviewAnnounceMove( $row );
		} );

		$list.on( 'change.drift_clearview', '.drift-clearview-link__toggle', driftClearviewRefresh );

		$list.on( 'change.drift_clearview', '.drift-clearview-link__icon-select', function () {
			var $select = $( this );
			var svg = $library.find( '[data-icon="' + $select.val() + '"]' ).html() || '';
			$select.closest( '.drift-clearview-link' ).find( '.drift-clearview-link__preview' ).html( svg );
		} );

		$list.on( 'click.drift_clearview', '.drift-clearview-link__remove', function () {
			$( this ).closest( '.drift-clearview-link' ).remove();
			driftClearviewRefresh();
			$add.trigger( 'focus' );
			driftClearviewSpeak( l10n.removed );
		} );

		$add.on( 'click.drift_clearview', function () {
			if ( driftClearviewInUse() >= max || ! template ) {
				return;
			}

			var $row = $( template.replace( /__INDEX__/g, String( nextIndex ) ) );
			nextIndex++;

			$list.append( $row );
			if ( $.fn.sortable ) {
				$list.sortable( 'refresh' );
			}

			driftClearviewRefresh();
			$row.find( 'input[type="text"]' ).first().trigger( 'focus' );
			driftClearviewSpeak( l10n.added );
		} );

		driftClearviewRefresh();
	} );
}( jQuery ) );
