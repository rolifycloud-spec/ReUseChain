import IconPicker from './icon-picker';

const { __ } = wp.i18n;
const {
	Button,
	RangeControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} = wp.components;

const HotspotsRepeater = ( { hotspots, onChange } ) => {

	const addHotspot = () => {
		onChange( [
			...hotspots,
			{
				id: 'hotspot_' + hotspots.length,
				horizontalPosition: 50,
				verticalPosition: 50,
				hotspotIcon: {},
				hotspotText: '',
				hotspotDescription: '',
				hotspotUrl: {},
				hotspotShowOnInit: false,
			},
		] );
	};

	const removeHotspot = ( index ) => {
		onChange( hotspots.filter( ( _, i ) => i !== index ) );
	};

	const updateHotspot = ( index, field, value ) => {
		onChange( hotspots.map( ( item, i ) =>
			i === index ? { ...item, [ field ]: value } : item
		) );
	};

	return (
		<div className="jet-tricks-hotspots-repeater">
			{ hotspots.map( ( hotspot, index ) => (
				<div
					key={ hotspot.id || index }
					className="jet-tricks-hotspot-item"
					style={ {
						marginBottom: '16px',
						padding: '12px',
						border: '1px solid #ddd',
						borderRadius: '4px',
					} }
				>
					<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' } }>
						<strong>{ __( 'Hotspot', 'jet-tricks' ) } #{ index + 1 }</strong>
						<Button
							isDestructive
							isSmall
							icon="trash"
							onClick={ () => removeHotspot( index ) }
							label={ __( 'Remove', 'jet-tricks' ) }
						/>
					</div>

					<RangeControl
						label={ __( 'Horizontal Position (%)', 'jet-tricks' ) }
						value={ typeof hotspot.horizontalPosition === 'object' ? hotspot.horizontalPosition?.size : hotspot.horizontalPosition }
						onChange={ ( val ) => updateHotspot( index, 'horizontalPosition', val ) }
						min={ 0 }
						max={ 100 }
						__nextHasNoMarginBottom
					/>

					<RangeControl
						label={ __( 'Vertical Position (%)', 'jet-tricks' ) }
						value={ typeof hotspot.verticalPosition === 'object' ? hotspot.verticalPosition?.size : hotspot.verticalPosition }
						onChange={ ( val ) => updateHotspot( index, 'verticalPosition', val ) }
						min={ 0 }
						max={ 100 }
						__nextHasNoMarginBottom
					/>

					<IconPicker
						label={ __( 'Icon', 'jet-tricks' ) }
						value={ hotspot.hotspotIcon }
						onChange={ ( val ) => updateHotspot( index, 'hotspotIcon', val ) }
					/>

					<TextControl
						label={ __( 'Text', 'jet-tricks' ) }
						value={ hotspot.hotspotText || '' }
						onChange={ ( val ) => updateHotspot( index, 'hotspotText', val ) }
						__nextHasNoMarginBottom
					/>

					<TextareaControl
						label={ __( 'Tooltip Content', 'jet-tricks' ) }
						value={ hotspot.hotspotDescription || '' }
						onChange={ ( val ) => updateHotspot( index, 'hotspotDescription', val ) }
						rows={ 3 }
						__nextHasNoMarginBottom
					/>

					<TextControl
						label={ __( 'Link', 'jet-tricks' ) }
						value={ ( hotspot.hotspotUrl && hotspot.hotspotUrl.url ) || '' }
						onChange={ ( val ) => updateHotspot( index, 'hotspotUrl', { ...( hotspot.hotspotUrl || {} ), url: val || '' } ) }
						placeholder="https://"
						__nextHasNoMarginBottom
					/>
					{ ( hotspot.hotspotUrl && hotspot.hotspotUrl.url ) && (
						<ToggleControl
							label={ __( 'Open in new tab', 'jet-tricks' ) }
							checked={ !!( hotspot.hotspotUrl && hotspot.hotspotUrl.opensInNewTab ) }
							onChange={ ( val ) => updateHotspot( index, 'hotspotUrl', { ...( hotspot.hotspotUrl || {} ), opensInNewTab: val } ) }
							__nextHasNoMarginBottom
						/>
					) }

					<ToggleControl
						label={ __( 'Show Tooltip on Init', 'jet-tricks' ) }
						checked={ !! hotspot.hotspotShowOnInit }
						onChange={ ( val ) => updateHotspot( index, 'hotspotShowOnInit', val ) }
						__nextHasNoMarginBottom
					/>
				</div>
			) ) }

			<Button
				variant="secondary"
				isSmall
				onClick={ addHotspot }
				style={ { marginTop: '8px' } }
			>
				{ __( 'Add Hotspot', 'jet-tricks' ) }
			</Button>
		</div>
	);
};

export default HotspotsRepeater;
