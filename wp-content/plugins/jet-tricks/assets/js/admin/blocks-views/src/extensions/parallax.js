/** JetTricks Parallax — Gutenberg. */
import PopoverContainer from '../components/popover-container';
import ExtensionTrigger from '../components/extension-trigger';

const {
	ToggleControl,
	CheckboxControl,
	RangeControl,
} = wp.components;
const { __, sprintf } = wp.i18n;

export const PARALLAX_BLOCKS = ( window.JetTricksBlocksData && window.JetTricksBlocksData.parallaxBlocks ) || [];

const PARALLAX_DEVICES = [
	{ slug: 'desktop', label: __( 'Desktop', 'jet-tricks' ) },
	{ slug: 'tablet', label: __( 'Tablet', 'jet-tricks' ) },
	{ slug: 'mobile', label: __( 'Mobile', 'jet-tricks' ) },
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

export function getParallaxAttributes() {
	return {
		jetTricksParallax: { type: 'boolean', default: false },
		jetTricksParallaxSpeed: { type: 'number', default: 50 },
		jetTricksParallaxInvert: { type: 'boolean', default: false },
		jetTricksParallaxOn: {
			type: 'array',
			default: [ 'desktop', 'tablet' ],
		},
	};
}

export function getParallaxPanel( props ) {
	const { attributes, setAttributes } = props;
	const {
		jetTricksParallax,
		jetTricksParallaxSpeed,
		jetTricksParallaxInvert,
		jetTricksParallaxOn,
	} = attributes;

	const onList = Array.isArray( jetTricksParallaxOn ) && jetTricksParallaxOn.length
		? jetTricksParallaxOn
		: [ 'desktop', 'tablet' ];

	return (
		<PopoverContainer
			label={ __( 'Parallax Settings', 'jet-tricks' ) }
			trigger={ <ExtensionTrigger label={ __( 'Parallax', 'jet-tricks' ) } isActive={ !! jetTricksParallax } /> }
		>
			<>
				<ToggleControl
					label={ __( 'Use Parallax', 'jet-tricks' ) }
					checked={ !! jetTricksParallax }
					onChange={ ( value ) => setAttributes( { jetTricksParallax: !! value } ) }
				/>
				{ jetTricksParallax && (
					<RangeControl
						label={ __( 'Parallax Speed(%)', 'jet-tricks' ) }
						value={ jetTricksParallaxSpeed ?? 50 }
						onChange={ ( value ) => setAttributes( { jetTricksParallaxSpeed: value ?? 50 } ) }
						min={ 1 }
						max={ 100 }
						step={ 1 }
					/>
				) }
				{ jetTricksParallax && (
					<ToggleControl
						label={ __( 'Invert', 'jet-tricks' ) }
						checked={ !! jetTricksParallaxInvert }
						onChange={ ( value ) => setAttributes( { jetTricksParallaxInvert: !! value } ) }
					/>
				) }
				{ jetTricksParallax && PARALLAX_DEVICES.map( ( { slug, label } ) => (
					<CheckboxControl
						key={ 'jet-tricks-parallax-on-' + slug }
						className="jet-tricks-device-checkbox"
						label={ sprintf( __( 'Active on %s', 'jet-tricks' ), label ) }
						checked={ onList.indexOf( slug ) !== -1 }
						onChange={ ( checked ) =>
							setAttributes( {
								jetTricksParallaxOn: setDeviceInList( onList, slug, checked ),
							} ) }
					/>
				) ) }
			</>
		</PopoverContainer>
	);
}
