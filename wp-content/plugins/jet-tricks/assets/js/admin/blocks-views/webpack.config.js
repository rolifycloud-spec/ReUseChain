const path = require( 'path' );
const TerserPlugin = require( 'terser-webpack-plugin' );

module.exports = {
	entry: {
		blocks: './src/main.js',
	},
	output: {
		path: __dirname,
		filename: '[name].js',
	},
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
				},
			},
		],
	},
	optimization: {
		minimizer: [
			new TerserPlugin( {
				terserOptions: {
					output: {
						comments: false,
					},
				},
				extractComments: false,
			} ),
		],
	},
	resolve: {
		modules: [
			path.resolve( __dirname, 'src' ),
			'node_modules',
		],
		extensions: [ '.js', '.jsx' ],
	},
	externals: {
		'@wordpress/blocks': [ 'wp', 'blocks' ],
		'@wordpress/element': [ 'wp', 'element' ],
		'@wordpress/block-editor': [ 'wp', 'blockEditor' ],
		'@wordpress/components': [ 'wp', 'components' ],
		'@wordpress/i18n': [ 'wp', 'i18n' ],
		'@wordpress/server-side-render': [ 'wp', 'serverSideRender' ],
	},
};
