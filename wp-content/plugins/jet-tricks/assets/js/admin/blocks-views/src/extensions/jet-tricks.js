/** JetTricks — Gutenberg extensions entry. */
import { PARTICLES_BLOCKS, getParticlesAttributes, getParticlesPanel } from './particles';
import { TOOLTIP_BLOCKS, getTooltipAttributes, getTooltipPanel, withBlockTooltipEditor } from './tooltip';
import { PARALLAX_BLOCKS, getParallaxAttributes, getParallaxPanel } from './parallax';
import {
	STICKY_COLUMN_BLOCKS,
	getStickyColumnAttributes,
	getStickyColumnPanel,
} from './sticky-column';
import {
	SATELLITE_BLOCKS,
	getSatelliteAttributes,
	getSatellitePanel,
	withBlockSatelliteEditor,
} from './satellite';
import {
	SCROLL_REVEAL_BLOCKS,
	getScrollRevealAttributes,
	getScrollRevealPanel,
} from './scroll-reveal';

const { addFilter } = wp.hooks;
const { createHigherOrderComponent } = wp.compose;
const { Fragment } = wp.element;
const { InspectorControls } = wp.blockEditor;
const { PanelBody } = wp.components;
const { __ } = wp.i18n;

function addJetTricksAttributes( settings, name ) {
	const hasParticles = PARTICLES_BLOCKS.includes( name );
	const hasTooltip = TOOLTIP_BLOCKS.includes( name );
	const hasSatellite = SATELLITE_BLOCKS.includes( name );
	const hasParallax = PARALLAX_BLOCKS.includes( name );
	const hasStickyColumn = STICKY_COLUMN_BLOCKS.includes( name );
	const hasScrollReveal = SCROLL_REVEAL_BLOCKS.includes( name );

	if ( ! hasParticles && ! hasTooltip && ! hasSatellite && ! hasParallax && ! hasStickyColumn && ! hasScrollReveal ) {
		return settings;
	}

	const newAttrs = { ...settings.attributes };

	if ( hasParticles ) {
		Object.assign( newAttrs, getParticlesAttributes() );
	}

	if ( hasTooltip ) {
		Object.assign( newAttrs, getTooltipAttributes() );
	}

	if ( hasSatellite ) {
		Object.assign( newAttrs, getSatelliteAttributes() );
	}

	if ( hasParallax ) {
		Object.assign( newAttrs, getParallaxAttributes() );
	}

	if ( hasStickyColumn ) {
		Object.assign( newAttrs, getStickyColumnAttributes() );
	}

	if ( hasScrollReveal ) {
		Object.assign( newAttrs, getScrollRevealAttributes() );
	}

	return {
		...settings,
		attributes: newAttrs,
	};
}

const withJetTricksControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const hasParticles = PARTICLES_BLOCKS.includes( props.name );
		const hasTooltip = TOOLTIP_BLOCKS.includes( props.name );
		const hasSatellite = SATELLITE_BLOCKS.includes( props.name );
		const hasParallax = PARALLAX_BLOCKS.includes( props.name );
		const hasStickyColumn = STICKY_COLUMN_BLOCKS.includes( props.name );
		const hasScrollReveal = SCROLL_REVEAL_BLOCKS.includes( props.name );

		if ( ! hasParticles && ! hasTooltip && ! hasSatellite && ! hasParallax && ! hasStickyColumn && ! hasScrollReveal ) {
			return <BlockEdit { ...props } />;
		}

		if ( ! props.isSelected ) {
			return <BlockEdit { ...props } />;
		}

		const panels = [];

		if ( hasParticles ) {
			panels.push( getParticlesPanel( props ) );
		}

		if ( hasTooltip ) {
			panels.push( getTooltipPanel( props ) );
		}

		if ( hasSatellite ) {
			panels.push( getSatellitePanel( props ) );
		}

		if ( hasParallax ) {
			panels.push( getParallaxPanel( props ) );
		}

		if ( hasStickyColumn ) {
			panels.push( getStickyColumnPanel( props ) );
		}

		if ( hasScrollReveal ) {
			panels.push( getScrollRevealPanel( props ) );
		}

		return (
			<Fragment>
				<BlockEdit { ...props } />
				<InspectorControls key="jet-tricks">
					<PanelBody title={ __( 'JetTricks', 'jet-tricks' ) } initialOpen={ false }>
						{ panels }
					</PanelBody>
				</InspectorControls>
			</Fragment>
		);
	};
}, 'withJetTricksControls' );

/*
 *  List View indicators implementation.
 *
 * import { getActiveJetTricks } from './utils';
 * const { select, subscribe } = wp.data;
 *
 * const withJetTricksIndicators = createHigherOrderComponent( ( BlockListBlock ) => {
 * 	return ( props ) => {
 * 		const activeTricks = getActiveJetTricks( props.name, props.attributes );
 *
 * 		if ( ! activeTricks.length ) {
 * 			return <BlockListBlock { ...props } />;
 * 		}
 *
 * 		const labels = activeTricks.map( ( item ) => item.label ).join( ', ' );
 * 		const wrapperProps = {
 * 			...( props.wrapperProps || {} ),
 * 			className: [
 * 				props.wrapperProps?.className || '',
 * 				'jet-tricks-has-active-tricks',
 * 			].filter( Boolean ).join( ' ' ),
 * 			'data-jet-tricks-active-count': String( activeTricks.length ),
 * 			'data-jet-tricks-active-labels': labels,
 * 			title: props.wrapperProps?.title ? props.wrapperProps.title + ' | JetTricks: ' + labels : 'JetTricks: ' + labels,
 * 		};
 *
 * 		return <BlockListBlock { ...props } wrapperProps={ wrapperProps } />;
 * 	};
 * }, 'withJetTricksIndicators' );
 *
 * function syncJetTricksListViewIndicators() {
 * 	const getBlock = select( 'core/block-editor' )?.getBlock;
 *
 * 	if ( ! getBlock ) {
 * 		return;
 * 	}
 *
 * 	document.querySelectorAll( '.block-editor-list-view-tree [data-block]' ).forEach( ( row ) => {
 * 		const clientId = row.getAttribute( 'data-block' );
 *
 * 		if ( ! clientId ) {
 * 			return;
 * 		}
 *
 * 		const block = getBlock( clientId );
 * 		const activeTricks = block ? getActiveJetTricks( block.name, block.attributes ) : [];
 * 		const labels = activeTricks.map( ( item ) => item.label ).join( ', ' );
 * 		const button = row.querySelector( '.block-editor-list-view-block-contents' );
 *
 * 		row.classList.toggle( 'jet-tricks-has-active-tricks', !! activeTricks.length );
 * 		row.setAttribute( 'data-jet-tricks-active-count', String( activeTricks.length ) );
 *
 * 		if ( labels ) {
 * 			row.setAttribute( 'data-jet-tricks-active-labels', labels );
 * 		} else {
 * 			row.removeAttribute( 'data-jet-tricks-active-labels' );
 * 		}
 *
 * 		if ( button ) {
 * 			if ( labels ) {
 * 				button.setAttribute( 'title', 'JetTricks: ' + labels );
 * 			} else if ( button.getAttribute( 'title' )?.startsWith( 'JetTricks: ' ) ) {
 * 				button.removeAttribute( 'title' );
 * 			}
 * 		}
 * 	} );
 * }
 *
 * let syncQueued = false;
 *
 * function queueJetTricksListViewSync() {
 * 	if ( syncQueued ) {
 * 		return;
 * 	}
 *
 * 	syncQueued = true;
 * 	requestAnimationFrame( () => {
 * 		syncQueued = false;
 * 		syncJetTricksListViewIndicators();
 * 	} );
 * }
 *
 * subscribe( queueJetTricksListViewSync );
 *
 * if ( typeof MutationObserver !== 'undefined' ) {
 * 	const observer = new MutationObserver( queueJetTricksListViewSync );
 * 	observer.observe( document.body, { childList: true, subtree: true } );
 * }
 *
 * queueJetTricksListViewSync();
 */

addFilter( 'blocks.registerBlockType', 'jet-tricks/attributes', addJetTricksAttributes );
addFilter( 'editor.BlockEdit', 'jet-tricks/controls', withJetTricksControls );
addFilter( 'editor.BlockListBlock', 'jet-tricks/block-tooltip-editor', withBlockTooltipEditor );
addFilter( 'editor.BlockListBlock', 'jet-tricks/block-satellite-editor', withBlockSatelliteEditor );
// addFilter( 'editor.BlockListBlock', 'jet-tricks/indicators', withJetTricksIndicators );
