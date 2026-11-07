/**
 * GeoPress ArcGIS Maps SDK v5 integration.
 *
 * Loaded as type="module". Reads config from window.geopressArcGISConfig
 * (injected by PHP via wp_add_inline_script), sets esriConfig, then
 * attaches arcgisViewReadyChange listeners to web component elements
 * that carry data-lat/data-lon (single-point) or data-locations (multi-point)
 * attributes.
 *
 * No AMD require() — SDK 5 is pure ES modules.
 */

// SDK 5.0 CDN module loading. The single https://js.arcgis.com/5.0/ entry
// (loaded as type="module" by register_scripts()) registers the web components
// and exposes the global $arcgis.import() loader. We pull the core modules we
// need at runtime rather than via deep per-module CDN import URLs.

// Wait until the SDK bootstrap has exposed $arcgis.import().
async function geopressArcGISReady() {
	while ( typeof window.$arcgis === "undefined" || typeof window.$arcgis.import !== "function" ) {
		await new Promise( resolve => setTimeout( resolve, 50 ) );
	}
}
await geopressArcGISReady();

const [ { default: Graphic }, { default: esriConfig } ] = await Promise.all( [
	$arcgis.import( "@arcgis/core/Graphic.js" ),
	$arcgis.import( "@arcgis/core/config.js" ),
] );

// Apply PHP-injected config (api key, portal URL for enterprise).
const cfg = window.geopressArcGISConfig || {};
if ( cfg.apiKey )    { esriConfig.apiKey    = cfg.apiKey; }
if ( cfg.portalUrl ) { esriConfig.portalUrl = cfg.portalUrl; }

const MARKER_SYMBOL = {
	type:    "simple-marker",
	color:   [ 226, 119, 40 ],
	size:    12,
	outline: { color: "white", width: 1 },
};

/**
 * Single-point maps.
 *
 * PHP sets data-lat, data-lon, and data-name on <arcgis-map> or <arcgis-scene>.
 * arcgis-map.js adds a graphic marker at that point once the view is ready.
 */
document.querySelectorAll( "arcgis-map[data-lat], arcgis-scene[data-lat]" ).forEach( el => {
	const lat  = parseFloat( el.dataset.lat );
	const lon  = parseFloat( el.dataset.lon );
	const name = el.dataset.name || "";

	if ( isNaN( lat ) || isNaN( lon ) ) {
		return;
	}

	el.addEventListener( "arcgisViewReadyChange", event => {
		if ( ! event.detail.ready ) {
			return;
		}
		event.target.view.graphics.add(
			new Graphic( {
				geometry:      { type: "point", longitude: lon, latitude: lat },
				symbol:        MARKER_SYMBOL,
				popupTemplate: name ? { title: name } : undefined,
			} )
		);
	} );
} );

/**
 * Multi-location maps.
 *
 * PHP serialises a JSON array into data-locations on <arcgis-map>:
 *   [ { lat, lon, name, details }, … ]
 *
 * arcgis-map.js adds a graphic for each location and auto-fits the view.
 */
document.querySelectorAll( "arcgis-map[data-locations]" ).forEach( el => {
	let locations;
	try {
		locations = JSON.parse( el.dataset.locations );
	} catch ( e ) {
		return;
	}

	if ( ! Array.isArray( locations ) || ! locations.length ) {
		return;
	}

	el.addEventListener( "arcgisViewReadyChange", event => {
		if ( ! event.detail.ready ) {
			return;
		}
		const view = event.target.view;

		locations.forEach( loc => {
			view.graphics.add(
				new Graphic( {
					geometry:      { type: "point", longitude: loc.lon, latitude: loc.lat },
					symbol:        MARKER_SYMBOL,
					popupTemplate: {
						title:   loc.name    || "",
						content: loc.details || "",
					},
				} )
			);
		} );

		if ( view.graphics.length ) {
			view.goTo( view.graphics.toArray() ).catch( () => {} );
		}
	} );
} );
