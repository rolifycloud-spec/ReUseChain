( function( $, elementor ) {

	'use strict';

	var JetTricks = {

		init: function() {
			var frontend = window.elementorFrontend || elementor;

			if ( ! frontend || ! frontend.hooks ) {
				return;
			}

			frontend.hooks.addAction( 'frontend/element_ready/section', JetTricks.elementorSection );
			frontend.hooks.addAction( 'frontend/element_ready/section', JetTricks.elementorColumn );
			frontend.hooks.addAction( 'frontend/element_ready/section', JetTricks.elementorWidget );
			frontend.hooks.addAction( 'frontend/element_ready/container', JetTricks.elementorSection );
			frontend.hooks.addAction( 'frontend/element_ready/container', JetTricks.elementorColumn );
			frontend.hooks.addAction( 'frontend/element_ready/column', JetTricks.elementorColumn );
			frontend.hooks.addAction( 'frontend/element_ready/column', JetTricks.elementorWidget );
			frontend.hooks.addAction( 'frontend/element_ready/widget', JetTricks.elementorWidget );
			frontend.hooks.addAction( 'frontend/element_ready/container', JetTricks.elementorWidget );

			var widgets = {
				'jet-view-more.default' : JetTricks.widgetViewMore,
				'jet-unfold.default' : JetTricks.widgetUnfold,
				'jet-hotspots.default' : JetTricks.widgetHotspots
			};

			$.each( widgets, function( widget, callback ) {
				frontend.hooks.addAction( 'frontend/element_ready/' + widget, callback );
			});

			// Re-init widgets in nested tabs
			if ( frontend.elements && frontend.elements.$window ) {
				frontend.elements.$window.on(
					'elementor/nested-tabs/activate',
					( event, content ) => {

					const $content = $( content );

					var $button  = $content.find( '.jet-unfold__button' );

					$button.off( 'click.jetUnfold' );

					JetTricks.initWidgetsHandlers( $content );
					JetTricks.elementorSection( $content );

				}
			);
			}

			// Add an action when the Swiper Loop Carousel widget is ready on the frontend
			var loopCarouselTypes = [
				'loop-carousel.post',
				'loop-carousel.product',
				'loop-carousel.post_taxonomy',
				'loop-carousel.product_taxonomy'
			];
			
			loopCarouselTypes.forEach( function( carouselType ) {
				frontend.hooks.addAction( 'frontend/element_ready/' + carouselType, function( $scope, $ ) {
					
					$( window ).on( 'load', function() {
						var loopCarousel = $scope.find( '.swiper' ),
							swiperInstance = loopCarousel.data( 'swiper' ),
							$button = $scope.find( '.jet-unfold__button' );
						
						if ( swiperInstance && $button ) {
				
							$button.off( 'click.jetUnfold' );
							JetTricks.initLoopCarouselHandlers( $scope );
				
							swiperInstance.on( 'slideChange', function() {
								$button.off( 'click.jetUnfold' );
								JetTricks.initLoopCarouselHandlers( $scope );
							});
						}
					});
				});
			});
		},

		getDeviceMode: function() {
			if ( window.elementorFrontend && typeof window.elementorFrontend.getCurrentDeviceMode === 'function' ) {
				return window.elementorFrontend.getCurrentDeviceMode();
			}
			var w = window.innerWidth || document.documentElement.clientWidth || 0;
			if ( w < 768 ) {
				return 'mobile';
			}
			if ( w < 1025 ) {
				return 'tablet';
			}
			return 'desktop';
			console.log( 'JetTricks.getDeviceMode', w );
		},

		initLoopCarouselHandlers: function( $selector ) {
			$selector.find( '.elementor-widget-jet-unfold' ).each( function() {

				var $this  = $( this ),

				elementType = $this.data( 'element_type' );

				if ( !elementType ) {
					return;
				}

				if ( 'widget' === elementType ) {
					elementType = $this.data( 'widget_type' );
					window.elementorFrontend.hooks.doAction( 'frontend/element_ready/widget', $this, $ );
				}

				window.elementorFrontend.hooks.doAction( 'frontend/element_ready/global', $this, $ );
				window.elementorFrontend.hooks.doAction( 'frontend/element_ready/' + elementType, $this, $ );


			} );
		},

		initWidgetsHandlers: function( $selector ) {

				$selector.find( '[data-element_type]' ).each( function() {

					var excludeWidgets = [
						'jet-woo-product-gallery-slider.default',
						'accordion.default',
						'jet-form-builder-form.default',
						'nav-menu.default'
					];

					var $this  = $( this ),

					elementType = $this.data( 'element_type' );

					if ( !elementType ) {
						return;
					}

					if ( 'widget' === elementType ) {

						elementType = $this.data( 'widget_type' );
						
						if ( excludeWidgets.includes( elementType ) ) {
							return;
						}
						
						window.elementorFrontend.hooks.doAction( 'frontend/element_ready/widget', $this, $ );
					}

					window.elementorFrontend.hooks.doAction( 'frontend/element_ready/global', $this, $ );
					window.elementorFrontend.hooks.doAction( 'frontend/element_ready/' + elementType, $this, $ );


				} );
		},

		loadParticles: function( $scope, instanceId, jsonConfig ) {
			$scope.prepend( '<div id="' + instanceId + '" class="jet-tricks-particles-section__instance"></div>' );

			if ( typeof tsParticles !== 'undefined' && tsParticles.load ) {
				if ( tsParticles.version && tsParticles.version.startsWith( '3.' ) ) {
					tsParticles.load( { id: instanceId, options: jsonConfig } );
				} else {
					tsParticles.load( instanceId, jsonConfig );
				}
			}
		},

		elementorSection: function( $scope ) {
			var $target        = $scope,
				sectionId      = $scope.data( 'id' ),
				editMode       = Boolean( elementor && elementor.isEditMode() ),
				jetListing     = $target.parents( '.elementor-widget-jet-listing-grid' ).data( 'id' ),
				settings       = {};

			if ( window.JetTricksSettings && window.JetTricksSettings.elements_data.sections.hasOwnProperty( sectionId ) ) {
				settings = window.JetTricksSettings.elements_data.sections[ sectionId ];
			}

			if ( editMode ) {
				settings = JetTricks.sectionEditorSettings( $scope );
			}

			if ( ! settings ) {
				return false;
			}

			if ( jQuery.isEmptyObject( settings ) ) {
				return false;
			}

			if ( 'false' === settings.particles || '' === settings.particles_json ) {
				return false;
			}

			// Compatibility jet tricks particles with jet listing

			if ( jetListing && $target.parent().data( 'elementor-type' ) === 'jet-listing-items' ){
				sectionId += jetListing + $target.parents( '.jet-listing-grid__item' ).data( 'post-id' );
			}

			JetTricks.loadParticles( $scope, 'jet-tricks-particles-instance-' + sectionId, JSON.parse( settings.particles_json ) );

		},

		elementorColumn: function( $scope ) {
			var $target  = $scope,
				$parentSection = $scope.closest( '.elementor-section' ),
				isLegacyModeActive = !!$target.find( '> .elementor-column-wrap' ).length,
				$window  = $( window ),
				columnId = $target.data( 'id' ),
				editMode = Boolean( elementor && elementor.isEditMode() ),
				settings = {},
				stickyInstance = null,
				stickyInstanceOptions = {
					topSpacing: 50,
					bottomSpacing: 50,
					containerSelector: isLegacyModeActive ? '.elementor-row' : '.elementor-container, .e-con-inner',
					innerWrapperSelector: isLegacyModeActive ? '.elementor-column-wrap' : '.elementor-widget-wrap',
				},
				$observerTarget = $target.find('.elementor-element');

			if ( ! editMode ) {
				settings = $target.data( 'jet-settings' );

				if ( $target.hasClass( 'jet-sticky-column' ) ) {
					if ( -1 !== settings['stickyOn'].indexOf( JetTricks.getDeviceMode() ) ) {
						$target.each( function() {
							var $this  = $( this ),
								elementType = $this.data( 'element_type' );
		
							if ( settings['behavior'] === 'fixed' ) {
								initFixedSticky( $this, settings );
							} else if ( elementType !== 'container' && elementType !== 'section' ) {
								initSidebarSticky( $this, settings, stickyInstanceOptions );
							} else if ( settings['behavior'] === 'scroll_until_end' ) {
								initScrollUntilEndSticky( $this, settings );
							} else {
								initDefaultSticky( $this, settings );
							}
						});
					}
				}
			}

			function initFixedSticky($element, settings) {
				var offsetTop = parseInt(settings['topSpacing']) || 0;
				var bottomSpacing = parseInt(settings['bottomSpacing']) || 0;
				var $window = $(window);
				var elementId = $element.data('id');
				var originalOffsetTop = $element.offset().top;
				var originalHeight = $element.outerHeight();
				var scrollVisibility = settings['scrollVisibility'] || 'both';
				var scrollOffset = parseInt( settings['scrollOffset'], 10 );
				var lastScrollTop = $window.scrollTop();
				var lastDirection = null;

				if ( isNaN( scrollOffset ) || scrollOffset < 0 ) {
					scrollOffset = 12;
				}
			
				var $allStickyElements = $('.jet-sticky-column').filter(function() {
					var $this = $(this);
					var elementSettings = $this.data('jet-settings');

					return elementSettings && elementSettings.stickyOn.indexOf( JetTricks.getDeviceMode() ) !== -1;
				});

				var currentIndex = $allStickyElements.index($element);
				var $nextSticky = currentIndex + 1 < $allStickyElements.length ? $allStickyElements.eq(currentIndex + 1) : null;
				var $stopper = null;
				if ($nextSticky) {
					$stopper = $nextSticky.closest('.elementor-top-section, .e-parent');
					if (!$stopper.length) {
						$stopper = $nextSticky;
					}
				}
			
				const $placeholder = $('<div></div>')
					.addClass('jet-sticky-placeholder')
					.css({
						display: 'none',
						height: originalHeight,
						width: $element.outerWidth(),
						visibility: 'hidden'
					});
			
				$element.before($placeholder);				

				$element.css({
					'--jet-tricks-sticky-offset': offsetTop + 'px',
				});

				function withTransitionDisabled( callback ) {
					$element.addClass( 'jet-sticky-container--no-transition' );
					callback();

					requestAnimationFrame( function() {
						requestAnimationFrame( function() {
							$element.removeClass( 'jet-sticky-container--no-transition' );
						} );
					} );
				}
			
				function enableSticky() {
					$placeholder.show();
					$element.addClass('jet-sticky-container--stuck');
			
					var stopperTop = $stopper?.offset()?.top;
					var stopPoint = stopperTop ? (stopperTop - $element.outerHeight() - offsetTop - bottomSpacing) : null;
					var diff = 0;
			
					if (stopPoint && stopPoint < $window.scrollTop()) {
						diff = (stopPoint - $window.scrollTop());
					}
			
					$element.css({
						position: 'fixed',
						top: diff + 'px',
						left: $placeholder.offset().left + 'px',
						width: $placeholder.outerWidth() + 'px',
						zIndex: settings['zIndex'] || ''
					});
				}
			
				function disableSticky() {
					$placeholder.hide();
					$element.removeClass('jet-sticky-container--stuck jet-sticky-container--hidden jet-sticky-container--scrolled');
			
					$element.css({
						position: '',
						top: '',
						left: '',
						width: '',
						zIndex: ''
					});
				}

				function updateStickyState( direction, isScrolled ) {
					var shouldHide = false;

					if ( ! isScrolled ) {
						withTransitionDisabled( function() {
							$element.removeClass( 'jet-sticky-container--hidden jet-sticky-container--scrolled' );
						} );
						return;
					}

					if ( ! direction ) {
						$element.removeClass( 'jet-sticky-container--hidden' );
						$element.toggleClass( 'jet-sticky-container--scrolled', !! isScrolled );
						return;
					}

					if ( scrollVisibility === 'up' ) {
						shouldHide = direction === 'down';
					} else if ( scrollVisibility === 'down' ) {
						shouldHide = direction === 'up';
					}

					$element.toggleClass( 'jet-sticky-container--hidden', shouldHide );
					$element.toggleClass( 'jet-sticky-container--scrolled', !! isScrolled );
				}
			
				function onScroll() {
					var scrollTop = $window.scrollTop();
					var isScrolled = scrollTop > scrollOffset;

					if ( Math.abs( scrollTop - lastScrollTop ) >= scrollOffset ) {
						lastDirection = scrollTop > lastScrollTop ? 'down' : 'up';
						lastScrollTop = scrollTop;
					}

					if (scrollTop >= originalOffsetTop) {
						enableSticky();
						updateStickyState( lastDirection, isScrolled );
					} else {
						disableSticky();
						lastScrollTop = scrollTop;
					}
				}
			
				function onResize() {
					originalOffsetTop = $placeholder.offset().top;
					originalHeight = $element.outerHeight();
			
					$placeholder.css({
						height: originalHeight,
						width: $element.outerWidth()
					});
			
					onScroll();
				}
			
				let ticking = false;
				$window.on('scroll.jetStickyHeader-' + elementId, function() {
					if (!ticking) {
						requestAnimationFrame(function() {
							onScroll();
							ticking = false;
						});
						ticking = true;
					}
				});
			
				$window.on('resize.jetStickyHeader-' + elementId, JetTricksTools.debounce(100, onResize));
				onScroll();
			
				$window.on('resize.jetStickyHeader-' + elementId, JetTricksTools.debounce(100, function() {
					if (-1 === settings['stickyOn'].indexOf( JetTricks.getDeviceMode() )) {
						cleanupSticky($element, $placeholder, elementId);
					}
				}));
			}
			
			function cleanupSticky( $element, $placeholder, elementId ) {
				$placeholder.remove();
				$element.css({
					position: '',
					top: '',
					left: '',
					width: '',
					zIndex: '',
					transition: '',
					willChange: '',
					'--jet-tricks-sticky-offset': ''
				});
				$element.removeClass('jet-sticky-container--stuck jet-sticky-container--hidden jet-sticky-container--scrolled');
				$window.off('scroll.jetStickyHeader-' + elementId);
				$window.off('resize.jetStickyHeader-' + elementId);
			}

			function initSidebarSticky( $element, settings, options ) {
				options.topSpacing = settings['topSpacing'];
				options.bottomSpacing = settings['bottomSpacing'];

				imagesLoaded( $parentSection, function() {
					$target.data( 'stickyColumnInit', true );
					stickyInstance = new StickySidebar( $target[0], options );
				});

				var targetMutation = $target[0],
					config = { attributes: true, childList: true, subtree: true };

				var observer = new MutationObserver( function( mutations ) {
					for( var mutation of mutations ) {
						if ( 'attributes' === mutation.type && mutation.attributeName !== 'style' ) {
							$target[0].style.height = 'auto';
						}
					}
				});

				observer.observe( targetMutation, config );

				$window.on( 'resize.JetTricksStickyColumn orientationchange.JetTricksStickyColumn', 
					JetTricksTools.debounce( 50, resizeDebounce ) );

				var observer = new MutationObserver( function( mutations ) {
					if ( stickyInstance ) {
						mutations.forEach( function(mutation){
							if (mutation.attributeName === 'class') {
								setTimeout( function() {
									stickyInstance.destroy();
									stickyInstance = new StickySidebar( $target[0], options );
								}, 100 );
							}
						});
					}
				});

				$observerTarget.each( function(){
					observer.observe( $( this )[0], {
						attributes: true
					});
				});
			}

			function initScrollUntilEndSticky( $element, settings ) {
				const stickyHeight = $element.outerHeight();
				const stickyContentBottom = $element.offset().top + stickyHeight;
				const stickyViewportOffset = $window.height() - stickyHeight - settings['bottomSpacing'];

				$('body').addClass('jet-sticky-container');

				$window.on( 'scroll.jetSticky', function () {
					const scrollPosition = $window.scrollTop();

					if ( scrollPosition + $window.height() >= stickyContentBottom ) {
						$element.css({
							position: 'sticky',
							top: stickyViewportOffset + 'px',
							bottom: 'auto',
							left: 'auto',
							zIndex: settings['zIndex'],
						});
					}
				});

				$observerTarget.on( 'destroy.jetSticky', function () {
					$window.off( 'scroll.jetSticky' );
					$('body').removeClass('jet-sticky-container');
				});
			}

			function initDefaultSticky( $element, settings ) {
				$('body').addClass('jet-sticky-container');
				$element.addClass( 'jet-sticky-container-sticky' );
				$element.css({ 
					'top': settings['topSpacing'], 
					'bottom': settings['bottomSpacing']
				});
			}

			function resizeDebounce() {
				var currentDeviceMode = JetTricks.getDeviceMode(),
					availableDevices  = settings['stickyOn'] || [],
					isInit            = $target.data( 'stickyColumnInit' );

				if ( -1 !== availableDevices.indexOf( currentDeviceMode ) ) {
					if ( ! isInit ) {
						$target.data( 'stickyColumnInit', true );
						stickyInstance = new StickySidebar( $target[0], stickyInstanceOptions );
						stickyInstance.updateSticky();
					}
				} else {
					$target.data( 'stickyColumnInit', false );
					stickyInstance.destroy();
				}
			}
		},

		elementorWidget: function( $scope ) {
			var parallaxInstance = null,
				satelliteInstance = null,
				tooltipInstance = null,
				scrollRevealInstance = null;

			parallaxInstance = new jetWidgetParallax( $scope );
			parallaxInstance.init();

			satelliteInstance = new jetWidgetSatellite( $scope );
			satelliteInstance.init();

			tooltipInstance = new jetWidgetTooltip( $scope );
			tooltipInstance.init();

			scrollRevealInstance = new jetWidgetScrollReveal( $scope );
			scrollRevealInstance.init();

		},

		getElementorElementSettings: function( $scope ) {

			if ( window.elementorFrontend && window.elementorFrontend.isEditMode() && $scope.hasClass( 'elementor-element-edit-mode' ) ) {
				return JetTricks.getEditorElementSettings( $scope );
			}

			return $scope.data( 'settings' ) || {};
		},

		getEditorElementSettings: function( $scope ) {
			var modelCID = $scope.data( 'model-cid' ),
				elementData;

			if ( ! modelCID ) {
				return {};
			}

			if ( ! elementor.hasOwnProperty( 'config' ) ) {
				return {};
			}

			if ( ! elementor.config.hasOwnProperty( 'elements' ) ) {
				return {};
			}

			if ( ! elementor.config.elements.hasOwnProperty( 'data' ) ) {
				return {};
			}

			elementData = elementor.config.elements.data[ modelCID ];

			if ( ! elementData ) {
				return {};
			}

			return elementData.toJSON();
		},

		widgetViewMore: function( $scope ) {
			var $target         = $scope.find( '.jet-view-more' ),
				instance        = null,
				settings        = $target.data( 'settings' );

			instance = new jetViewMore( $target, settings );
			instance.init();
		},

		widgetUnfold: function( $scope ) {
			var $target                = $scope.find( '.jet-unfold' ),
				$button                = $( '.jet-unfold__button', $target ),
				$mask                  = $( '.jet-unfold__mask', $target ),
				$content               = $( '.jet-unfold__content', $target ),
				$contentInner          = $( '.jet-unfold__content-inner', $target),
				$trigger               = $( '.jet-unfold__trigger', $target),
				$separator             = $( '.jet-unfold__separator', $target ),
				baseSettings           = $target.data( 'settings' ) || {},
				elemSettings           = ( typeof elementor !== 'undefined' && JetTricks.getElementorElementSettings ) ? ( JetTricks.getElementorElementSettings( $scope ) || {} ) : {},
				settings               = $.extend( {}, baseSettings, elemSettings ),
				maskBreakpointsHeights = [],
				prevBreakpoint         = '',
				unfoldDuration         = settings['unfoldDuration'] || settings['unfold_duration'],
				foldDuration           = settings['foldDuration'] || settings['fold_duration'],
				unfoldEasing           = settings['unfoldEasing'] || settings['unfold_easing'],
				foldEasing             = settings['foldEasing'] || settings['fold_easing'],
				maskHeightAdv          = 20,
				heightCalc             = '',
				autoHide               = settings['autoHide'] || false,
				autoHideTime           = settings['autoHideTime'] && 0 != settings['autoHideTime']['size'] ? settings['autoHideTime']['size'] : 5,
				hideOutsideClick       = settings['hideOutsideClick'] || false,
				heightControlType      = settings['heightControlType'] || 'height',
				wordCount              = settings['wordCount'] || 20,
				autoHideTrigger,
				activeBreakpoints      = ( window.elementor && window.elementor.config && window.elementor.config.responsive && window.elementor.config.responsive.activeBreakpoints ) ? window.elementor.config.responsive.activeBreakpoints : {},
				initialLoaded          = false,
				isTrue                 = function( v ) { return v === true || v === 'true'; };

			function updateMaskGradientClass() {
				if (settings.separatorType === 'gradient') {
					if ($target.hasClass('jet-unfold-state') || $trigger.is(':hidden')) {
						$mask.removeClass('jet-unfold__mask-gradient');
					} else {
						$mask.addClass('jet-unfold__mask-gradient');
					}
				}
			}

			function calculateHeightByWordCount() {
				var text = $contentInner.text().trim();
				if (!text) {
					return 0;
				}

				var words = text.split(/\s+/);
				var wordsToShow = Math.min(getDeviceWordCount(), words.length);
				if (wordsToShow >= words.length) {
					return $contentInner.outerHeight();
				}

				var visibleText = words.slice(0, wordsToShow).join(' ');
				var range = document.createRange();
				var walker = document.createTreeWalker($contentInner[0], NodeFilter.SHOW_TEXT, null, false);
				var endNode = null;
				var endOffset = 0;
				var wordsCounted = 0;

				while (walker.nextNode()) {
					var node = walker.currentNode;
					var nodeText = node.textContent;
					var match;
					var wordPattern = /\S+/g;

					while ((match = wordPattern.exec(nodeText)) !== null) {
						wordsCounted++;

						if (wordsCounted === wordsToShow) {
							endOffset = match.index + match[0].length;
							endNode = node;
							break;
						}
					}

					if (endNode) {
						break;
					}
				}

				if (!endNode) {
					return $contentInner.outerHeight();
				}

				try {
					range.selectNodeContents($contentInner[0]);
					range.setEnd(endNode, endOffset);
					var rect = range.getBoundingClientRect();
					var containerRect = $contentInner[0].getBoundingClientRect();
					return Math.ceil(rect.bottom - containerRect.top);
				} catch (e) {
					var $temp = $contentInner.clone().empty().css({ position: 'absolute', visibility: 'hidden', width: $contentInner.outerWidth() }).html('<p>' + visibleText + '</p>');
					$contentInner.after($temp);
					var h = $temp.outerHeight();
					$temp.remove();
					return h;
				}
			}

			maskBreakpointsHeights['desktop']    = [];
			maskBreakpointsHeights['widescreen'] = [];

			maskBreakpointsHeights['desktop']['maskHeight'] = (settings['mask_height'] && settings['mask_height']['size'] && '' != settings['mask_height']['size']) ? settings['mask_height']['size'] : 50;

			prevBreakpoint = 'desktop';

			Object.keys(  activeBreakpoints ).reverse().forEach(  function( breakpointName ) {

				if ( 'widescreen' === breakpointName ) {
					maskBreakpointsHeights['widescreen']['maskHeight'] = (settings['mask_height_widescreen'] && settings['mask_height_widescreen']['size'] && '' != settings['mask_height_widescreen']['size']) ? settings['mask_height_widescreen']['size'] : maskBreakpointsHeights['desktop']['maskHeight'];
				} else {
					maskBreakpointsHeights[breakpointName] = [];

					var breakpointSetting = settings['mask_height_' + breakpointName];
					maskBreakpointsHeights[breakpointName]['maskHeight'] = (breakpointSetting && breakpointSetting['size'] && '' != breakpointSetting['size']) ? breakpointSetting['size'] : maskBreakpointsHeights[prevBreakpoint]['maskHeight'];

					prevBreakpoint = breakpointName;
				}
			} );

			onLoaded();

			if ( typeof ResizeObserver !== 'undefined' ) {
				new ResizeObserver( function( entries ) {
					if ( $target.hasClass( 'jet-unfold-state' ) ) {
						$mask.css( {
							'height': $contentInner.outerHeight()
						} );
					}
				} ).observe( $contentInner[0] );
			}
			
			if ( isTrue( hideOutsideClick ) ) {
				$( window ).on( 'mouseup.jetUnfold', function( event ) {
					let container = $target;
					if ( !container.is( event.target ) && 0 === container.has( event.target ).length && $target.hasClass( 'jet-unfold-state' ) ) {
						$button.trigger( 'click', {
							scrollOnFold: false
						} );
					}
				} )
			}

			$target.one('transitionend webkitTransitionEnd oTransitionEnd', function() {
				if ( !initialLoaded ) {
					onLoaded();
					initialLoaded = true;
				}
			});

			function onLoaded() {
				initialLoaded = true;

				var deviceHeight = getDeviceHeight();

				heightCalc = +deviceHeight + maskHeightAdv;

				if ( heightCalc < $contentInner.height() ) {

					if ( ! $target.hasClass( 'jet-unfold-state' ) ) {
						$separator.css( {
							'opacity': '1'
						} );
					}

					if ( ! $target.hasClass( 'jet-unfold-state' ) ) {
						$mask.css( {
							'height': deviceHeight
						} );
					} else {
						$mask.css( {
							'height': $contentInner.outerHeight()
						} );
					}
					$trigger.css( 'display', 'flex' );
					updateMaskGradientClass();
				} else {
					$trigger.hide();
					$mask.css( {
						'height': '100%'
					} );
					$content.css( {
						'max-height': 'none'
					} );
					$separator.css( {
						'opacity': '0'
					} );
					updateMaskGradientClass();
				}
			}

			$( window ).on( 'resize.jetWidgetUnfold orientationchange.jetWidgetUnfold', JetTricksTools.debounce( 50, function(){
				initialLoaded = false;
				onLoaded();
			} ) );

			$button.keypress( function( e ) {
				if ( e.which == 13 ) {
					$button.click();
					return false;
				}
			} );

			$button.on( 'click.jetUnfold', function( e, options ) {
				var $this         = $( this ),
					$buttonText   = $( '.jet-unfold__button-text', $this ),
					unfoldText    = settings['unfoldText'] || '',
					foldText      = settings['foldText'] || '',
					$buttonIcon   = $( '.jet-unfold__button-icon', $this ),
					unfoldIcon    = settings['unfoldIcon'] || '',
					foldIcon      = settings['foldIcon'] || '',
					contentHeight = $contentInner.outerHeight(),
					deviceHeight  = getDeviceHeight(),
					shouldScrollOnFold = ! options || false !== options.scrollOnFold;

				e.preventDefault();

				if ( typeof anime !== 'undefined' ) {
					anime.remove( $mask[0] );
				}

				if ( ! $target.hasClass( 'jet-unfold-state' ) ) {
					$target.addClass( 'jet-unfold-state' );

					$separator.css( {
						'opacity': '0'
					} );

					$buttonIcon.html( foldIcon );
					$buttonText.html( foldText );

					// Force recalculation of content height after state change
					setTimeout(function() {
						contentHeight = $contentInner.outerHeight();
						var duration = ( unfoldDuration && unfoldDuration.size != null ? unfoldDuration.size : 300 );
						if ( typeof anime !== 'undefined' ) {
							anime( {
								targets: $mask[0],
								height: contentHeight,
								duration: duration,
								easing: unfoldEasing || 'ease',
								complete: function( anim ) {
									$( document ).trigger( 'jet-engine/listing/recalculate-masonry' );
								}
							} );
						} else {
							$mask.css( 'height', contentHeight );
							$( document ).trigger( 'jet-engine/listing/recalculate-masonry' );
						}
					}, 0);

					if ( isTrue( autoHide ) ) {
						autoHideTrigger = setTimeout( function() {
							$button.trigger( 'click', {
								scrollOnFold: false
							} );
						}, autoHideTime * 1000 );
					}
				} else {
					clearTimeout( autoHideTrigger );

					$target.removeClass( 'jet-unfold-state' );

					$separator.css( {
						'opacity': '1'
					} );

					$buttonIcon.html( unfoldIcon );
					$buttonText.html( unfoldText );

					var foldDurationVal = ( foldDuration && foldDuration.size != null ? foldDuration.size : 300 );
					var onFoldComplete = function() {
						if ( shouldScrollOnFold && isTrue( settings['foldScrolling'] ) && settings['foldScrollOffset'] ) {
							$( 'html, body' ).animate( {
								scrollTop: $target.offset().top - ( settings['foldScrollOffset']['size'] || 0 )
							}, 'slow' );
						}
						$( document ).trigger( 'jet-engine/listing/recalculate-masonry' );
					};
					if ( typeof anime !== 'undefined' ) {
						anime( {
							targets: $mask[0],
							height: deviceHeight,
							duration: foldDurationVal,
							easing: foldEasing || 'ease',
							complete: onFoldComplete
						} );
					} else {
						$mask.css( 'height', deviceHeight );
						onFoldComplete();
					}
				}
				
				updateMaskGradientClass();
			} );

			function getDeviceMode() {
				return ( typeof elementorFrontend !== 'undefined' && elementorFrontend.getCurrentDeviceMode ) ? elementorFrontend.getCurrentDeviceMode() : 'desktop';
			}

			function getDeviceHeight() {
				if (heightControlType === 'word_count') {
					return calculateHeightByWordCount();
				}

				var device = getDeviceMode();
				var heightSettings;

				switch ( device ) {

					case 'mobile':
						heightSettings = settings.mask_height_mobile;
						break;

					case 'tablet':
						heightSettings = settings.mask_height_tablet;
						break;

					default:
						heightSettings = settings.mask_height || settings.height;

				}

				if ( ! heightSettings || ( heightSettings.size == null || heightSettings.size === '' ) ) {
					heightSettings = settings.mask_height || settings.height || { size: 50, unit: 'px' };
				}

				var unit = heightSettings.unit || 'px';
				var size = heightSettings.size != null ? heightSettings.size : 50;

				switch ( unit ) {

					case 'vh':
						return ( window.innerHeight * size ) / 100;

					case '%':
						var parentHeight = $contentInner.parent().height();
						return ( parentHeight * size ) / 100;

					default:
						return size;
				}
			}

			function getDeviceWordCount() {
				var device = getDeviceMode();
				var value;

				switch ( device ) {
					case 'mobile':
						value = settings.word_count_mobile || settings.wordCount;
						break;
					case 'tablet':
						value = settings.word_count_tablet || settings.wordCount;
						break;
					default:
						value = settings.word_count || settings.wordCount;
				}

				return ( value !== null && value !== undefined ) ? parseInt( value, 10 ) : 20;
			}
		},

		widgetHotspots: function( $scope ) {
			var $target   = $scope.find( '.jet-hotspots' ),
				$hotspots = $( '.jet-hotspots__item', $target),
				settings  = $target.data( 'settings' ),
				editMode  = Boolean( elementor && elementor.isEditMode() ),
				itemActiveClass = 'jet-hotspots__item--active';

			$target.imagesLoaded().progress( function() {
				$target.addClass( 'image-loaded' );
			} );

			$hotspots.each( function( index ) {
				var $this          = $( this ),
					horizontal     = $this.data( 'horizontal-position' ),
					vertical       = $this.data( 'vertical-position' ),
					tooltipWidth   = $this.data( 'tooltip-width' ) || null,
					showOnInit     = $this.data( 'show-on-init' ),
					itemSelector   = $this[0],
					options        = {};

				$this.css( {
					'left': horizontal + '%',
					'top': vertical + '%'
				} );

				if ( itemSelector._tippy ) {
					itemSelector._tippy.destroy();
				}

				options = {
					content: $this.data('tippy-content'),
					arrow: settings['tooltipArrow'] ? true : false,
					placement: settings['tooltipPlacement'],
					trigger: settings['tooltipTrigger'],
					appendTo: editMode ? document.body : $target[0],
					hideOnClick: 'manual' !== settings['tooltipTrigger'],
					maxWidth: 'none',
					offset: [0, settings['tooltipDistance']['size']],
					allowHTML: true,
					interactive: settings['tooltipInteractive'] ? true : false,
					onShow( instance ) {
						$( itemSelector ).addClass( itemActiveClass );

						if ( tooltipWidth ) {
							instance.popper.querySelector( '.tippy-box' ).style.width = tooltipWidth;
						}

					},
					onHidden( instance ) {
						$( itemSelector ).removeClass( itemActiveClass );
					}
				}

				if ( 'manual' != settings['tooltipTrigger'] ) {
					options['duration']  = [ settings['tooltipShowDuration']['size'], settings['tooltipHideDuration']['size'] ];
					options['animation'] = settings['tooltipAnimation'];
					options['delay']     = settings['tooltipDelay'];
				}

				tippy( [ itemSelector ], options );

				if ( 'manual' === settings['tooltipTrigger'] && itemSelector._tippy ) {
					itemSelector._tippy.show();
				}
			
				if ( ( showOnInit === 'yes' || settings['tooltipShowOnInit'] ) && itemSelector._tippy ) {
					itemSelector._tippy.show();
				}

			} );
		},

		columnEditorSettings: function( columnId ) {
			var editorElements = null,
				columnData     = {};

			if ( ! window.elementor.hasOwnProperty( 'elements' ) ) {
				return false;
			}

			editorElements = window.elementor.elements;

			if ( ! editorElements.models ) {
				return false;
			}

			$.each( editorElements.models, function( index, obj ) {

				$.each( obj.attributes.elements.models, function( index, obj ) {
					if ( columnId == obj.id ) {
						columnData = obj.attributes.settings.attributes;
					}
				} );

			} );

			return {
				'sticky': columnData['jet_tricks_column_sticky'] || false,
				'topSpacing': columnData['jet_tricks_top_spacing'] || 50,
				'bottomSpacing': columnData['jet_tricks_bottom_spacing'] || 50,
				'stickyOn': columnData['jet_tricks_column_sticky_on'] || [ 'desktop', 'tablet', 'mobile']
			}

		},

		sectionEditorSettings: function( $scope ) {
			var editorElements = null,
				sectionData     = {};

			if ( ! window.elementor.hasOwnProperty( 'elements' ) ) {
				return false;
			}

			sectionData = JetTricks.getElementorElementSettings( $scope );

			return {
				'particles': sectionData['section_jet_tricks_particles'] || 'false',
				'particles_json': sectionData['section_jet_tricks_particles_json'] || '',
			}

		}

	};

	$( window ).on( 'elementor/frontend/init', JetTricks.init );

	JetTricks.initBlocks = function() {
		if ( window.JetPlugins ) {
			window.JetPlugins.bulkBlocksInit( [
				{ block: 'jet-tricks/view-more', callback: JetTricks.widgetViewMore },
				{ block: 'jet-tricks/unfold', callback: JetTricks.widgetUnfold },
				{ block: 'jet-tricks/hotspots', callback: JetTricks.widgetHotspots },
			] );
		}
	};

	var JetTricksTools = {
		debounce: function( threshold, callback ) {
			var timeout;

			return function debounced( $event ) {
				function delayed() {
					callback.call( this, $event );
					timeout = null;
				}

				if ( timeout ) {
					clearTimeout( timeout );
				}

				timeout = setTimeout( delayed, threshold );
			};
		},

		widgetEditorSettings: function( widgetId ) {
			var editorElements = null,
				widgetData = {};
		
			if ( !window.elementor.hasOwnProperty( 'elements' ) || !window.elementor.elements.models ) {
				return false;
			}
		
			editorElements = window.elementor.elements;
		
			function findWidgetById( models, widgetId ) {
				let foundData = null;
		
				$.each( models, function( index, obj ) {
					if ( obj.id === widgetId ) {
						foundData = obj.attributes.settings.attributes;
						return false;
					}
					if ( obj.attributes.elements && obj.attributes.elements.models ) {
						foundData = findWidgetById( obj.attributes.elements.models, widgetId );
						if ( foundData ) {
							return false;
						}
					}
				});
		
				return foundData;
			}
		
			widgetData = findWidgetById( editorElements.models, widgetId ) || {};

			return {
				'speed': widgetData['jet_tricks_widget_parallax_speed'] || { 'size': 50, 'unit': '%'},
				'parallax': widgetData['jet_tricks_widget_parallax'] || 'false',
				'invert': widgetData['jet_tricks_widget_parallax_invert'] || 'false',
				'stickyOn': widgetData['jet_tricks_widget_parallax_on'] || [ 'desktop', 'tablet', 'mobile'],
				'satellite': widgetData['jet_tricks_widget_satellite'] || 'false',
				'satelliteType': widgetData['jet_tricks_widget_satellite_type'] || 'text',
				'satellitePosition': widgetData['jet_tricks_widget_satellite_position'] || 'top-center',
				'satelliteText': widgetData['jet_tricks_widget_satellite_text'] || 'Lorem Ipsum',
				'satelliteIcon': widgetData['selected_jet_tricks_widget_satellite_icon'] || '',
				'satelliteImage': widgetData['jet_tricks_widget_satellite_image'] || '',
				'satelliteLink': widgetData['jet_tricks_widget_satellite_link'] || '',
				'tooltip': widgetData['jet_tricks_widget_tooltip'] || 'false',
				'tooltipDescription': widgetData['jet_tricks_widget_tooltip_description'] || 'Lorem Ipsum',
				'tooltipPlacement': widgetData['jet_tricks_widget_tooltip_placement'] || 'top',
				'tooltipArrow': 'true' === widgetData['jet_tricks_widget_tooltip_arrow'] ? true : false,
				'xOffset': widgetData['jet_tricks_widget_tooltip_x_offset'] || 0,
				'yOffset': widgetData['jet_tricks_widget_tooltip_y_offset'] || 0,
				'tooltipAnimation': widgetData['jet_tricks_widget_tooltip_animation'] || 'shift-toward',
				'tooltipTrigger': widgetData['jet_tricks_widget_tooltip_trigger'] || 'mouseenter',
				'customSelector': widgetData['jet_tricks_widget_tooltip_custom_selector'] || '',
				'zIndex': widgetData['jet_tricks_widget_tooltip_z_index'] || '999',
				'appendTo': widgetData['jet_tricks_widget_tooltip_append_to'] || 'widget',
				'delay': widgetData['jet_tricks_widget_tooltip_delay'] || '0',
				'followCursor': widgetData['jet_tricks_widget_tooltip_follow_cursor'] || 'false',
				'tooltipDevices': Array.isArray( widgetData['jet_tricks_widget_tooltip_devices'] )
					? widgetData['jet_tricks_widget_tooltip_devices']
					: [],
				'scrollReveal': widgetData['jet_tricks_widget_scroll_reveal'] || 'false',
				'scrollRevealEffect': widgetData['jet_tricks_widget_scroll_reveal_effect'] || 'fade-up',
				'scrollRevealMaskDirection': widgetData['jet_tricks_widget_scroll_reveal_mask_direction'] || 'up',
				'scrollRevealDuration': widgetData['jet_tricks_widget_scroll_reveal_duration'] || { 'size': 0.6, 'unit': 's' },
				'scrollRevealDelay': widgetData['jet_tricks_widget_scroll_reveal_delay'] || { 'size': 0, 'unit': 's' },
				'scrollRevealOnce': widgetData['jet_tricks_widget_scroll_reveal_once'] || 'true',
				'scrollRevealRootMargin': widgetData['jet_tricks_widget_scroll_reveal_root_margin'] !== undefined && widgetData['jet_tricks_widget_scroll_reveal_root_margin'] !== null ? parseInt( widgetData['jet_tricks_widget_scroll_reveal_root_margin'], 10 ) : 0,
				'scrollRevealOn': widgetData['jet_tricks_widget_scroll_reveal_on'] || [ 'desktop', 'tablet', 'mobile' ]
			}
		}
	}

	/**
	 * Jet jetViewMore Class
	 *
	 * @return {void}
	 */
	window.jetViewMore = function( $selector, settings ) {
		var self            = this,
			$window         = $( window ),
			$button         = $( '.jet-view-more__button', $selector ),
			defaultSettings = {
				sections: {},
				effect: 'move-up',
				showall: false
			},
			settings        = $.extend( {}, defaultSettings, settings ),
			sections        = settings['sections'],
			sectionsData    = {},
			editMode        = Boolean( elementor && elementor.isEditMode() ),
			readLess      = settings['read_less'] || false,
			readMoreLabel   = settings['read_more_label'],
			readLessLabel   = settings['read_less_label'],
			readMoreIconHtml = settings['read_more_icon_html'] || '',
			readLessIconHtml = settings['read_less_icon_html'] || '',
			hideAll         = settings['hide_all'] || false,
			isOpened        = false;

		/**
		 * Init
		 */
		self.init = function() {
			self.setSectionsData();

			if ( editMode ) {
				return false;
			}			

			function hideSection($section) {
				// Apply hide effect if available
				if (settings['hide_effect'] && settings['hide_effect'] !== 'none') {
					$section.addClass('view-more-hiding');
					$section.addClass('jet-tricks-' + settings['hide_effect'] + '-hide-effect');
					
					// Remove classes after animation completes
					(function($currentSection) {
						$currentSection.on('animationend', function animationEndHandler() {
							$currentSection.off('animationend', animationEndHandler);
							$currentSection.removeClass('view-more-hiding');
							$currentSection.removeClass('jet-tricks-' + settings['hide_effect'] + '-hide-effect');
							$currentSection.css('height', '');
							$currentSection.removeClass('view-more-visible');
							$currentSection.removeClass('jet-tricks-' + settings['effect'] + '-effect');
						});
					})($section);
				} else {
					$section.css('height', '');
					$section.removeClass('view-more-visible');
					$section.removeClass('jet-tricks-' + settings['effect'] + '-effect');
				}
			}

			function showAllSections() {
				for ( var section in sectionsData ) {
					var $section = sectionsData[ section ]['selector'];
					sectionsData[ section ]['visible'] = true;
					$section.addClass( 'view-more-visible' );
					$section.addClass( 'jet-tricks-' + settings['effect'] + '-effect' );
				}
			}

			function hideAllSections() {
				for ( var section in sectionsData ) {
					var $section = sectionsData[ section ]['selector'];
					sectionsData[ section ]['visible'] = false;
					hideSection($section);
				}
			}

			function showNextSection() {
				for ( var section in sectionsData ) {
					var $section = sectionsData[ section ]['selector'];
					if ( !sectionsData[ section ]['visible'] ) {
						sectionsData[ section ]['visible'] = true;
						$section.addClass( 'view-more-visible' );
						$section.addClass( 'jet-tricks-' + settings['effect'] + '-effect' );
						break;
					}
				}
			}

			function hideNextSection() {
				var sectionKeys = Object.keys(sectionsData).reverse();
				for (var i = 0; i < sectionKeys.length; i++) {
					var sectionKey = sectionKeys[i];
					var $section = sectionsData[sectionKey]['selector'];
					if (sectionsData[sectionKey]['visible']) {
						sectionsData[sectionKey]['visible'] = false;
						hideSection($section);
						break;
					}
				}
			}

			$button.on('click', function() {
				if (readLess) {
					if (!isOpened) {
						if (!settings.showall) {
							showNextSection();
							var allVisible = true;
							for (var section in sectionsData) {
								if (!sectionsData[section]['visible']) {
									allVisible = false;
									break;
								}
							}
							if (allVisible) {
								$button.find('.jet-view-more__label').text(readLessLabel);
								var lessIconHtml = readLessIconHtml;
								if ( lessIconHtml ) {
									$button.find('.jet-view-more__icon').html( lessIconHtml );
								}
								$button.addClass('jet-view-more__button--read-less');
								isOpened = true;
							}
						} else {
						showAllSections();
						$button.find('.jet-view-more__label').text(readLessLabel);
							var lessIconHtml2 = readLessIconHtml;
							if ( lessIconHtml2 ) {
								$button.find('.jet-view-more__icon').html( lessIconHtml2 );
							}
							$button.addClass('jet-view-more__button--read-less');
							isOpened = true;
						}
					} else {
						if (hideAll) {
						hideAllSections();
						$button.find('.jet-view-more__label').text(readMoreLabel);
							var moreIconHtml = readMoreIconHtml;
							if ( moreIconHtml ) {
								$button.find('.jet-view-more__icon').html( moreIconHtml );
							}
							$button.removeClass('jet-view-more__button--read-less');
							isOpened = false;
						} else {
							hideNextSection();
							var allHidden = true;
							for (var section in sectionsData) {
								if (sectionsData[section]['visible']) {
									allHidden = false;
									break;
								}
							}
							if (allHidden) {
								$button.find('.jet-view-more__label').text(readMoreLabel);
								var moreIconHtml2 = readMoreIconHtml;
								if ( moreIconHtml2 ) {
									$button.find('.jet-view-more__icon').html( moreIconHtml2 );
								}
								$button.removeClass('jet-view-more__button--read-less');
								isOpened = false;
							}
						}
					}
				} else {
					if (!settings.showall) {
						showNextSection();
					} else {
						showAllSections();
					}
					var allVisible = true;
					for (var section in sectionsData) {
						if (!sectionsData[section]['visible']) {
							allVisible = false;
							break;
						}
					}
					if (allVisible) {
						$button.css({ 'display': 'none' });
					}
				}
			});

			$button.keydown(function(e) {
				var $which = e.which || e.keyCode;
				if ($which == 13 || $which == 32) {
					e.preventDefault();
					if (readLess) {
						$button.trigger('click');
					} else {
						if (!settings.showall) {
							showNextSection();
						} else {
							showAllSections();
						}
						var allVisible = true;
						for (var section in sectionsData) {
							if (!sectionsData[section]['visible']) {
								allVisible = false;
								break;
							}
						}
						if (allVisible) {
							$button.css({ 'display': 'none' });
						}
					}
				}
			});
		};

		self.setSectionsData = function() {

			for ( var section in sections ) {
				var $selector = $( '#' + sections[ section ] );

				if ( ! editMode ) {
					$selector.addClass( 'jet-view-more-section' );
				} else {
					$selector.addClass( 'jet-view-more-section-edit-mode' );
				}

				sectionsData[ section ] = {
					'section_id': sections[ section ],
					'selector': $selector,
					'visible': false,
				}
			}
		};
	};

	/**
	 * [jetWidgetParallax description]
	 * @param  {[type]} $scope [description]
	 * @return {[type]}        [description]
	 */
	window.jetWidgetParallax = function( $scope ) {
		var self         = this,
			$target      = $scope,
			$section     = $scope.closest( '.elementor-top-section' ),
			widgetId     = $scope.data('id'),
			settings     = {},
			editMode     = Boolean( elementor && elementor.isEditMode() ),
			$window      = $( window ),
			isSafari     = !!navigator.userAgent.match(/Version\/[\d\.]+.*Safari/),
			platform     = navigator.platform,
			safariClass  = isSafari ? 'is-safari' : '',
			macClass    = 'MacIntel' == platform ? ' is-mac' : '';

		/**
		 * Init
		 */
		self.init = function() {

			$scope.addClass( macClass );

			if ( ! editMode ) {
				settings = $scope.data( 'jet-tricks-settings' );
			} else {
				settings = JetTricksTools.widgetEditorSettings( widgetId );
			}

			if ( ! settings ) {
				return false;
			}

			if ( 'undefined' === typeof settings ) {
				return false;
			}

			if ( 'false' === settings['parallax'] || 'undefined' === typeof settings['parallax'] ) {
				return false;
			}

			$window.on( 'scroll.jetWidgetParallax resize.jetWidgetParallax', self.scrollHandler ).trigger( 'resize.jetWidgetParallax' );
		};

		self.scrollHandler = function( event ) {
			var speed             = +settings['speed']['size'] * 0.01,
				invert            = 'true' == settings['invert'] ? -1 : 1,
				winHeight         = $window.height(),
				winScrollTop      = $window.scrollTop(),
				offsetTop         = $scope.offset().top,
				thisHeight        = $scope.outerHeight(),
				sectionHeight     = $section.length ? $section.outerHeight() : 0,
				positionDelta     = winScrollTop - offsetTop + ( winHeight / 2 ),
				abs               = positionDelta > 0 ? 1 : -1,
				posY              = abs * Math.pow( Math.abs( positionDelta ), 0.85 ),
				availableDevices  = settings['stickyOn'] || [],
				currentDeviceMode = JetTricks.getDeviceMode();

			posY = invert * Math.ceil( speed * posY );

			if ( ! availableDevices.length || -1 !== availableDevices.indexOf( currentDeviceMode ) ) {
				$target.css( {
					'transform': 'translateY(' + posY + 'px)'
				} );
			} else {
				$target.css( {
					'transform': 'translateY(0)'
				} );
			}
		};
	};

	/**
	 * Scroll reveal on viewport entry (Elementor widgets).
	 *
	 * @param {jQuery} $scope Widget root.
	 */
	window.jetWidgetScrollReveal = function( $scope ) {
		var self     = this,
			settings = {},
			io         = null,
			el         = $scope[ 0 ];

		self.init = function() {

			if ( ! el || $scope.data( 'jetScrollRevealInit' ) ) {
				return false;
			}

			settings = $scope.data( 'jet-tricks-settings' );

			if ( ! settings || typeof settings !== 'object' ) {
				return false;
			}

			if ( settings.scrollReveal !== 'true' && settings.scrollReveal !== true ) {
				return false;
			}

			var availableDevices  = settings.scrollRevealOn || [],
				currentDeviceMode = JetTricks.getDeviceMode(),
				deviceOk          = ! availableDevices.length || -1 !== availableDevices.indexOf( currentDeviceMode );

			if ( ! deviceOk ) {
				$scope
					.addClass( 'jet-scroll-reveal--instant-exit jet-scroll-reveal--in-view' )
					.removeClass( 'jet-scroll-reveal--pending' );
				return false;
			}

			if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				$scope
					.addClass( 'jet-scroll-reveal--instant-exit jet-scroll-reveal--in-view' )
					.removeClass( 'jet-scroll-reveal--pending' );
				return false;
			}

			if ( typeof IntersectionObserver === 'undefined' ) {
				$scope
					.addClass( 'jet-scroll-reveal--instant-exit jet-scroll-reveal--in-view' )
					.removeClass( 'jet-scroll-reveal--pending' );
				return false;
			}

			$scope.data( 'jetScrollRevealInit', true );

			var dur = settings.scrollRevealDuration && typeof settings.scrollRevealDuration.size !== 'undefined'
				? parseFloat( settings.scrollRevealDuration.size, 10 )
				: 0.6;
			var del = settings.scrollRevealDelay && typeof settings.scrollRevealDelay.size !== 'undefined'
				? parseFloat( settings.scrollRevealDelay.size, 10 )
				: 0;
			var rm  = typeof settings.scrollRevealRootMargin === 'number'
				? settings.scrollRevealRootMargin
				: parseInt( settings.scrollRevealRootMargin || 0, 10 );
			var once = settings.scrollRevealOnce === 'true' || settings.scrollRevealOnce === true;

			el.style.setProperty( '--jet-sr-duration', dur + 's' );
			el.style.setProperty( '--jet-sr-delay', del + 's' );

			var rootMargin = '0px 0px ' + rm + 'px 0px';

			io = new IntersectionObserver( function( entries ) {
				entries.forEach( function( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.remove( 'jet-scroll-reveal--instant-exit' );
						entry.target.classList.add( 'jet-scroll-reveal--in-view' );
						entry.target.classList.remove( 'jet-scroll-reveal--pending' );
						if ( once && io ) {
							io.unobserve( entry.target );
						}
					} else if ( ! once ) {
						entry.target.classList.add( 'jet-scroll-reveal--instant-exit' );
						entry.target.classList.remove( 'jet-scroll-reveal--in-view' );
						entry.target.classList.add( 'jet-scroll-reveal--pending' );
					}
				} );
			}, {
				root: null,
				rootMargin: rootMargin,
				threshold: 0
			} );

			io.observe( el );
		};
	};

	/**
	 * [jetWidgetSatellite description]
	 * @param  {[type]} $scope [description]
	 * @return {[type]}        [description]
	 */
	window.jetWidgetSatellite = function( $scope ) {
		var self     = this,
			widgetId = $scope.data('id'),
			settings = {},
			editMode = Boolean( elementor && elementor.isEditMode() );

		self.getClampedNumber = function( value, fallback, min, max ) {
			var parsed = parseFloat( value );

			if ( isNaN( parsed ) ) {
				parsed = fallback;
			}

			if ( parsed < min ) {
				parsed = min;
			}

			if ( parsed > max ) {
				parsed = max;
			}

			return parsed;
		};

		self.getSatelliteLayoutStyleAttr = function() {
			var x = self.getClampedNumber( settings['satelliteOffsetX'], 0, -500, 500 );
			var y = self.getClampedNumber( settings['satelliteOffsetY'], 0, -500, 500 );
			var rotate = self.getClampedNumber( settings['satelliteRotate'], 0, -180, 180 );
			var zIndex = self.getClampedNumber( settings['satelliteZIndex'], 2, -10, 999 );
			var styleParts = [
				'--jet-satellite-offset-x:' + x + 'px',
				'--jet-satellite-offset-y:' + y + 'px',
				'--jet-satellite-rotate:' + rotate + 'deg',
				'--jet-satellite-z:' + zIndex
			];

			return ' style="' + styleParts.join( ';' ) + '"';
		};

		/**
		 * Init
		 */
		self.init = function() {

			if ( ! editMode ) {
				settings = $scope.data( 'jet-tricks-settings' );
			} else {
				settings = JetTricksTools.widgetEditorSettings( widgetId );
			}

			if ( ! settings || typeof settings !== 'object' ) {
				return false;
			}

			if ( 'false' === settings['satellite'] || 'undefined' === typeof settings['satellite'] ) {
				return false;
			}

			$scope.addClass( 'jet-satellite-widget' );

			$( '.jet-tricks-satellite', $scope ).addClass( 'jet-tricks-satellite--' + settings['satellitePosition'] );

			//satellite display in edit mode
			if ( editMode && $scope.find( '.jet-tricks-satellite' ).length === 0 ) {
				var html = '';
				var layoutStyle = self.getSatelliteLayoutStyleAttr();
				var pos = settings['satellitePosition'] || 'top-center';
				var rootTag = ( $scope[0] && $scope[0].tagName ) ? $scope[0].tagName.toLowerCase() : '';
				var wrapperTag = [ 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ].indexOf( rootTag ) !== -1 ? 'span' : 'div';
				var instanceTag = 'span' === wrapperTag ? 'span' : 'div';

				var link = settings['satelliteLink'] || {};
				var linkStart = '', linkEnd = '';
				if ( link.url ) {
					linkStart = '<a class="jet-tricks-satellite__link">';
					linkEnd = '</a>';
				}

				if ( settings['satelliteType'] === 'text' && settings['satelliteText'] ) {
					html = '<' + wrapperTag + ' class="jet-tricks-satellite jet-tricks-satellite--' + pos + '"' + layoutStyle + '><' + wrapperTag + ' class="jet-tricks-satellite__inner"><' + wrapperTag + ' class="jet-tricks-satellite__text">' + linkStart + '<span>' + settings['satelliteText'] + '</span>' + linkEnd + '</' + wrapperTag + '></' + wrapperTag + '></' + wrapperTag + '>';
				} else if ( settings['satelliteType'] === 'icon' && settings['satelliteIcon'] && settings['satelliteIcon'].value ) {
					html = '<' + wrapperTag + ' class="jet-tricks-satellite jet-tricks-satellite--' + pos + '"' + layoutStyle + '><' + wrapperTag + ' class="jet-tricks-satellite__inner"><' + wrapperTag + ' class="jet-tricks-satellite__icon">' + linkStart + '<' + instanceTag + ' class="jet-tricks-satellite__icon-instance jet-tricks-icon"><i class="' + settings['satelliteIcon'].value + '"></i></' + instanceTag + '>' + linkEnd + '</' + wrapperTag + '></' + wrapperTag + '></' + wrapperTag + '>';
				} else if ( settings['satelliteType'] === 'image' && settings['satelliteImage'] && settings['satelliteImage'].url ) {
					html = '<' + wrapperTag + ' class="jet-tricks-satellite jet-tricks-satellite--' + pos + '"' + layoutStyle + '><' + wrapperTag + ' class="jet-tricks-satellite__inner"><' + wrapperTag + ' class="jet-tricks-satellite__image">' + linkStart + '<img class="jet-tricks-satellite__image-instance" src="' + settings['satelliteImage'].url + '" alt="">' + linkEnd + '</' + wrapperTag + '></' + wrapperTag + '></' + wrapperTag + '>';
				}

				if ( html ) {
					$scope.prepend(html);
				}
			}
		};
	};

	/**
	 * [jetWidgetTooltip description]
	 * @param  {[type]} $scope [description]
	 * @return {[type]}        [description]
	 */
	window.jetWidgetTooltip = function( $scope ) {
		var self            = this,
			widgetId        = $scope.data('id'),
			widgetSelector  = $scope[0],
			tooltipSelector = widgetSelector,
			settings        = {},
			editMode        = Boolean( elementor && elementor.isEditMode() ),
			$window         = $( window ),
			resizeTimer;

		self.removeTooltipContent = function() {
			$scope.find( '> .jet-tooltip-widget__content' ).remove();
		};

		self.destroyTooltipInstance = function() {
			if ( tooltipSelector && tooltipSelector._tippy ) {
				tooltipSelector._tippy.destroy();
			}
			if ( widgetSelector && widgetSelector._tippy ) {
				widgetSelector._tippy.destroy();
			}
		};

		self.isTooltipDeviceAllowed = function() {
			var devices = settings['tooltipDevices'];
			if ( typeof devices === 'undefined' || devices === null ) {
				return true;
			}
			if ( ! devices.length ) {
				return true;
			}
			return -1 !== devices.indexOf( JetTricks.getDeviceMode() );
		};

		self.getTooltipDelayMs = function() {
			var d = settings['delay'];
			if ( d && typeof d === 'object' && d.hasOwnProperty( 'size' ) ) {
				return d.size ? d.size : 0;
			}
			if ( typeof d === 'number' ) {
				return d;
			}
			return 0;
		};

		self.mountTippy = function() {
			if ( ! tooltipSelector ) {
				return;
			}

			var contentEl = $scope.find( '.jet-tooltip-widget__content' )[0];
			if ( ! contentEl ) {
				return;
			}

			var appendToBody = editMode || ( settings['appendTo'] === 'body' );

			tippy(
				[ tooltipSelector ],
				{
					content: contentEl.innerHTML,
					allowHTML: true,
					appendTo: appendToBody ? document.body : widgetSelector,
					arrow: settings['tooltipArrow'] ? true : false,
					placement: settings['tooltipPlacement'],
					offset: [ settings['xOffset'], settings['yOffset'] ],
					animation: settings['tooltipAnimation'],
					trigger: settings['tooltipTrigger'],
					interactive: settings['followCursor'] === 'false' || settings['followCursor'] === 'initial',
					zIndex: settings['zIndex'],
					maxWidth: 'none',
					delay: self.getTooltipDelayMs(),
					followCursor: settings['followCursor'] === 'false' ? false : (settings['followCursor'] === 'true' ? true : settings['followCursor']),
					onCreate: function (instance) {
						if ( appendToBody ) {
							var tippyId = editMode ? ( tooltipSelector.getAttribute('data-id') || widgetId ) : widgetId;
							if ( tippyId ) {
								instance.popper.classList.add( 'tippy-' + tippyId );
							}
							if ( settings['wrapperClass'] ) {
								instance.popper.classList.add( settings['wrapperClass'] );
							}
						}
					},
					onShow: function (instance) {
						// JetFormBuilder Formless Actions Endpoints compatibility
						var addButtonListeners = window.crocoblock && window.crocoblock.frontComponents && window.crocoblock.frontComponents.addButtonListeners;
						if ( addButtonListeners && instance.popper ) {
							var buttons = instance.popper.querySelectorAll( '[data-jfb-submit-endpoint]' );
							buttons.forEach( function (el) { addButtonListeners( el ); } );
						}
					}
				}
			);

			if ( editMode && tooltipSelector && tooltipSelector._tippy ) {
				tooltipSelector._tippy.show();
			}
		};

		self.refreshTooltipForDevice = function() {
			self.destroyTooltipInstance();

			if ( ! self.isTooltipDeviceAllowed() ) {
				return;
			}

			self.mountTippy();
		};

		/**
		 * Init
		 */
		self.init = function() {

			if ( ! editMode ) {
				settings = $scope.data( 'jet-tricks-settings' );
			} else {
				settings = JetTricksTools.widgetEditorSettings( widgetId );
			}

			if ( ! settings ) {
				return false;
			}

			if ( 'undefined' === typeof settings ) {
				return false;
			}

			if ( 'false' === settings['tooltip'] || 'undefined' === typeof settings['tooltip'] || '' === settings['tooltipDescription'] ) {
				self.destroyTooltipInstance();
				self.removeTooltipContent();
				return false;
			}

			$scope.addClass( 'jet-tooltip-widget' );

			tooltipSelector = widgetSelector;
			if ( settings['customSelector'] ) {
				var customEl = $( '.' + settings['customSelector'], $scope )[0];
				if ( customEl ) {
					tooltipSelector = customEl;
				}
			}

			if ( editMode && ! $( '#jet-tricks-tooltip-content-' + widgetId )[0] ) {
				var rootTag = ( $scope[0] && $scope[0].tagName ) ? $scope[0].tagName.toLowerCase() : '';
				var wrapperTag = [ 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ].indexOf( rootTag ) !== -1 ? 'span' : 'div';
				var template = $( '<' + wrapperTag + '>', {
					id: 'jet-tricks-tooltip-content-' + widgetId,
					class: 'jet-tooltip-widget__content'
				} );

				template.html( settings['tooltipDescription'] );
				$scope.append( template );
			}

			$window.off( 'resize.jetTooltip' + widgetId );
			$window.on( 'resize.jetTooltip' + widgetId, function() {
				clearTimeout( resizeTimer );
				resizeTimer = setTimeout( function() {
					self.refreshTooltipForDevice();
				}, 200 );
			} );

			if ( editMode && document.body ) {
				var editorBindingsCleanupKey = 'jetTooltipEditorBindingsCleanup';
				var prevEditorCleanup = $scope.data( editorBindingsCleanupKey );
				if ( typeof prevEditorCleanup === 'function' ) {
					prevEditorCleanup();
				}
				$scope.removeData( editorBindingsCleanupKey );

				var onEditorDeviceModeChange = function() {
					clearTimeout( resizeTimer );
					resizeTimer = setTimeout( function() {
						self.refreshTooltipForDevice();
					}, 50 );
				};
				window.addEventListener( 'elementor/device-mode/change', onEditorDeviceModeChange );
				var deviceModeObserver = new MutationObserver( function( mutations ) {
					var i;
					for ( i = 0; i < mutations.length; i++ ) {
						if ( mutations[ i ].attributeName === 'data-elementor-device-mode' ) {
							onEditorDeviceModeChange();
							break;
						}
					}
				} );
				deviceModeObserver.observe( document.body, {
					attributes: true,
					attributeFilter: [ 'data-elementor-device-mode' ]
				} );

				$scope.data( editorBindingsCleanupKey, function jetTooltipEditorBindingsCleanup() {
					window.removeEventListener( 'elementor/device-mode/change', onEditorDeviceModeChange );
					deviceModeObserver.disconnect();
				} );
			}

			self.refreshTooltipForDevice();

		};
	};

	JetTricks.initBlocksExtensions = function() {
		var isBlockEditorContext = !! (
			document.body && (
				document.body.classList.contains( 'block-editor-page' ) ||
				document.body.classList.contains( 'block-editor-iframe__body' ) ||
				document.body.classList.contains( 'editor-styles-wrapper' )
			)
		) || !! document.querySelector( '.block-editor-block-list__layout, .edit-post-visual-editor' );

		$( '[data-jet-tricks-settings]' ).each( function() {
			var $scope   = $( this ),
				settings = $scope.data( 'jet-tricks-settings' );

			if ( ! settings ) {
				return;
			}

			if ( settings['parallax'] === 'true' ) {
				new jetWidgetParallax( $scope ).init();
			}

			if ( settings['satellite'] === 'true' && ! isBlockEditorContext ) {
				new jetWidgetSatellite( $scope ).init();
			}

			if ( settings['tooltip'] === 'true' && typeof tippy !== 'undefined' && ! isBlockEditorContext ) {
				new jetWidgetTooltip( $scope ).init();
			}

			if ( settings['scrollReveal'] === 'true' || settings['scrollReveal'] === true ) {
				new jetWidgetScrollReveal( $scope ).init();
			}
		} );

		JetTricks.initBlocksParticles();
	};

	JetTricks.initBlocksParticles = function() {
		$( '.jet-tricks-particles-section[data-jet-tricks-particles="true"]' ).each( function() {
			var $scope  = $( this ),
				blockId = $scope.attr( 'data-jet-tricks-particles-id' ),
				jsonStr = $scope.attr( 'data-jet-tricks-particles-json' );

			if ( ! blockId || ! jsonStr ) {
				return;
			}

			try {
				var particlesJson = JSON.parse( jsonStr );
			} catch ( e ) {
				return;
			}

			JetTricks.loadParticles( $scope, 'jet-tricks-particles-instance-' + blockId, particlesJson );
		} );
	};

	JetTricks.destroyBlockStickyColumn = function( $target ) {
		$target.css( {
			position: '',
			top: '',
			bottom: '',
			alignSelf: '',
			zIndex: '',
		} );

	};

	JetTricks.getBlockStickyAlign = function( settings ) {
		var align = settings && settings.stickyAlign ? String( settings.stickyAlign ) : 'top';

		if ( -1 === [ 'top', 'center', 'bottom' ].indexOf( align ) ) {
			align = 'top';
		}

		return align;
	};

	JetTricks.applyBlockStickyColumn = function( $target, settings, topSpacing, bottomSpacing ) {
		var align = JetTricks.getBlockStickyAlign( settings );
		var alignSelf = 'flex-start';

		if ( 'bottom' === align ) {
			alignSelf = 'flex-end';
		} else if ( 'center' === align ) {
			alignSelf = 'center';
		}

		$target.css( {
			position: 'sticky',
			top: topSpacing,
			bottom: bottomSpacing,
			alignSelf: alignSelf,
			zIndex: settings.zIndex !== undefined && settings.zIndex !== null && settings.zIndex !== '' ? settings.zIndex : '',
		} );
	};

	JetTricks.initBlockStickyColumn = function( $target ) {
		var raw = $target.attr( 'data-jet-settings' );
		var settings = raw;
		if ( typeof settings === 'string' ) {
			try {
				settings = JSON.parse( settings );
			} catch ( e ) {
				return;
			}
		}
		if ( ! settings || typeof settings !== 'object' || ! settings.stickyOn || ! settings.stickyOn.length ) {
			return;
		}

		var topS = parseInt( settings.topSpacing, 10 );
		if ( settings.topSpacing === undefined || settings.topSpacing === null || settings.topSpacing === '' || window.isNaN( topS ) ) {
			topS = 50;
		}
		var bottomS = parseInt( settings.bottomSpacing, 10 );
		if ( settings.bottomSpacing === undefined || settings.bottomSpacing === null || settings.bottomSpacing === '' || window.isNaN( bottomS ) ) {
			bottomS = 50;
		}

		var allowed = -1 !== settings.stickyOn.indexOf( JetTricks.getDeviceMode() );

		if ( ! allowed ) {
			JetTricks.destroyBlockStickyColumn( $target );
			return;
		}

		var $row = $target.closest( '.wp-block-columns' );
		if ( ! $row.length ) {
			JetTricks.destroyBlockStickyColumn( $target );
			return;
		}

		JetTricks.applyBlockStickyColumn( $target, settings, topS, bottomS );
	};

	JetTricks.initBlocksStickyColumns = function() {
		$( '.wp-block-column.jet-sticky-column' ).each( function() {
			JetTricks.initBlockStickyColumn( $( this ) );
		} );

		if ( ! JetTricks._blockStickyResizeBound ) {
			JetTricks._blockStickyResizeBound = true;
			$( window ).on(
				'resize.jetTricksBlockSticky orientationchange.jetTricksBlockSticky',
				JetTricksTools.debounce( 150, function() {
					$( '.wp-block-column.jet-sticky-column' ).each( function() {
						JetTricks.initBlockStickyColumn( $( this ) );
					} );
				} )
			);
		}
	};

	window.JetTricks = JetTricks;

	if ( window.JetPlugins ) {
		JetTricks.initBlocks();
		$( function() { JetPlugins.init() } );
	}

	$( function() {
		JetTricks.initBlocksExtensions();
		JetTricks.initBlocksStickyColumns();
	} );

}( jQuery, window.elementorFrontend ) );
