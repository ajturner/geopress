<?php

/*  Copyright 2007  Barry Hunter - http://www.nearby.org.uk/blog/

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

# set to a full url of a small image file somewhere online
# set to a blank string to disable
$kml_icon = "";


# end config
###################################

if (empty($wp)) {
    require_once('wp-config.php');
    $posts_per_page=-1;
		wp();
		//wp('feed=rss2');
}

header('Content-type: application/gpx+xml; charset=' . get_settings('blog_charset'), true);
$more = 1;

?>
<?php echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'"?'.'>'; ?>
<!-- generator="wordpress/<?php bloginfo_rss('version') ?>" -->
<gpx
 version="1.0"
 creator="ExpertGPS 1.1 - http://www.topografix.com"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xmlns="http://www.topografix.com/GPX/1/0"
 xsi:schemaLocation="http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd">
<time><?php echo current_time('mysql'); ?></time>
    <?php $items_count = 0; $wp_query->post_count = 1000; if ($posts) {
    foreach ($posts as $post) {
        $coord = the_coord();
          $coord = split(" ", $coord);
        if ($coord[0] || $coord[1]) {
            start_wp(); ?>
    <wpt lat="<?php echo $coord[0] ?>" lon="<?php echo $coord[1] ?>">
        <name><?php the_title_rss() ?></name>
        <desc><![CDATA[<?php the_content_rss() ?>]]></desc>
        <type><?php foreach((get_the_category()) as $cat) { echo $cat->cat_name . ' '; } ?></type>
		<time><?php the_time('c') ?></time>
    </wpt>
    <?php $items_count++; /* if (($items_count == get_settings('posts_per_rss')) && empty($m)) { break; } */ } } } ?>
</gpx>
