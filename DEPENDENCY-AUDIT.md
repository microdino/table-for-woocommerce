# Development dependency audit

The project uses esbuild for build and watch tasks. It has JavaScript/JSX and plain CSS, with no Gulp tasks, Sass sources, or TypeScript sources.

## Retained

- Build: esbuild.
- Source imports: @wordpress/components, react, react-dom. React and React DOM are now declared directly at their existing ^18.3.1 version range. These packages are bundled into the plugin's JavaScript.
- Linting: @wordpress/eslint-plugin, eslint, eslint-plugin-react. The WordPress plugin supplies the JSX-capable Babel parser.
- Formatting: @wordpress/prettier-config, prettier (wp-prettier alias).
- Packaging: yazl creates an installable ZIP with an explicit plugin root and curated contents. It has one runtime dependency.

## Removed direct dependencies

- @types/react
- @babel/core
- @babel/eslint-parser
- @babel/preset-react
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
- typescript

The Webpack configs and their Babel config were removed because npm scripts use esbuild. Gulp and its plugins have no tasks. Sass and its loaders have no Sass sources. cross-env, del, and webpack-bundle-analyzer have no active usage. path is built into Node.js. concurrently wrapped only one command; dev now runs watch directly. @types/react has no direct project usage (it can remain transitively). The ESLint plugin provides its own WordPress Babel preset.

The lockfile was synchronized, also removing its stale @wordpress/block-editor root dependency. TypeScript remains installed only through the lint toolchain's peer requirements. Some removed direct packages may remain as dependencies of retained tooling.

## Verification

- `npm ls --depth=0` passes with the updated lockfile.
- `npm run build:zip` runs ESLint, builds the admin bundle, and creates `dist/table-for-woocommerce-1.0.0.zip`.
- The ZIP contains the plugin entry point, PHP classes, templates, compiled assets, readable source, and build manifests. It excludes `node_modules`, tests, development notes, lint configuration, and the unused JavaScript source map.
