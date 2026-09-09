import { PopoverContainerContext } from './popover-container';

const { __ } = wp.i18n;
const { Button } = wp.components;
const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { useContext } = wp.element;

const IconPicker = ( { label, value, onChange } ) => {

	const iconValue = value || {};
	const hasIcon = iconValue.url && Object.keys( iconValue ).length > 0;
	const { closePopover } = useContext( PopoverContainerContext );

	return (
		<div className="components-base-control">
			{ label && (
				<label className="components-base-control__label">{ label }</label>
			) }

			<MediaUploadCheck>
				{ hasIcon &&
					<div className="preview-jet-tricks-media preview-jet-tricks-media-icon">
						<Button
							className="jet-remove-button"
							isPrimary
							icon="no-alt"
							onClick={ () => onChange( {} ) }
						></Button>
						<img src={ iconValue.url } width="100%" height="auto" />
					</div>
				}
				<div className="components-base-control jet-media-control">
					<MediaUpload
						allowedTypes={ [ 'image/svg+xml' ] }
						value={ hasIcon ? iconValue.id : undefined }
						onSelect={ ( media ) => {
							onChange( {
								id: media.id,
								url: media.url,
							} );
						} }
						render={ ( { open } ) => (
							<Button
								isSecondary
								icon="edit"
								onClick={ () => {
									closePopover();
									setTimeout( open, 0 );
								} }
							>{ label || __( 'Select Icon', 'jet-tricks' ) }</Button>
						) }
					/>
				</div>
			</MediaUploadCheck>
		</div>
	);
};

export default IconPicker;
