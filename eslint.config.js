/**
 * Re-exports wp-scripts' default ESLint flat config.
 *
 * Why: the VSCode/Antigravity ESLint extension resolves config per-file by
 * walking up from the file being edited — without a config file physically
 * present here, fix-on-save silently does nothing for this plugin even
 * though `npm run lint:js` (which loads wp-scripts' default internally)
 * works fine from the CLI.
 * Impact of changing: affects both editor fix-on-save and `npm run lint:js`
 * for every JS file in this plugin.
 */
const defaultConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = defaultConfig;
