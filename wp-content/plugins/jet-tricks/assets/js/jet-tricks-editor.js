(function( $ ) {

	'use strict';

	var JetTricksEditor = {

		scrollRevealObserver: null,
		scrollRevealTimers: null,
		scrollRevealPanelTimer: null,

		init: function() {
			window.elementor.on( 'preview:loaded', function() {
				elementor.$preview[0].contentWindow.JetTricksEditor = JetTricksEditor;

				JetTricksEditor.onPreviewLoaded();
			} );
		},

		onPreviewLoaded: function() {
			var previewWindow = $( '#elementor-preview-iframe' )[0].contentWindow,
				elementorFrontend = previewWindow.elementorFrontend;

			elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', function( $scope ) {
				$scope.find( '.jet-tricks-edit-template-link' ).on( 'click', function( event ) {
					window.open( $( this ).attr( 'href' ) );
				} );
			} );

			JetTricksEditor.initScrollRevealPreviewObserver( previewWindow );
			JetTricksEditor.bindScrollRevealPanelPreview( previewWindow );
		},

		initScrollRevealPreviewObserver: function( previewWindow ) {
			var previewDocument = previewWindow.document;

			if ( ! previewWindow.JetTricksScrollRevealTools || ! previewDocument || ! previewDocument.body ) {
				return;
			}

			if ( JetTricksEditor.scrollRevealObserver ) {
				JetTricksEditor.scrollRevealObserver.disconnect();
			}

			JetTricksEditor.scrollRevealTimers = new WeakMap();

			function scheduleReplay( node ) {
				var $node = $( node ),
					$targets = $node.hasClass( 'jet-scroll-reveal-widget' )
						? $node
						: $node.find( '.jet-scroll-reveal-widget' );

				if ( ! $targets.length ) {
					$targets = $node.closest( '.jet-scroll-reveal-widget' );
				}

				$targets.each( function() {
					var target = this,
						settings = $( target ).data( 'jet-tricks-settings' ) || {};

					if ( settings.scrollReveal !== 'true' && settings.scrollReveal !== true ) {
						return;
					}

					if ( JetTricksEditor.scrollRevealTimers.has( target ) ) {
						clearTimeout( JetTricksEditor.scrollRevealTimers.get( target ) );
					}

					JetTricksEditor.scrollRevealTimers.set( target, setTimeout( function() {
						previewWindow.JetTricksScrollRevealTools.replay( $( target ), settings, { force: true } );
					}, 220 ) );
				} );
			}

			JetTricksEditor.scrollRevealObserver = new previewWindow.MutationObserver( function( mutations ) {
				mutations.forEach( function( mutation ) {
					if ( mutation.target ) {
						scheduleReplay( mutation.target );
					}

					if ( mutation.addedNodes && mutation.addedNodes.length ) {
						mutation.addedNodes.forEach( function( node ) {
							if ( node && 1 === node.nodeType ) {
								scheduleReplay( node );
							}
						} );
					}
				} );
			} );

			JetTricksEditor.scrollRevealObserver.observe( previewDocument.body, {
				childList: true,
				subtree: true,
				attributes: true,
				attributeFilter: [ 'class', 'style', 'data-jet-tricks-settings' ]
			} );
		},

		replayAllScrollRevealWidgets: function( previewWindow ) {
			if ( ! previewWindow || ! previewWindow.JetTricksScrollRevealTools ) {
				return;
			}

			$( previewWindow.document ).find( '.jet-scroll-reveal-widget' ).each( function() {
				var $target = $( this ),
					settings = $target.data( 'jet-tricks-settings' ) || {};

				if ( settings.scrollReveal !== 'true' && settings.scrollReveal !== true ) {
					return;
				}

				previewWindow.JetTricksScrollRevealTools.replay( $target, settings, { force: true } );
			} );
		},

		bindScrollRevealPanelPreview: function( previewWindow ) {
			var $doc = $( document );

			$doc.off( '.jetTricksScrollRevealPreview' );

			$doc.on(
				'input.jetTricksScrollRevealPreview change.jetTricksScrollRevealPreview keyup.jetTricksScrollRevealPreview',
				'[data-setting*="scroll_reveal"], [data-setting*="scrollReveal"], [data-setting*="jetTricksScrollReveal"] :input, [data-setting*="scroll_reveal"] :input, [data-setting*="scrollReveal"] :input, [data-setting*="jetTricksScrollReveal"]',
				function() {
					clearTimeout( JetTricksEditor.scrollRevealPanelTimer );

					JetTricksEditor.scrollRevealPanelTimer = setTimeout( function() {
						JetTricksEditor.replayAllScrollRevealWidgets( previewWindow );
					}, 220 );
				}
			);
		}
	};

	$( window ).on( 'elementor:init', JetTricksEditor.init );

	window.JetTricksEditor = JetTricksEditor;

}( jQuery ));
