/**
 * ESLint configuration.
 *
 * @see https://github.com/WordPress/gutenberg/tree/trunk/packages/scripts#lint-js
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );
const json = require( 'eslint-plugin-json' );

module.exports = [
	{
		ignores: [
			'.github/**',
			'js/dist/**',
			'vendor/**',
			'vendor-bin/**',
			'wordpress/**',
			'wp-content/**',
		],
	},
	...defaultConfig,
	{
		files: [ '**/*.json' ],
		...json.configs.recommended,
	},
];
