/* jSticky Plugin
 * =============
 * Author: Andrew Henderson (@AndrewHenderson)
 * Contributor: Mike Street (@mikestreety)
 * Date: 9/7/2012
 * Update: 09/20/2016
 * Website: http://github.com/andrewhenderson/jsticky/
 * Description: A jQuery plugin that keeps select DOM
 * element(s) in view while scrolling the page.
 */

;(function($) {

  $.fn.jetStickySection = function(options) {
    var defaults = {
      topSpacing: 0, // No spacing by default
      zIndex: '', // No default z-index
      stopper: $('.sticky-stopper'), // Default stopper class, also accepts number value
      stickyClass: false // Class applied to element when it's stuck
    };
    var settings = $.extend({}, defaults, options); // Accepts custom stopper id or class

    // Checks if custom z-index was defined
    function checkIndex() {
      if (typeof settings.zIndex == 'number') {
        return true;
      } else {
        return false;
      }
    }

    var hasIndex = checkIndex(); // True or false

    // Checks if a stopper exists in the DOM or number defined
    function checkStopper() {
      if (0 < settings.stopper.length || typeof settings.stopper === 'number') {
        return true;
      } else {
        return false;
      }
    }
    var hasStopper = checkStopper(); // True or false
    return this.each(function() {

      var $this = $(this);
      var topSpacing = settings.topSpacing;
      var zIndex = settings.zIndex;
      var stopper = settings.stopper;
      var $window = $(window);
      var detached = false;
      var stick = false;
      var isSection = $this.hasClass('elementor-section');
      var isOuterContainer = $this.hasClass('e-parent');
      var placeholder = $( '<div></div>' ).addClass( 'sticky-placeholder' );

      function getMetrics() {
        var hasPlaceholder = placeholder.parent().length > 0;
        var currentHeight = $this.outerHeight();
        var currentWidth;
        var currentLeft;
        var currentTop;

        if ( isSection || isOuterContainer ) {
          currentWidth = $this.parent().outerWidth();
        } else {
          currentWidth = hasPlaceholder ? placeholder.outerWidth() : $this.outerWidth();
        }

        if ( ! currentWidth ) {
          currentWidth = $this.outerWidth();
        }

        currentLeft = hasPlaceholder ? placeholder.offset().left : $this.offset().left;
        currentTop  = hasPlaceholder ? placeholder.offset().top : $this.offset().top;

        return {
          height: currentHeight,
          width: currentWidth,
          left: currentLeft,
          top: currentTop,
          pushPoint: currentTop - topSpacing
        };
      }

      function resetStickyStyles() {
        $this.css( {
          position: '',
          top: '',
          left: '',
          right: '',
          width: '',
          transform: '',
          'z-index': ''
        } );
      }

      function stickyScroll() {
        if (detached) {
          return;
        }

        var metrics = getMetrics();
        var windowTop = $window.scrollTop(); // Check window's scroll position
        var stopPoint = stopper;

        placeholder
            .width( metrics.width )
            .height( metrics.height );

        if (hasStopper) {
          if (typeof settings.stopper !== 'number') {
            var stopperTop = settings.stopper.offset().top;
            stopPoint = stopperTop - metrics.height - topSpacing;
          } else if (typeof settings.stopper === 'number') {
            stopPoint = settings.stopper - metrics.height - topSpacing;
          }
        }

        if ( metrics.pushPoint < windowTop ) {
          // Create a placeholder for sticky element to occupy vertical real estate
          if(settings.stickyClass)
            $this.addClass(settings.stickyClass);

          var cssOptions = {
            position: 'fixed',
            top: topSpacing,
            width: metrics.width
          };
          if (!isSection && !isOuterContainer) {
            cssOptions.left = metrics.left;
          }

          $this.after(placeholder).css(cssOptions);

          if (hasIndex) {
            $this.css({
              zIndex: zIndex
            });
          }

          if ( hasStopper && stopPoint < windowTop ) {
              var diff = (stopPoint - windowTop) + topSpacing;

              $this.css({
                top: diff
              });
          }
          if ( ! stick ) {
            $this.trigger( 'jetStickySection:stick' );
          }

          stick = true;
        } else {
          if ( settings.stickyClass ) {
            $this.removeClass(settings.stickyClass);
          }

          resetStickyStyles();
          placeholder.remove();

          if ( stick ) {
            $this.trigger( 'jetStickySection:unstick' );
          }

          stick = false;
        }
      }

      function detachStickyScroll() {
        detached = true;

        //$window.off('load', stickyScroll);
        $window.off('scroll', stickyScroll);
        $window.off('touchmove', stickyScroll);
        $window.off('resize', stickyScroll);

        if( settings.stickyClass ) {
          $this.removeClass(settings.stickyClass);
        }

        resetStickyStyles();
        placeholder.remove();
      }

      if( $window.innerHeight() > $this.outerHeight() ) {
        //$window.on('load', stickyScroll);
        $this.on( 'jetStickySection:activated', stickyScroll );

        $window.on('scroll', stickyScroll);
        $window.on('touchmove', stickyScroll);
        $window.on('resize', stickyScroll);

        $this.on( 'jetStickySection:detach', detachStickyScroll );
      }
    });
  };
})(jQuery);
