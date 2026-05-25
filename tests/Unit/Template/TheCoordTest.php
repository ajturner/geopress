<?php
/**
 * Tests for the_coord() and the_geo_mf()
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class TheCoordTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();

        $post     = new stdClass();
        $post->ID = 1;
        $GLOBALS['post']  = $post;

        $wpdb         = \Mockery::mock( 'wpdb' );
        $wpdb->prefix = 'wp_';
        $GLOBALS['wpdb'] = $wpdb;
    }

    protected function tearDown(): void {
        unset( $GLOBALS['post'], $GLOBALS['wpdb'] );
        parent::tearDown();
    }

    private function mockGeo( string $coord ): stdClass {
        $geo        = new stdClass();
        $geo->coord = $coord;
        $geo->loc   = '';
        $geo->name  = '';
        return $geo;
    }

    public function test_returns_coord_string(): void {
        Functions\when( 'get_post_meta' )->justReturn( '3' );
        $GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( '' );
        $GLOBALS['wpdb']->shouldReceive( 'get_row' )->andReturn( $this->mockGeo( '51.5 -0.1' ) );

        $this->assertSame( '51.5 -0.1', the_coord() );
    }

    public function test_returns_empty_string_when_no_geo(): void {
        Functions\when( 'get_post_meta' )->justReturn( '' );

        $this->assertSame( '', the_coord() );
    }

    public function test_geo_mf_returns_empty_when_no_coord(): void {
        Functions\when( 'get_post_meta' )->justReturn( '' );

        $this->assertSame( '', the_geo_mf() );
    }

    public function test_geo_mf_wraps_coords_in_microformat(): void {
        Functions\when( 'get_post_meta' )->justReturn( '3' );
        $GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( '' );
        $GLOBALS['wpdb']->shouldReceive( 'get_row' )->andReturn( $this->mockGeo( '51.5 -0.1' ) );

        $html = the_geo_mf();

        $this->assertStringContainsString( "class='geo'", $html );
        $this->assertStringContainsString( "class='latitude'", $html );
        $this->assertStringContainsString( '51.5', $html );
        $this->assertStringContainsString( '-0.1', $html );
    }
}
