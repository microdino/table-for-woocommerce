# Development dependency audit

The project uses esbuild for build and watch tasks. It has JavaScript/JSX and plain CSS, with no Gulp tasks, Sass sources, or TypeScript sources.

## Retained

- Build: esbuild.
- Source imports: @wordpress/components, react, react-dom. React and React DOM are now declared directly at their existing ^18.3.1 version range. These packages are bundled into the plugin's JavaScript.
- Linting: @babel/core, @babel/eslint-parser, @babel/preset-react, @wordpress/eslint-plugin, eslint, eslint-plugin-react.
- Formatting: @wordpress/prettier-config, prettier (wp-prettier alias).
- TypeScript: required as a peer dependency by @wordpress/eslint-plugin, despite having no TypeScript source files.

## Removed direct dependencies

- @types/react
- @wordpress/babel-preset-default
- babel-loader
- concurrently
- cross-env
- css-loader
- del
- gulp
- gulp-autoprefixer
- gulp-clean-css
- gulp-concat
- gulp-sass
- gulp-terser
- gulp-zip
- path
- sass
- sass-loader
- style-loader
- terser-webpack-plugin
- webpack
- webpack-bundle-analyzer
- webpack-cli

The Webpack configs and their Babel config were removed because npm scripts use esbuild. Gulp and its plugins have no tasks. Sass and its loaders have no Sass sources. cross-env, del, and webpack-bundle-analyzer have no active usage. path is built into Node.js. concurrently wrapped only one command; dev now runs watch directly. @types/react has no direct project usage (it can remain transitively). The ESLint plugin provides its own WordPress Babel preset.

The lockfile was synchronized, also removing its stale @wordpress/block-editor root dependency. Some removed direct packages may remain as dependencies of retained tooling.

## Verification

- npm install --offline --ignore-scripts --no-audit --no-fund succeeded; 634 packages removed.
- npm ls --depth=0 passed.
- npm run build passed; output matched the pre-cleanup build apart from the source-map URL.
- npm run dev completed its initial build and entered watch mode.
- ESLint configuration resolves and Prettier runs. An invalid ESLint extends entry was moved to its intended rule override. ESLint reports 35 source errors with no fatal parser/configuration errors; source lint cleanup is outside this dependency change.
