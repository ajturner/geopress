<?php
/**
 * Tests for GeoPress_Feeds::rss2_item()
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class Rss2ItemTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();

        $post     = new stdClass();
        $post->ID = 1;
        $GLOBALS['post']  = $post;

        $wpdb         = \Mockery::mock( 'wpdb' );
        $wpdb->prefix   = 'wp_';
        $wpdb->postmeta = 'wp_postmeta';
        $wpdb->posts    = 'wp_posts';
        $GLOBALS['wpdb'] = $wpdb;
    }

    protected function tearDown(): void {
        unset( $GLOBALS['post'], $GLOBALS['wpdb'] );
        parent::tearDown();
    }

    public function test_rss2_item_outputs_nothing_when_disabled(): void {
        Functions\when( 'get_option' )->justReturn( 'false' );

        ob_start();
        GeoPress_Feeds::rss2_item( 1 );
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_rss2_item_outputs_gml_format(): void {
        Functions\when( 'get_option' )->alias( function ( $key, $default = false ) {
            if ( '_geopress_rss_enable' === $key ) return 'true';
            if ( '_geopress_rss_format' === $key ) return 'gml';
            return $default;
        } );

        $geo        = new stdClass();
        $geo->coord = '40.7 -74.0';
        $geo->loc   = 'New York';
        $geo->name  = 'NYC';

        Functions\when( 'get_post_meta' )->justReturn( '3' );
        $GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( '' );
        $GLOBALS['wpdb']->shouldReceive( 'get_row' )->andReturn( $geo );

        ob_start();
        GeoPress_Feeds::rss2_item( 1 );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'georss:where', $output );
        $this->assertStringContainsString( 'gml:Point', $output );
        $this->assertStringContainsString( '40.7 -74.0', $output );
    }

    public function test_rss2_item_outputs_w3c_format(): void {
        Functions\when( 'get_option' )->alias( function ( $key, $default = false ) {
            if ( '_geopress_rss_enable' === $key ) return 'true';
            if ( '_geopress_rss_format' === $key ) return 'w3c';
            return $default;
        } );

        $geo        = new stdClass();
        $geo->coord = '35.7 139.7';
        $geo->loc   = 'Tokyo';
        $geo->name  = 'Tokyo';

        Functions\when( 'get_post_meta' )->justReturn( '4' );
        $GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( '' );
        $GLOBALS['wpdb']->shouldReceive( 'get_row' )->andReturn( $geo );

        ob_start();
        GeoPress_Feeds::rss2_item( 1 );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'geo:lat', $output );
        $this->assertStringContainsString( 'geo:lon', $output );
        $this->assertStringContainsString( '35.7', $output );
    }
}
