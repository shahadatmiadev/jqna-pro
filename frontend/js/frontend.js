/**
 * JQNA Pro Frontend Script
 * Accordion + AJAX pagination + category filter + question submission.
 *
 * @package JQNA_Pro
 */
/* global jqnaPro, jQuery */
( function ( $, cfg ) {
	'use strict';

	// ----------------------------------------------------------------
	// Accordion
	// ----------------------------------------------------------------
	function initAccordion( $scope ) {
		$scope.find( '.jqna-accordion-toggle' ).off( 'click.jqna' ).on( 'click.jqna', function () {
			var $btn   = $( this );
			var $panel = $( '#' + $btn.attr( 'aria-controls' ) );
			var open   = $btn.attr( 'aria-expanded' ) === 'true';

			// Close all others.
			$scope.find( '.jqna-accordion-toggle[aria-expanded="true"]' ).each( function () {
				$( this ).attr( 'aria-expanded', 'false' );
				$( '#' + $( this ).attr( 'aria-controls' ) ).attr( 'hidden', '' );
			} );

			if ( ! open ) {
				$btn.attr( 'aria-expanded', 'true' );
				$panel.removeAttr( 'hidden' );
			}
		} );
	}

	// ----------------------------------------------------------------
	// AJAX load questions
	// ----------------------------------------------------------------
	function loadQuestions( categoryId, paged ) {
		var $list  = $( '#jqna-accordion-list' );
		var $pager = $( '#jqna-pagination' );

		$list.html( '<p class="jqna-pagination-loader">' + cfg.i18n.loading + '</p>' );

		$.ajax( {
			url:  cfg.ajaxUrl,
			type: 'POST',
			data: {
				action:      'jqna_load_questions',
				nonce:       cfg.nonce,
				category_id: categoryId,
				paged:       paged
			},
			success: function ( res ) {
				if ( ! res.success ) {
					$list.html( '<p class="jqna-no-results">' + cfg.i18n.error + '</p>' );
					return;
				}

				var data = res.data;
				$list.html( data.html );
				initAccordion( $list );

				// Update pagination state.
				var total   = parseInt( data.total_pages, 10 ) || 1;
				var current = parseInt( data.current, 10 )     || 1;

				$pager.attr( 'data-total',    total );
				$pager.attr( 'data-current',  current );
				$pager.attr( 'data-category', categoryId );

				$( '#jqna-cur-page' ).text( current );
				$( '#jqna-total-pages' ).text( total );

				if ( total > 1 ) {
					$pager.show();
				} else {
					$pager.hide();
				}

				updatePagerButtons( current, total );
			},
			error: function () {
				$list.html( '<p class="jqna-no-results">' + cfg.i18n.error + '</p>' );
			}
		} );
	}

	function updatePagerButtons( current, total ) {
		var $pager = $( '#jqna-pagination' );
		$pager.find( '[data-action="prev"]' ).prop( 'disabled', current <= 1 );
		$pager.find( '[data-action="next"]' ).prop( 'disabled', current >= total );
	}

	// ----------------------------------------------------------------
	// Category sidebar
	// ----------------------------------------------------------------
	$( document ).on( 'click', '.jqna-cat-btn', function () {
		var $btn    = $( this );
		var catId   = parseInt( $btn.data( 'cat' ), 10 ) || 0;

		$( '.jqna-cat-btn' ).removeClass( 'active' );
		$btn.addClass( 'active' );

		// Show / hide Reset button.
		if ( catId !== 0 ) {
			$( '#jqna-reset' ).show();
		} else {
			$( '#jqna-reset' ).hide();
		}

		loadQuestions( catId, 1 );
	} );

	// Reset button.
	$( document ).on( 'click', '#jqna-reset', function () {
		$( '.jqna-cat-btn' ).removeClass( 'active' );
		$( '.jqna-cat-btn[data-cat="0"]' ).addClass( 'active' );
		$( '#jqna-reset' ).hide();
		loadQuestions( 0, 1 );
	} );

	// ----------------------------------------------------------------
	// Pagination
	// ----------------------------------------------------------------
	$( document ).on( 'click', '#jqna-pagination .jqna-page-btn', function () {
		var $pager  = $( '#jqna-pagination' );
		var current = parseInt( $pager.attr( 'data-current' ), 10 ) || 1;
		var total   = parseInt( $pager.attr( 'data-total' ),   10 ) || 1;
		var catId   = parseInt( $pager.attr( 'data-category' ),10 ) || 0;
		var action  = $( this ).data( 'action' );

		if ( action === 'prev' && current > 1 ) {
			loadQuestions( catId, current - 1 );
		} else if ( action === 'next' && current < total ) {
			loadQuestions( catId, current + 1 );
		}

		// Scroll to list top.
		$( 'html, body' ).animate(
			{ scrollTop: $( '#jqna-accordion-list' ).offset().top - 60 },
			300
		);
	} );

	// ----------------------------------------------------------------
	// Question submission
	// ----------------------------------------------------------------
	$( document ).on( 'submit', '#jqna-submit-form', function ( e ) {
		e.preventDefault();

		var $form  = $( this );
		var $btn   = $form.find( 'button[type="submit"]' );
		var $msg   = $( '#jqna-submit-msg' );
		var origTxt = $btn.text();

		$btn.prop( 'disabled', true ).text( cfg.i18n.loading );
		$msg.html( '' );

		$.ajax( {
			url:  cfg.ajaxUrl,
			type: 'POST',
			data: {
				action: 'jqna_submit_question',
				nonce:  cfg.nonce,
				title:  $form.find( '[name="title"]' ).val(),
				cat_id: $form.find( '[name="cat_id"]' ).val() || 0
			},
			success: function ( res ) {
				if ( res.success ) {
					$msg.html( '<span class="jqna-alert jqna-alert-success">' + res.data + '</span>' );
					$form[ 0 ].reset();
				} else {
					$msg.html( '<span class="jqna-alert jqna-alert-error">' + res.data + '</span>' );
				}
			},
			error: function () {
				$msg.html( '<span class="jqna-alert jqna-alert-error">' + cfg.i18n.error + '</span>' );
			},
			complete: function () {
				$btn.prop( 'disabled', false ).text( origTxt );
			}
		} );
	} );

	// ----------------------------------------------------------------
	// Init on DOM ready
	// ----------------------------------------------------------------
	$( function () {
		initAccordion( $( '#jqna-accordion-list' ) );
	} );

}( jQuery, jqnaPro ) );
