import metadata from './block.json';
import SectionsRepeater from '../../components/sections-repeater';
import IconPicker from '../../components/icon-picker';

const { __ } = wp.i18n;
const { icon } = metadata;
const { registerBlockType } = wp.blocks;
const { serverSideRender: ServerSideRender } = wp;

const {
	InspectorControls,
	useBlockProps
} = wp.blockEditor;

const {
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
} = wp.components;

registerBlockType( metadata, {
	icon: <span dangerouslySetInnerHTML={ { __html: icon } }></span>,
	edit: props => {
		const blockProps = useBlockProps();
		const {
			attributes,
			setAttributes,
			name,
		} = props;

		return [
			props.isSelected && (
				<InspectorControls key="inspector">
						<PanelBody title={ __( 'Block Anchors', 'jet-tricks' ) } initialOpen>
							<SectionsRepeater
								sections={ attributes.sections }
								onChange={ ( sections ) => setAttributes( { sections } ) }
							/>
						</PanelBody>

						<PanelBody title={ __( 'Settings', 'jet-tricks' ) } initialOpen>
							<TextControl
								label={ __( 'Button Label', 'jet-tricks' ) }
								value={ attributes.buttonLabel }
								onChange={ ( buttonLabel ) => setAttributes( { buttonLabel } ) }
								__nextHasNoMarginBottom
							/>

							<IconPicker
								label={ __( 'Button Icon (SVG)', 'jet-tricks' ) }
								value={ attributes.buttonIcon }
								onChange={ ( buttonIcon ) => setAttributes( { buttonIcon } ) }
							/>

							<ToggleControl
								label={ __( 'Show All Sections', 'jet-tricks' ) }
								checked={ attributes.showAll }
								onChange={ ( showAll ) => setAttributes( { showAll } ) }
								__nextHasNoMarginBottom
							/>

							<SelectControl
								label={ __( 'Show Effect', 'jet-tricks' ) }
								value={ attributes.showEffect }
								options={ [
									{ label: __( 'None', 'jet-tricks' ), value: 'none' },
									{ label: __( 'Fade', 'jet-tricks' ), value: 'fade' },
									{ label: __( 'Zoom In', 'jet-tricks' ), value: 'zoom-in' },
									{ label: __( 'Zoom Out', 'jet-tricks' ), value: 'zoom-out' },
									{ label: __( 'Move Up', 'jet-tricks' ), value: 'move-up' },
									{ label: __( 'Fall Perspective', 'jet-tricks' ), value: 'fall-perspective' },
								] }
								onChange={ ( showEffect ) => setAttributes( { showEffect } ) }
								__nextHasNoMarginBottom
							/>

							<ToggleControl
								label={ __( 'Read Less Button', 'jet-tricks' ) }
								checked={ attributes.readLess }
								onChange={ ( readLess ) => setAttributes( { readLess } ) }
								__nextHasNoMarginBottom
							/>

							{ attributes.readLess && (
								<TextControl
									label={ __( 'Read Less Label', 'jet-tricks' ) }
									value={ attributes.readLessLabel }
									onChange={ ( readLessLabel ) => setAttributes( { readLessLabel } ) }
									__nextHasNoMarginBottom
								/>
							) }

							{ attributes.readLess && (
								<IconPicker
									label={ __( 'Read Less Icon (SVG)', 'jet-tricks' ) }
									value={ attributes.readLessIcon }
									onChange={ ( readLessIcon ) => setAttributes( { readLessIcon } ) }
								/>
							) }

							{ attributes.readLess && (
								<ToggleControl
									label={ __( 'Hide All Sections', 'jet-tricks' ) }
									checked={ attributes.hideAll }
									onChange={ ( hideAll ) => setAttributes( { hideAll } ) }
									__nextHasNoMarginBottom
								/>
							) }

							{ attributes.readLess && (
								<SelectControl
									label={ __( 'Hide Effect', 'jet-tricks' ) }
									value={ attributes.hideEffect }
									options={ [
										{ label: __( 'None', 'jet-tricks' ), value: 'none' },
										{ label: __( 'Fade', 'jet-tricks' ), value: 'fade' },
										{ label: __( 'Zoom In', 'jet-tricks' ), value: 'zoom-in' },
										{ label: __( 'Zoom Out', 'jet-tricks' ), value: 'zoom-out' },
										{ label: __( 'Move Down', 'jet-tricks' ), value: 'move-down' },
										{ label: __( 'Fall Perspective', 'jet-tricks' ), value: 'fall-perspective' },
									] }
									onChange={ ( hideEffect ) => setAttributes( { hideEffect } ) }
									__nextHasNoMarginBottom
								/>
							) }
						</PanelBody>
					</InspectorControls>
			),

			<div { ...blockProps } key="preview">
				<div className="jet-tricks-view-more">
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
