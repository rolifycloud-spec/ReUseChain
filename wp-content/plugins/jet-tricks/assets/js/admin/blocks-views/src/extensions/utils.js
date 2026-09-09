/** JetTricks Gutenberg extension helpers. */
import { PARTICLES_BLOCKS } from './particles';
import { TOOLTIP_BLOCKS } from './tooltip';
import { PARALLAX_BLOCKS } from './parallax';
import { STICKY_COLUMN_BLOCKS } from './sticky-column';
import { SATELLITE_BLOCKS } from './satellite';
import { SCROLL_REVEAL_BLOCKS } from './scroll-reveal';

const { __ } = wp.i18n;

const EXTENSIONS = [
	{
		key: 'particles',
		label: __( 'Particles', 'jet-tricks' ),
		blocks: PARTICLES_BLOCKS,
		isActive: ( attributes ) => !! attributes?.jetTricksParticles,
	},
	{
		key: 'tooltip',
		label: __( 'Tooltip', 'jet-tricks' ),
		blocks: TOOLTIP_BLOCKS,
		isActive: ( attributes ) => !! attributes?.jetTricksTooltip,
	},
	{
		key: 'satellite',
		label: __( 'Satellite', 'jet-tricks' ),
		blocks: SATELLITE_BLOCKS,
		isActive: ( attributes ) => !! attributes?.jetTricksSatellite,
	},
	{
		key: 'parallax',
		label: __( 'Parallax', 'jet-tricks' ),
		blocks: PARALLAX_BLOCKS,
		isActive: ( attributes ) => !! attributes?.jetTricksParallax,
	},
	{
		key: 'sticky-column',
		label: __( 'Sticky column', 'jet-tricks' ),
		blocks: STICKY_COLUMN_BLOCKS,
		isActive: ( attributes ) => !! attributes?.jetTricksStickyColumn,
	},
	{
		key: 'scroll-reveal',
		label: __( 'Scroll Reveal', 'jet-tricks' ),
		blocks: SCROLL_REVEAL_BLOCKS,
		isActive: ( attributes ) => !! attributes?.jetTricksScrollReveal,
	},
];

export function getSupportedJetTricks( blockName ) {
	return EXTENSIONS.filter( ( extension ) => extension.blocks.includes( blockName ) );
}

export function getActiveJetTricks( blockName, attributes ) {
	return getSupportedJetTricks( blockName ).filter( ( extension ) => extension.isActive( attributes ) );
}

