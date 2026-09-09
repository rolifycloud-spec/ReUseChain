/** JetTricks Particles — Gutenberg. */
import PopoverContainer from '../components/popover-container';
import ExtensionTrigger from '../components/extension-trigger';

const { ToggleControl, TextareaControl } = wp.components;
const { __ } = wp.i18n;

export const PARTICLES_BLOCKS = ( window.JetTricksBlocksData && window.JetTricksBlocksData.particlesBlocks ) || [];

const particlesGeneratorUrl = ( window.JetTricksBlocksData && window.JetTricksBlocksData.particlesGeneratorUrl ) || 'https://vincentgarreau.com/particles.js/';
const particlesVersion = ( window.JetTricksBlocksData && window.JetTricksBlocksData.particlesVersion ) || '1.18.11';
const particlesSettingsUrl = ( window.JetTricksBlocksData && window.JetTricksBlocksData.particlesSettingsUrl ) || '';

export function getParticlesAttributes() {
	return {
		jetTricksParticles: { type: 'boolean', default: false },
		jetTricksParticlesJson: { type: 'string', default: '' },
	};
}

export function getParticlesPanel( props ) {
	const { attributes, setAttributes } = props;
	const { jetTricksParticles, jetTricksParticlesJson } = attributes;

	let particlesHelp = (
		<p>
			{ __( 'Paste your particles JSON code here.', 'jet-tricks' ) }{ ' ' }
			<a href={ particlesGeneratorUrl } target="_blank" rel="noopener noreferrer">
				{ __( 'Generate it from here', 'jet-tricks' ) }
			</a>
			.
		</p>
	);

	if ( particlesVersion === '1.18.11' && particlesSettingsUrl ) {
		particlesHelp = (
			<>
				{ particlesHelp }
				<p>
					<a href={ particlesSettingsUrl } target="_blank" rel="noopener noreferrer">
						{ __( 'You can also switch to the new Particles version.', 'jet-tricks' ) }
					</a>
				</p>
			</>
		);
	} else if ( particlesVersion === '3.0.2' ) {
		particlesHelp = (
			<>
				{ particlesHelp }
				<p>
					{ __( 'Version 3.0.2 enables full-screen animation by default. To disable it, set "fullScreen: true" to false in your JSON.', 'jet-tricks' ) }
				</p>
			</>
		);
	}

	return (
		<PopoverContainer
			label={ __( 'Particles Settings', 'jet-tricks' ) }
			trigger={ <ExtensionTrigger label={ __( 'Particles', 'jet-tricks' ) } isActive={ !! jetTricksParticles } /> }
		>
			<>
				<ToggleControl
					label={ __( 'Enable Particles', 'jet-tricks' ) }
					checked={ !! jetTricksParticles }
					onChange={ ( value ) => setAttributes( { jetTricksParticles: !! value } ) }
				/>
				{ jetTricksParticles && (
					<TextareaControl
						label={ __( 'Particles JSON', 'jet-tricks' ) }
						value={ jetTricksParticlesJson || '' }
						onChange={ ( value ) => setAttributes( { jetTricksParticlesJson: value || '' } ) }
						help={ particlesHelp }
						rows={ 8 }
					/>
				) }
			</>
		</PopoverContainer>
	);
}
