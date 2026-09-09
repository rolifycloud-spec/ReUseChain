const { __ } = wp.i18n;
const {
	registerBlockType
} = wp.blocks;

const {
	useBlockProps,
} = wp.blockEditor;

const {
	SelectControl,
	Placeholder,
	Path,
	Rect,
	SVG
} = wp.components;

const GIcon = <SVG xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64" fill="none"><Rect fill="white" x="1" y="5" width="44" height="53" rx="3" stroke="#162B40" strokeWidth="2"></Rect><Path d="M7 49C7 47.3431 8.34315 46 10 46H21C22.6569 46 24 47.3431 24 49C24 50.6569 22.6569 52 21 52H10C8.34315 52 7 50.6569 7 49Z" fill="#4AF3BA" stroke="#162B40" strokeWidth="2"></Path><Rect fill="white" x="7" y="33" width="32" height="6" rx="1" stroke="#162B40" strokeWidth="2"></Rect><Rect fill="white" x="7" y="23" width="32" height="6" rx="1" stroke="#162B40" strokeWidth="2"></Rect><Rect fill="white" x="6.5" y="14.5" width="18" height="1" rx="0.5" stroke="#162B40"></Rect><Rect fill="white" x="6.5" y="10.5" width="33" height="1" rx="0.5" stroke="#162B40"></Rect></SVG>;

const blockAttributes = window.JetEngineListingData.atts.dynamicChart;

registerBlockType( 'jet-engine/dynamic-chart', {
	apiVersion: "3",
	title: __( 'Dynamic Chart' ),
	icon: GIcon,
	category: 'layout',
	attributes: blockAttributes,
	className: 'jet-chart',
	edit: ( props ) => {

		const blockProps = useBlockProps();
		const attributes = props.attributes;
		const chartsList = window.JetEngineListingData.chartsList;

		return [
			<div { ...blockProps }>
				<Placeholder
					instructions={ 'Here you can select chart you want to render. Actual chart will be rendered on front-end for better editor performance' }
					label={ 'Dynamic Chart' }
					isColumnLayout={ true }
					key={ 'dynamic-chart-placeholder' }
				>
					<div>
						<SelectControl
							label={ __( 'Select chart' ) }
							value={ attributes.chart_id }
							options={ chartsList }
							onChange={ newValue => {
								props.setAttributes( { chart_id: newValue } );
							} }
						/>
					</div>
				</Placeholder>
			</div>
		];
	},
	save: props => {
		return null;
	}
} );
