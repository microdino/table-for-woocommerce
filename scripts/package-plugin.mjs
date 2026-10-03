import { createWriteStream, existsSync, mkdirSync, readFileSync, readdirSync, renameSync, rmSync, statSync } from 'node:fs';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { once } from 'node:events';
import yazl from 'yazl';

const root = dirname( dirname( fileURLToPath( import.meta.url ) ) );
const manifest = JSON.parse( readFileSync( join( root, 'package.json' ), 'utf8' ) );
const slug = manifest.name;
const version = manifest.version;
const header = readFileSync( join( root, 'table-for-woocommerce.php' ), 'utf8' );
const readme = readFileSync( join( root, 'readme.txt' ), 'utf8' );
const pluginVersion = header.match( /^ \* Version:\s*(\S+)/m )?.[ 1 ];
const stableTag = readme.match( /^Stable tag:\s*(\S+)/mi )?.[ 1 ];

if ( ! /^[a-z0-9-]+$/.test( slug ) || ! /^\d+\.\d+\.\d+$/.test( version ) ) {
	throw new Error( 'Package name or version is invalid.' );
}
if ( pluginVersion !== version || stableTag !== version ) {
	throw new Error( 'package.json, the plugin header, and readme.txt must have the same version.' );
}

const files = [
	'table-for-woocommerce.php',
	'uninstall.php',
	'readme.txt',
	'changelog.txt',
	'package.json',
	'package-lock.json',
];
function addDirectory( directory ) {
	if ( ! existsSync( join( root, directory ) ) ) {
		return;
	}
	for ( const entry of readdirSync( join( root, directory ), { withFileTypes: true } ) ) {
		const path = join( directory, entry.name );
		if ( entry.name.startsWith( '.' ) || path === join( 'src', 'dashboard', 'style.css' ) || path.endsWith( '.js.map' ) ) {
			continue;
		}
		if ( entry.isDirectory() ) {
			addDirectory( path );
		} else if ( entry.isFile() ) {
			files.push( path );
		} else {
			throw new Error( `Unsupported entry in release: ${ path }` );
		}
	}
}
for ( const directory of [ 'includes', 'templates', 'assets', 'src', 'languages', 'scripts' ] ) {
	addDirectory( directory );
}
for ( const path of files ) {
	const source = join( root, path );
	if ( ! existsSync( source ) || ! statSync( source ).isFile() ) {
		throw new Error( `Release file is missing: ${ path }` );
	}
}

const outputDirectory = join( root, 'dist' );
const outputPath = join( outputDirectory, `${ slug }-${ version }.zip` );
const temporaryPath = `${ outputPath }.tmp`;
mkdirSync( outputDirectory, { recursive: true } );
rmSync( temporaryPath, { force: true } );

const output = createWriteStream( temporaryPath );
const archive = new yazl.ZipFile();
const done = once( output, 'close' );
archive.on( 'error', ( error ) => output.destroy( error ) );
archive.outputStream.on( 'error', ( error ) => output.destroy( error ) );
archive.outputStream.pipe( output );

for ( const path of files.sort() ) {
	const source = join( root, path );
	archive.addFile( source, `${ slug }/${ relative( root, source ).replaceAll( '\\', '/' ) }`, {
		mtime: new Date( '1980-01-01T00:00:00Z' ),
		mode: 0o100644,
		compressionLevel: 9,
	} );
}

try {
	archive.end();
	await done;
	renameSync( temporaryPath, outputPath );
	console.log( `Created ${ relative( root, outputPath ) } with ${ files.length } files.` );
} catch ( error ) {
	rmSync( temporaryPath, { force: true } );
	throw error;
}
