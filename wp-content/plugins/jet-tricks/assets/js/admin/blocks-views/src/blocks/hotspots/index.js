import metadata from './block.json';
import HotspotsRepeater from '../../components/hotspots-repeater';

const { __ } = wp.i18n;
const { icon } = metadata;
const { registerBlockType } = wp.blocks;
const { serverSideRender: ServerSideRender } = wp;

const {
	InspectorControls,
	useBlockProps
} = wp.blockEditor;

const { useDispatch } = wp.data;
const { useEffect, useRef, useCallback } = wp.element;

const {
	PanelBody,
	SelectControl,
	ToggleControl,
	RangeControl,
	Button,
} = wp.components;

const { MediaUpload, MediaUploadCheck } = wp.blockEditor;

const ImagePicker = ( { value, onChange } ) => {
	const imgValue = value || {};
	const hasImage = imgValue.url && Object.keys( imgValue ).length > 0;

	return (
		<MediaUploadCheck>
			{ hasImage && (
				<div className="preview-jet-tricks-media" style={ { marginBottom: '8px' } }>
					<img src={ imgValue.url } alt="" style={ { maxWidth: '100%', height: 'auto' } } />
				</div>
			) }
			<MediaUpload
				allowedTypes={ [ 'image' ] }
				value={ hasImage ? imgValue.id : undefined }
				onSelect={ ( media ) => onChange( { id: media.id, url: media.url } ) }
				render={ ( { open } ) => (
					<Button
						isSecondary
						icon={ hasImage ? 'edit' : 'format-image' }
						onClick={ open }
					>
						{ hasImage ? __( 'Replace Image', 'jet-tricks' ) : __( 'Choose Image', 'jet-tricks' ) }
					</Button>
				) }
			/>
		</MediaUploadCheck>
	);
};

function initHotspotsTooltips( container ) {
	if ( ! container || ! window.tippy ) return;
	const target = container.querySelector( '.jet-hotspots' );
	if ( ! target ) return;
	const settingsData = target.getAttribute( 'data-settings' );
	const settings = settingsData ? JSON.parse( settingsData ) : {};
	const items = target.querySelectorAll( '.jet-hotspots__item' );
	items.forEach( ( el ) => {
		if ( el._tippy ) el._tippy.destroy();
		const content = el.getAttribute( 'data-tippy-content' );
		const horizontal = el.getAttribute( 'data-horizontal-position' ) || 50;
		const vertical = el.getAttribute( 'data-vertical-position' ) || 50;
		el.style.left = horizontal + '%';
		el.style.top = vertical + '%';
		const options = {
			content,
			arrow: !! settings.tooltipArrow,
			placement: settings.tooltipPlacement || 'top',
			trigger: 'manual',
			appendTo: target,
			hideOnClick: false,
			maxWidth: 'none',
			offset: [ 0, ( settings.tooltipDistance && settings.tooltipDistance.size ) || 15 ],
			allowHTML: true,
			interactive: !! settings.tooltipInteractive,
			onShow: () => el.classList.add( 'jet-hotspots__item--active' ),
			onHidden: () => el.classList.remove( 'jet-hotspots__item--active' ),
		};
		window.tippy( el, options );
		if ( el._tippy ) el._tippy.show();
	} );
}

registerBlockType( metadata, {
	icon: <span dangerouslySetInnerHTML={ { __html: icon } }></span>,
	edit: props => {
		const { selectBlock } = useDispatch( 'core/block-editor' );
		const { attributes, setAttributes, name, clientId } = props;
		const previewRef = useRef( null );
		const blockProps = useBlockProps( {
			onClick: () => selectBlock( clientId ),
			ref: previewRef,
		} );

		useEffect( () => {
			const el = previewRef.current;
			if ( ! el ) return;
			const ob = new MutationObserver( ( mutations ) => {
				const hasNonTippy = mutations.some( ( m ) => {
					if ( ! m.addedNodes.length ) return false;
					for ( let i = 0; i < m.addedNodes.length; i++ ) {
						const n = m.addedNodes[ i ];
						if ( n.nodeType !== 1 ) continue;
						if ( ! n.closest || ! n.closest( '[data-tippy-root]' ) ) return true;
					}
					return false;
				} );
				if ( hasNonTippy && el.querySelector( '.jet-hotspots' ) ) {
					ob.disconnect();
					initHotspotsTooltips( el );
					setTimeout( () => ob.observe( el, { childList: true, subtree: true } ), 100 );
				}
			} );
			if ( el.querySelector( '.jet-hotspots' ) ) {
				initHotspotsTooltips( el );
			}
			ob.observe( el, { childList: true, subtree: true } );
			return () => ob.disconnect();
		}, [ attributes ] );

		return [
			props.isSelected && (
				<InspectorControls key="inspector">
					<PanelBody title={ __( 'Image', 'jet-tricks' ) } initialOpen>
						<div style={ { marginBottom: '16px' } }>
							<ImagePicker
								value={ attributes.image }
								onChange={ ( image ) => setAttributes( { image } ) }
							/>
						</div>
						{ window.JetTricksBlocksData && window.JetTricksBlocksData.imageSizes && (
							<SelectControl
								label={ __( 'Image Size', 'jet-tricks' ) }
								value={ attributes.imageSize }
								options={ window.JetTricksBlocksData.imageSizes }
								onChange={ ( imageSize ) => setAttributes( { imageSize } ) }
								__nextHasNoMarginBottom
							/>
						) }
					</PanelBody>

					<PanelBody title={ __( 'Hotspots', 'jet-tricks' ) } initialOpen>
						<HotspotsRepeater
							hotspots={ attributes.hotspots }
							onChange={ ( hotspots ) => setAttributes( { hotspots } ) }
						/>
						<div className="jet-tricks-hotspot-animation-control" style={ { marginTop: '16px' } }>
							<SelectControl
								label={ __( 'Hotspot Animation', 'jet-tricks' ) }
								value={ attributes.hotspotsAnimation || 'pulse' }
								options={ [
									{ label: __( 'None', 'jet-tricks' ), value: 'none' },
									{ label: __( 'Flash', 'jet-tricks' ), value: 'flash' },
									{ label: __( 'Pulse', 'jet-tricks' ), value: 'pulse' },
									{ label: __( 'Shake', 'jet-tricks' ), value: 'shake' },
									{ label: __( 'Tada', 'jet-tricks' ), value: 'tada' },
									{ label: __( 'Rubber', 'jet-tricks' ), value: 'rubber' },
									{ label: __( 'Swing', 'jet-tricks' ), value: 'swing' },
								] }
								onChange={ ( hotspotsAnimation ) => setAttributes( { hotspotsAnimation } ) }
								__nextHasNoMarginBottom
							/>
						</div>
					</PanelBody>

					<PanelBody title={ __( 'Tooltip', 'jet-tricks' ) }>
						<ToggleControl
							label={ __( 'Show All Tooltips on Init', 'jet-tricks' ) }
							checked={ attributes.tooltipShowOnInit }
							onChange={ ( tooltipShowOnInit ) => setAttributes( { tooltipShowOnInit } ) }
							__nextHasNoMarginBottom
						/>

						<ToggleControl
							label={ __( 'Interactive Tooltip', 'jet-tricks' ) }
							help={ __( 'Allow interaction with elements inside tooltip', 'jet-tricks' ) }
							checked={ attributes.tooltipInteractive }
							onChange={ ( tooltipInteractive ) => setAttributes( { tooltipInteractive } ) }
							__nextHasNoMarginBottom
						/>

						<SelectControl
							label={ __( 'Placement', 'jet-tricks' ) }
							value={ attributes.tooltipPlacement }
							options={ [
								{ label: __( 'Top Start', 'jet-tricks' ), value: 'top-start' },
								{ label: __( 'Top', 'jet-tricks' ), value: 'top' },
								{ label: __( 'Top End', 'jet-tricks' ), value: 'top-end' },
								{ label: __( 'Right Start', 'jet-tricks' ), value: 'right-start' },
								{ label: __( 'Right', 'jet-tricks' ), value: 'right' },
								{ label: __( 'Right End', 'jet-tricks' ), value: 'right-end' },
								{ label: __( 'Bottom Start', 'jet-tricks' ), value: 'bottom-start' },
								{ label: __( 'Bottom', 'jet-tricks' ), value: 'bottom' },
								{ label: __( 'Bottom End', 'jet-tricks' ), value: 'bottom-end' },
								{ label: __( 'Left Start', 'jet-tricks' ), value: 'left-start' },
								{ label: __( 'Left', 'jet-tricks' ), value: 'left' },
								{ label: __( 'Left End', 'jet-tricks' ), value: 'left-end' },
							] }
							onChange={ ( tooltipPlacement ) => setAttributes( { tooltipPlacement } ) }
							__nextHasNoMarginBottom
						/>

						{ attributes.tooltipTrigger !== 'manual' && (
							<>
								<SelectControl
									label={ __( 'Animation', 'jet-tricks' ) }
									value={ attributes.tooltipAnimation }
									options={ [
										{ label: __( 'Fade', 'jet-tricks' ), value: 'fade' },
										{ label: __( 'Shift Away', 'jet-tricks' ), value: 'shift-away' },
										{ label: __( 'Shift Toward', 'jet-tricks' ), value: 'shift-toward' },
										{ label: __( 'Scale', 'jet-tricks' ), value: 'scale' },
										{ label: __( 'Perspective', 'jet-tricks' ), value: 'perspective' },
									] }
									onChange={ ( tooltipAnimation ) => setAttributes( { tooltipAnimation } ) }
									__nextHasNoMarginBottom
								/>

								<RangeControl
									label={ __( 'Animation Delay (ms)', 'jet-tricks' ) }
									value={ attributes.tooltipDelay?.size ?? 0 }
									onChange={ ( size ) => setAttributes( {
										tooltipDelay: {
											size,
											unit: 'ms',
										},
									} ) }
									min={ 0 }
									max={ 1000 }
									step={ 100 }
									__nextHasNoMarginBottom
								/>

								<RangeControl
									label={ __( 'Appearance Duration (ms)', 'jet-tricks' ) }
									value={ attributes.tooltipShowDuration?.size ?? 500 }
									onChange={ ( size ) => setAttributes( {
										tooltipShowDuration: {
											size,
											unit: 'ms',
										},
									} ) }
									min={ 100 }
									max={ 1000 }
									step={ 100 }
									__nextHasNoMarginBottom
								/>

								<RangeControl
									label={ __( 'Disappearance Duration (ms)', 'jet-tricks' ) }
									value={ attributes.tooltipHideDuration?.size ?? 300 }
									onChange={ ( size ) => setAttributes( {
										tooltipHideDuration: {
											size,
											unit: 'ms',
										},
									} ) }
									min={ 100 }
									max={ 1000 }
									step={ 100 }
									__nextHasNoMarginBottom
								/>
							</>
						) }

						<SelectControl
							label={ __( 'Trigger', 'jet-tricks' ) }
							value={ attributes.tooltipTrigger }
							options={ [
								{ label: __( 'None', 'jet-tricks' ), value: 'manual' },
								{ label: __( 'Mouse Enter', 'jet-tricks' ), value: 'mouseenter' },
								{ label: __( 'Click', 'jet-tricks' ), value: 'click' },
								{ label: __( 'Focus', 'jet-tricks' ), value: 'focus' },
								{ label: __( 'Mouse Enter + Click', 'jet-tricks' ), value: 'mouseenter click' },
								{ label: __( 'Mouse Enter + Focus', 'jet-tricks' ), value: 'mouseenter focus' },
							] }
							onChange={ ( tooltipTrigger ) => setAttributes( { tooltipTrigger } ) }
							__nextHasNoMarginBottom
						/>

						<ToggleControl
							label={ __( 'Use Arrow', 'jet-tricks' ) }
							checked={ attributes.tooltipArrow }
							onChange={ ( tooltipArrow ) => setAttributes( { tooltipArrow } ) }
							__nextHasNoMarginBottom
						/>

						<RangeControl
							label={ __( 'Distance (px)', 'jet-tricks' ) }
							value={ attributes.tooltipDistance?.size ?? 15 }
							onChange={ ( size ) => setAttributes( {
								tooltipDistance: {
									size,
									unit: 'px',
								},
							} ) }
							min={ 0 }
							max={ 100 }
							__nextHasNoMarginBottom
						/>
					</PanelBody>
				</InspectorControls>
			),

			<div { ...blockProps } key="preview">
				<div className="jet-tricks-hotspots jet-tricks-hotspots-editor">
					<ServerSideRender
						block={ name }
						attributes={ attributes }
						httpMethod={ 'POST' }
					/>
				</div>
			</div>,
		];
	},
	save: props => {
		return null;
	}
} );
