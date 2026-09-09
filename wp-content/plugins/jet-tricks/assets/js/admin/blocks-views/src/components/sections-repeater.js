const { __ } = wp.i18n;

const {
	TextControl,
	Button,
	Notice,
} = wp.components;

const SectionsRepeater = ( { sections, onChange } ) => {

	const addSection = () => {
		onChange( [
			...sections,
			{ id: 'section_' + sections.length, sectionId: '' },
		] );
	};

	const removeSection = ( index ) => {
		onChange( sections.filter( ( _, i ) => i !== index ) );
	};

	const updateSection = ( index, value ) => {
		onChange( sections.map( ( section, i ) =>
			i === index ? { ...section, sectionId: value } : section
		) );
	};

	return (
		<div className="jet-tricks-sections-repeater">
			<div style={ { marginBottom: '12px' } }>
				<Notice
					status="info"
					isDismissible={ false }
				>
					{ __( 'Enter the block anchor you want to hide/show. You can set it in block settings → Advanced → HTML Anchor.', 'jet-tricks' ) }
				</Notice>
			</div>

			{ sections.map( ( section, index ) => (
				<div
					key={ section.id || index }
					style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px' } }
				>
					<TextControl
						label={ __( 'Block Anchor', 'jet-tricks' ) + ' #' + ( index + 1 ) }
						value={ section.sectionId || '' }
						onChange={ ( val ) => updateSection( index, val ) }
						__nextHasNoMarginBottom
						style={ { flex: 1 } }
					/>
					<Button
						isDestructive
						isSmall
						icon="trash"
						onClick={ () => removeSection( index ) }
						label={ __( 'Remove', 'jet-tricks' ) }
					/>
				</div>
			) ) }

			<Button
				variant="secondary"
				isSmall
				onClick={ addSection }
				style={ { marginTop: '8px' } }
			>
				{ __( 'Add Anchor', 'jet-tricks' ) }
			</Button>
		</div>
	);
};

export default SectionsRepeater;
