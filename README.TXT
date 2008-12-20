=== Plugin Name ===
Contributors: ajturner
Tags: geo, mapping, maps, georss, kml, microformats, mapstraction, google, yahoo, microsoft, openstreetmap, map24, multimap, mapquest, freeearth, spinnyglobe, neogeography
Requires at least: 2.0.0
Tested up to: 2.8
Stable tag: /trunk/

Add maps to your posts or pages, embed Microformat Geo or Adr, and output GeoRSS and KML. 

== Description ==

GeoPress adds geographic tagging of your posts and pages. You can enter an address, points on a map, or enter latitude & longitude using the post interface. You can also include the post location within the body itself to make it easy to geotag using emailed posts and desktop clients. Maps can be Google, Microsoft, OpenStreetMap, 3D globe, or any major mapping provider by using the Mapstraction mapping library.  Makes your feeds GeoRSS compatible, and also adds KML for viewing in Google Earth.

User forums available at: http://getsatisfaction.com/mapufacture/products/mapufacture_geopress
Most information at: http://georss.org/geopress

== Installation ==

1. Unzip the geopress folder in the zip file to your wordpress plugins directory (wp-content/plugins). 
2. Activate the plugin from your admin panel. 
3. Register and add your Google or Yahoo Keys in the Options panel
4. Optionally, add http://mapufacture.com/ping/api to your "Update Services" under "Options" -> "Writing" 
5. Write a post, and enter the location name, address, [latitude, longitude], or use the map for the point you want to store with the post.

== Frequently Asked Questions ==

= How do i make my RSS feed publish lat/long? =
Make sure "Enable GeoRSS tags in feeds" is checked in the GeoPress options. Everything else is added for you automatically.

= How do i insert a map into my content? =
A: First enter in a location for your post. After that, you have a couple of options. 
 - You can change the "Add Maps:" setting in the GeoPress admin panel to automatically show maps on any page, or just single view pages
 - You can use one of the template functions like geopress_post_map to put the map where you want.
 - Lastly, you can insert the text "INSERT_MAP" (without the quotes) where you want the image tag for the map included within your post text while editing the post. A map centered at the post's location & with a marker showing the location will appear. 


== Template Functions Available ==

geopress_map(height, width, locations, unique_id, loop_locations, zoom_level) : heigth and width set the size of the map in pixels. 'locations' is the maximum number of locations to show (-1 shows all). 'unique_id' should be true. 'loop_locations' should be true to just show the markers for the current page (for a category, or search result map), or false to show all locations from your blog. 'zoom_level' for the map zoom level of the map. Default zooms to fit all markers, 1 is the whole Earth, and 17 is zoomed all the way to street level.

geopress_post_map(height, width, controls): 
 
the_lat_rss() : prints out <geo:lat>latitude</geo:lat>

the_lat()     : returns raw latitude

the_lat_rss() : prints out <geo:long>longitude</geo:long>

the_lon()     : returns raw longitude

the_addr_rss(): prints out <ymaps:Address>Address</ymaps:Address>

the_addr()    : return raw address

ymap_post_url(): returns a link to maps (flash based) page centered at the location for the post

ymap_blog_url(type): returns a link to maps (old v1) page overlaying the locations of the blog entries. This assumes that you have enabled your rss feed with lat/long. the type can be 'rss2_url' for rss2 feed, 'rss_url' for rss 0.92 feed.
