import HieraSelectData from './input';
import HieraSelectSignal from './signal';

const {
	      addFilter,
      } = JetPlugins.hooks;

addFilter(
	'jet.fb.inputs',
	'jet-form-builder/hierarchical-select',
	function ( inputs ) {
		inputs = [ HieraSelectData, ...inputs ];

		return inputs;
	},
);

addFilter(
	'jet.fb.signals',
	'jet-form-builder/hierarchical-select',
	function ( signals ) {
		signals = [ HieraSelectSignal, ...signals ];

		return signals;
	},
);