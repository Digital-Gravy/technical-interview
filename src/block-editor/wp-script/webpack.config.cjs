const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'src/index.ts' ),
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...( defaultConfig.resolve?.alias || {} ),
			'@utilities': path.resolve( __dirname, '../../frontend/_utilities' ),
		},
	},
	watchOptions: {
		...( defaultConfig.watchOptions || {} ),
		ignored: [
			'**/node_modules',
			path.resolve( __dirname, '../../plugin-src/build' ),
		],
	},
};
