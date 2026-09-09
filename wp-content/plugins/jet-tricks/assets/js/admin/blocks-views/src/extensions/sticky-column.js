/** JetTricks Sticky column — Gutenberg (core/column). */
import PopoverContainer from '../components/popover-container';
import ExtensionTrigger from '../components/extension-trigger';

const {
	ToggleControl,
	CheckboxControl,
	RangeControl,
	TextControl,
	SelectControl,
} = wp.components;
const { __, sprintf } = wp.i18n;

export const STICKY_COLUMN_BLOCKS = ( window.JetTricksBlocksData && window.JetTricksBlocksData.stickyColumnBlocks ) || [];

const STICKY_DEVICES = [
	{ slug: 'desktop', label: __( 'Desktop', 'jet-tricks' ) },
	{ slug: 'tablet', label: __( 'Tablet', 'jet-tricks' ) },
	{ slug: 'mobile', label: __( 'Mobile', 'jet-tricks' ) },
];

const STICKY_ALIGN_OPTIONS = [
	{ value: 'top', label: __( 'Top', 'jet-tricks' ) },
	{ value: 'center', label: __( 'Center', 'jet-tricks' ) },
	{ value: 'bottom', label: __( 'Bottom', 'jet-tricks' ) },
];

function makeStickyInstanceId() {
	try {
		if ( typeof crypto !== 'undefined' && crypto.randomUUID ) {
			return 'jt-' + crypto.randomUUID().replace( /-/g, '' );
		}
	} catch ( e ) { /* noop */ }
	return 'jt-' + Date.now().toString( 36 ) + Math.random().toString( 36 ).slice( 2, 10 );
}

export function getStickyColumnAttributes() {
	return {
		jetTricksStickyColumn: { type: 'boolean', default: false },
		jetTricksStickyInstanceId: { type: 'string', default: '' },
		jetTricksStickyTop: { type: 'number', default: 50 },
		jetTricksStickyBottom: { type: 'number', default: 50 },
		jetTricksStickyAlign: { type: 'string', default: 'top' },
		jetTricksStickyOn: {
			type: 'array',
			default: [ 'desktop', 'tablet' ],
		},
		jetTricksStickyZIndex: { type: 'number', default: 1100 },
	};
}

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

export function getStickyColumnPanel( props ) {
	const { attributes, setAttributes } = props;
	const {
		jetTricksStickyColumn,
		jetTricksStickyTop,
		jetTricksStickyBottom,
		jetTricksStickyAlign,
		jetTricksStickyOn,
		jetTricksStickyZIndex,
	} = attributes;

	const onList = Array.isArray( jetTricksStickyOn ) && jetTricksStickyOn.length
		? jetTricksStickyOn
		: [ 'desktop', 'tablet' ];

	const onToggleSticky = ( value ) => {
		const next = { jetTricksStickyColumn: !! value };
		if ( value && ! attributes.jetTricksStickyInstanceId ) {
			next.jetTricksStickyInstanceId = makeStickyInstanceId();
		}
		setAttributes( next );
	};

	return (
		<PopoverContainer
			label={ __( 'Sticky Column Settings', 'jet-tricks' ) }
			trigger={ <ExtensionTrigger label={ __( 'Sticky column', 'jet-tricks' ) } isActive={ !! jetTricksStickyColumn } /> }
		>
			<>
				<ToggleControl
					label={ __( 'Sticky Column', 'jet-tricks' ) }
					checked={ !! jetTricksStickyColumn }
					onChange={ onToggleSticky }
				/>
				{ jetTricksStickyColumn && (
					<SelectControl
						label={ __( 'Sticky Align', 'jet-tricks' ) }
						value={ jetTricksStickyAlign || 'top' }
						options={ STICKY_ALIGN_OPTIONS }
						onChange={ ( value ) => setAttributes( { jetTricksStickyAlign: value || 'top' } ) }
					/>
				) }
				{ jetTricksStickyColumn && (
					<RangeControl
						label={ __( 'Top spacing (px)', 'jet-tricks' ) }
						value={ jetTricksStickyTop ?? 50 }
						onChange={ ( value ) => setAttributes( { jetTricksStickyTop: value ?? 50 } ) }
						min={ 0 }
						max={ 500 }
						step={ 1 }
					/>
				) }
				{ jetTricksStickyColumn && (
					<RangeControl
						label={ __( 'Bottom spacing (px)', 'jet-tricks' ) }
						value={ jetTricksStickyBottom ?? 50 }
						onChange={ ( value ) => setAttributes( { jetTricksStickyBottom: value ?? 50 } ) }
						min={ 0 }
						max={ 500 }
						step={ 1 }
					/>
				) }
				{ jetTricksStickyColumn && (
					<TextControl
						label={ __( 'Z-index', 'jet-tricks' ) }
						type="number"
						value={ jetTricksStickyZIndex !== undefined && jetTricksStickyZIndex !== null
							? String( jetTricksStickyZIndex )
							: '1100' }
						onChange={ ( value ) => {
							const trimmed = String( value ).trim();
							if ( trimmed === '' ) {
								setAttributes( { jetTricksStickyZIndex: 1100 } );
								return;
							}
							const n = parseInt( trimmed, 10 );
							if ( Number.isNaN( n ) ) {
								return;
							}
							setAttributes( { jetTricksStickyZIndex: n } );
						} }
						min={ 0 }
						max={ 10000 }
						__nextHasNoMarginBottom={ true }
					/>
				) }
				{ jetTricksStickyColumn && STICKY_DEVICES.map( ( { slug, label } ) => (
					<CheckboxControl
						key={ 'jet-tricks-sticky-on-' + slug }
						className="jet-tricks-device-checkbox"
						label={ sprintf( __( 'Sticky on %s', 'jet-tricks' ), label ) }
						checked={ onList.indexOf( slug ) !== -1 }
						onChange={ ( checked ) =>
							setAttributes( {
								jetTricksStickyOn: setDeviceInList( onList, slug, checked ),
							} ) }
					/>
				) ) }
			</>
		</PopoverContainer>
	);
}
