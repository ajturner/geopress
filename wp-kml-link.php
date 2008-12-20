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
if (empty($wp)) {
    require_once('../../../wp-config.php');
    //wp('feed=rss2');
}

header('Content-type: application/vnd.google-earth.kml+xml; charset=' . get_settings('blog_charset'), true);
$more = 1;

?>
<?php echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'"?'.'>'; ?>
<!-- generator="wordpress/<?php bloginfo_rss('version') ?>/KMLpress" -->
<kml xmlns="http://earth.google.com/kml/2.0">
<NetworkLink>
    <name><?php bloginfo_rss('name'); ?></name>
    <Snippet><![CDATA[<?php bloginfo_rss('url') ?>]]></Snippet>
    <description><?php bloginfo_rss("description") ?></description>
    <open>0</open>
    <Url>
        <href><?php bloginfo_rss('url'); echo "/wp-kml.php".(!empty($_SERVER['QUERY_STRING'])?"?{$_SERVER['QUERY_STRING']}":''); ?></href>
        <refreshMode>onInterval</refreshMode>
        <refreshInterval>3600</refreshInterval>
    </Url>
    <visibility>1</visibility>
</NetworkLink>
</kml>
