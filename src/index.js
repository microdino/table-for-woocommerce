import { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import MenuBar from './dashboard/MenuBar';
if ( document.body.contains( document.getElementById( 'prta-dashboard' ) ) ) {
	const container = document.getElementById( 'prta-dashboard' );
	const root = createRoot( container );
	root.render( <MenuBar /> );
}
