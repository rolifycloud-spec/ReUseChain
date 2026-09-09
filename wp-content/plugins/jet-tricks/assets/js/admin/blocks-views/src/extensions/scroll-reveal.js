/** JetTricks Scroll Reveal — Gutenberg. */
import PopoverContainer from '../components/popover-container';
import ExtensionTrigger from '../components/extension-trigger';

const {
	ToggleControl,
	CheckboxControl,
	RangeControl,
	SelectControl,
	TextControl,
} = wp.components;
const { __, sprintf } = wp.i18n;

export const SCROLL_REVEAL_BLOCKS = ( window.JetTricksBlocksData && window.JetTricksBlocksData.scrollRevealBlocks ) || [];

const SR_DEVICES = [
	{ slug: 'desktop', label: __( 'Desktop', 'jet-tricks' ) },
	{ slug: 'tablet', label: __( 'Tablet', 'jet-tricks' ) },
	{ slug: 'mobile', label: __( 'Mobile', 'jet-tricks' ) },
];

const EFFECT_OPTIONS = [
	{ value: 'fade', label: __( 'Fade', 'jet-tricks' ) },
	{ value: 'fade-up', label: __( 'Fade Up', 'jet-tricks' ) },
	{ value: 'fade-down', label: __( 'Fade Down', 'jet-tricks' ) },
	{ value: 'fade-left', label: __( 'Fade Left', 'jet-tricks' ) },
	{ value: 'fade-right', label: __( 'Fade Right', 'jet-tricks' ) },
	{ value: 'zoom-in', label: __( 'Zoom In', 'jet-tricks' ) },
	{ value: 'zoom-out', label: __( 'Zoom Out', 'jet-tricks' ) },
	{ value: 'flip-up', label: __( 'Flip Up', 'jet-tricks' ) },
	{ value: 'flip-down', label: __( 'Flip Down', 'jet-tricks' ) },
	{ value: 'mask', label: __( 'Mask', 'jet-tricks' ) },
];

const MASK_DIR_OPTIONS = [
	{ value: 'up', label: __( 'Up', 'jet-tricks' ) },
	{ value: 'down', label: __( 'Down', 'jet-tricks' ) },
	{ value: 'left', label: __( 'Left', 'jet-tricks' ) },
	{ value: 'right', label: __( 'Right', 'jet-tricks' ) },
	{ value: 'center-vertical', label: __( 'Center - Vertical', 'jet-tricks' ) },
	{ value: 'center-horizontal', label: __( 'Center - Horizontal', 'jet-tricks' ) },
	{ value: 'center-all', label: __( 'Center - All sides', 'jet-tricks' ) },
];

function setDeviceInList( current, device, enabled ) {
	const list = Array.isArray( current ) ? [ ...current ] : [ 'desktop', 'tablet', 'mobile' ];
	const i = list.indexOf( device );
	if ( enabled && i === -1 ) {
		list.push( device );
	}
	if ( ! enabled && i !== -1 ) {
		list.splice( i, 1 );
	}
	return list;
}

export function getScrollRevealAttributes() {
	return {
		jetTricksScrollReveal: { type: 'boolean', default: false },
		jetTricksScrollRevealEffect: { type: 'string', default: 'fade-up' },
		jetTricksScrollRevealMaskDirection: { type: 'string', default: 'up' },
		jetTricksScrollRevealDuration: { type: 'number', default: 0.6 },
		jetTricksScrollRevealDelay: { type: 'number', default: 0 },
		jetTricksScrollRevealRootMargin: { type: 'number', default: 0 },
		jetTricksScrollRevealOnce: { type: 'boolean', default: true },
		jetTricksScrollRevealOn: {
			type: 'array',
			default: [ 'desktop', 'tablet', 'mobile' ],
		},
	};
}

export function getScrollRevealPanel( props ) {
	const { attributes, setAttributes } = props;
	const {
		jetTricksScrollReveal,
		jetTricksScrollRevealEffect,
		jetTricksScrollRevealMaskDirection,
		jetTricksScrollRevealDuration,
		jetTricksScrollRevealDelay,
		jetTricksScrollRevealRootMargin,
		jetTricksScrollRevealOnce,
		jetTricksScrollRevealOn,
	} = attributes;

	const onList = Array.isArray( jetTricksScrollRevealOn ) && jetTricksScrollRevealOn.length
		? jetTricksScrollRevealOn
		: [ 'desktop', 'tablet', 'mobile' ];

	const isMask = jetTricksScrollRevealEffect === 'mask';

	return (
		<PopoverContainer
			label={ __( 'Scroll Reveal Settings', 'jet-tricks' ) }
			trigger={ <ExtensionTrigger label={ __( 'Scroll Reveal', 'jet-tricks' ) } isActive={ !! jetTricksScrollReveal } /> }
		>
			<>
				<ToggleControl
					label={ __( 'Scroll Reveal', 'jet-tricks' ) }
					checked={ !! jetTricksScrollReveal }
					onChange={ ( value ) => setAttributes( { jetTricksScrollReveal: !! value } ) }
				/>
				{ jetTricksScrollReveal && (
					<SelectControl
						label={ __( 'Effect', 'jet-tricks' ) }
						value={ jetTricksScrollRevealEffect || 'fade-up' }
						options={ EFFECT_OPTIONS }
						onChange={ ( value ) => setAttributes( { jetTricksScrollRevealEffect: value } ) }
					/>
				) }
				{ jetTricksScrollReveal && isMask && (
					<SelectControl
						label={ __( 'Mask direction', 'jet-tricks' ) }
						value={ jetTricksScrollRevealMaskDirection || 'up' }
						options={ MASK_DIR_OPTIONS }
						onChange={ ( value ) => setAttributes( { jetTricksScrollRevealMaskDirection: value } ) }
					/>
				) }
				{ jetTricksScrollReveal && (
					<RangeControl
						label={ __( 'Duration (s)', 'jet-tricks' ) }
						value={ jetTricksScrollRevealDuration ?? 0.6 }
						onChange={ ( value ) => setAttributes( { jetTricksScrollRevealDuration: value ?? 0.6 } ) }
						min={ 0.1 }
						max={ 3 }
						step={ 0.05 }
					/>
				) }
				{ jetTricksScrollReveal && (
					<RangeControl
						label={ __( 'Delay (s)', 'jet-tricks' ) }
						value={ jetTricksScrollRevealDelay ?? 0 }
						onChange={ ( value ) => setAttributes( { jetTricksScrollRevealDelay: value ?? 0 } ) }
						min={ 0 }
						max={ 15 }
						step={ 0.05 }
					/>
				) }
				{ jetTricksScrollReveal && (
					<RangeControl
						label={ __( 'Viewport offset (px)', 'jet-tricks' ) }
						value={ jetTricksScrollRevealRootMargin ?? 0 }
						onChange={ ( value ) => setAttributes( { jetTricksScrollRevealRootMargin: value ?? 0 } ) }
						min={ -200 }
						max={ 200 }
						step={ 1 }
						help={ __( 'When the animation starts relative to the viewport. Negative starts earlier.', 'jet-tricks' ) }
					/>
				) }
				{ jetTricksScrollReveal && (
					<ToggleControl
						label={ __( 'Animate once', 'jet-tricks' ) }
						checked={ jetTricksScrollRevealOnce !== false }
						onChange={ ( value ) => setAttributes( { jetTricksScrollRevealOnce: !! value } ) }
					/>
				) }
				{ jetTricksScrollReveal && SR_DEVICES.map( ( { slug, label } ) => (
					<CheckboxControl
						key={ 'jet-tricks-sr-on-' + slug }
						className="jet-tricks-device-checkbox"
						label={ sprintf( __( 'Active on %s', 'jet-tricks' ), label ) }
						checked={ onList.indexOf( slug ) !== -1 }
						onChange={ ( checked ) =>
							setAttributes( {
								jetTricksScrollRevealOn: setDeviceInList( onList, slug, checked ),
							} ) }
					/>
				) ) }
			</>
		</PopoverContainer>
	);
}
