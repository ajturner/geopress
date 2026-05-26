<?php
/**
 * Tests for GeoPress_Feeds::atom_entry() and GeoPress_Feeds::geopress_namespace()
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class AtomEntryTest extends TestCase {

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

    public function test_atom_entry_outputs_nothing_when_rss_disabled(): void {
        Functions\when( 'get_option' )->justReturn( 'false' );
        Functions\when( 'get_post_meta' )->justReturn( '' );

        ob_start();
        GeoPress_Feeds::atom_entry( 1 );
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_atom_entry_outputs_nothing_when_no_coord(): void {
        Functions\when( 'get_option' )->justReturn( 'true' );
        Functions\when( 'get_post_meta' )->justReturn( '' ); // No geo → the_coord() returns ''.

        ob_start();
        GeoPress_Feeds::atom_entry( 1 );
        $output = ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_atom_entry_outputs_georss_when_enabled_and_coord_exists(): void {
        // get_option returns 'true' for rss_enable, 'simple' for rss_format.
        Functions\when( 'get_option' )->alias( function ( $key, $default = false ) {
            if ( '_geopress_rss_enable' === $key ) return 'true';
            if ( '_geopress_rss_format' === $key ) return 'simple';
            return $default;
        } );

        $geo        = new stdClass();
        $geo->coord = '51.5 -0.1';
        $geo->loc   = 'London';
        $geo->name  = 'London';

        Functions\when( 'get_post_meta' )->justReturn( '5' );
        $GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( '' );
        $GLOBALS['wpdb']->shouldReceive( 'get_row' )->andReturn( $geo );

        ob_start();
        GeoPress_Feeds::atom_entry( 1 );
        $output = ob_get_clean();

        $this->assertStringContainsString( 'georss:point', $output );
        $this->assertStringContainsString( '51.5 -0.1', $output );
    }

    public function test_namespace_outputs_w3c_namespace(): void {
        Functions\when( 'get_option' )->alias( function ( $key, $default = false ) {
            if ( '_geopress_rss_enable' === $key ) return 'true';
            if ( '_geopress_rss_format' === $key ) return 'w3c';
            return $default;
        } );

        ob_start();
        GeoPress_Feeds::geopress_namespace();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'xmlns:geo=', $output );
        $this->assertStringContainsString( 'wgs84_pos', $output );
    }

    public function test_namespace_outputs_georss_for_simple_format(): void {
        Functions\when( 'get_option' )->alias( function ( $key, $default = false ) {
            if ( '_geopress_rss_enable' === $key ) return 'true';
            if ( '_geopress_rss_format' === $key ) return 'simple';
            return $default;
        } );

        ob_start();
        GeoPress_Feeds::geopress_namespace();
        $output = ob_get_clean();

        $this->assertStringContainsString( 'xmlns:georss=', $output );
    }
}
