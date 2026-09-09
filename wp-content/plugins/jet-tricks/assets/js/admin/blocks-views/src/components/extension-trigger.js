const { __ } = wp.i18n;

const ExtensionTrigger = ( { label, isActive, activeLabel } ) => (
	<>
		<style>
			{ `
				.jet-tricks-extension-trigger {
					cursor: pointer;
					display: flex;
					width: 100%;
					font-weight: 600;
					align-items: center;
					justify-content: space-between;
					padding: 8px 0;
				}

				.jet-tricks-extension-trigger svg {
					fill: currentColor;
				}

				.jet-tricks-extension-trigger:hover {
					color: var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9));
				}

				.jet-tricks-extension-trigger__icon {
					width: 24px;
					height: 24px;
					display: inline-flex;
					align-items: center;
					justify-content: center;
					flex: 0 0 24px;
				}
			` }
		</style>
		<div className="jet-tricks-extension-trigger">
			<span>{ label }</span>
			<span className="jet-tricks-extension-trigger__icon">
				{ isActive ? (
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" title={ activeLabel }>
						<path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"></path>
					</svg>
				) : (
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
						<path d="M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z"></path>
					</svg>
				) }
			</span>
		</div>
	</>
);

ExtensionTrigger.defaultProps = {
	label: __( 'Settings', 'jet-tricks' ),
	isActive: false,
	activeLabel: __( 'On', 'jet-tricks' ),
};

export default ExtensionTrigger;
