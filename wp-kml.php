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
    require_once('../../../wp-config.php');
    $posts_per_page=-1;
		wp('feed=1');
		//wp('feed=rss2');
}

header('Content-type: application/vnd.google-earth.kml+xml; charset=' . get_settings('blog_charset'), true);
$more = 1;

?>
<?php echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'"?'.'>'; ?>
<!-- generator="wordpress/<?php bloginfo_rss('version') ?>/GeoPress" -->
<kml xmlns="http://earth.google.com/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Document>
    <Style id="defaultIcon">
        <LabelStyle>
            <scale>0</scale>
        </LabelStyle>
        <?php if ($kml_icon) { ?>
        <IconStyle>
            <Icon>
                <href><?php echo $kml_icon; ?></href>
            </Icon>
        </IconStyle>
        <?php } ?>
        </Style>
    <Style id="hoverIcon">
        <IconStyle>
            <scale>2.1</scale>
            <?php if ($kml_icon) { ?>
            <Icon>
                <href><?php echo $kml_icon; ?></href>
            </Icon>
            <?php } ?>
        </IconStyle>
    </Style>
    <StyleMap id="defaultStyle">
        <Pair>
            <key>normal</key>
            <styleUrl>#defaultIcon</styleUrl>
        </Pair>
        <Pair>
            <key>highlight</key>
            <styleUrl>#hoverIcon</styleUrl>
        </Pair>
    </StyleMap>
    <name><?php bloginfo_rss('name'); ?></name>
    <Snippet><![CDATA[<?php bloginfo_rss('url') ?><br/><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?>]]></Snippet>
    <description><?php bloginfo_rss("description") ?></description>
    <atom:link href="<?php bloginfo('atom_url'); ?>"/>
  	<?php while (have_posts()) : the_post();
        $coord = the_coord();
          $coord = split(" ", $coord);
        if ($coord[0] || $coord[1]) { ?>
    <Placemark id="<?php the_ID(); ?>">
        <name><?php the_title_rss() ?></name>
<?php if (get_settings('rss_use_excerpt')) : ?>
        <Snippet><![CDATA[<?php the_excerpt_rss() ?>]]></Snippet>
<?php else : ?>
        <Snippet><![CDATA[<?php the_excerpt_rss() ?>]]></Snippet>
    <?php if ( strlen( $post->post_content ) > 0 ) : ?>
        <description><![CDATA[<?php the_content('', 0, '') ?>]]></description>
    <?php endif; ?>
<?php endif; ?>
		<atom:author>
			<atom:name><?php the_author() ?></atom:name>
		</atom:author>
		<?php $author_url = get_the_author_url(); if ( !empty($author_url) ) : ?>
			<atom:link href="<?php the_author_url()?>"/>
		<?php endif; ?>
	
        <styleUrl>#defaultStyle</styleUrl>
        <metadata><?php echo convert_chars(the_category(','));  ?></metadata>
        <visibility>1</visibility>
        <Point>
            <extrude>1</extrude>
            <altitudeMode>relativeToGround</altitudeMode>
            <coordinates><?php echo $coord[1].','.$coord[0]; ?>,25</coordinates>
        </Point>
    </Placemark>
  <?php } endwhile ; ?>
</Document>
</kml>
