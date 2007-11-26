
GeoPress v2.1 
=============================

This plugin is intends to provide few services

1) To geocode each of your posts based on an address. So, you can say, this post happened at this address, or this location. You can specify, full addres, city + state, or zip
2) We have a quick function to insert a map image centered at the location. 
3) You can create a GeoRSS enabled RSS feed and make your rss feed plottable on a map. (You'd have to do some coding)
4) You can create a quick link to show off your feed locations on yahoo! maps.
5) You can save your favorite locations in a list and use them again. 
6) You can customize the size of the map, goto options-->GeoPress to customize. 


INSTALLATION
============

1. Unzip the georss folder in the zip file to your wordpress plugins directory (wp-content/plugins). 
2. Activate the plugin from your admin panel. 
3. Register and add your Google and Yahoo Keys in the Options panel
4. Optionally, add http://mapufacture.com/ping/api to your "Update Services" under "Options" -> "Writing"

You are good to go. 


Template Functions Available
============================

geopress_map(height, width) : 
the_lat_rss() : prints out <geo:lat>latitude</geo:lat>
the_lat()     : returns raw latitude
the_lat_rss() : prints out <geo:long>longitude</geo:long>
the_lon()     : returns raw longitude
the_addr_rss(): prints out <ymaps:Address>Address</ymaps:Address>
the_addr()    : return raw address
ymap_post_url(): returns a link to maps (flash based) page centered at the location for the post
ymap_blog_url(type): returns a link to maps (old v1) page overlaying the locations of the blog entries. This assumes that you have enabled your rss feed with lat/long. the type can be 'rss2_url' for rss2 feed, 'rss_url' for rss 0.92 feed.


Frequently Asked Questions (FAQ)
================================

1) Great! How do i make my RSS feed publish lat/long?
Make sure "Enable GeoRSS tags in feeds" is checked in the GeoPress options. Everything else is added for you automatically.

2) How do i insert a map into my content?
A: First enter in a location for your post. After that, insert this text "INSERT_MAP" (without the quotes) where you want the image tag for the map included within your post text while editing the post. A map centered at the post's location & with a star showing the location will appear. 

