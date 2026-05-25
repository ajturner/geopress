<?php
/**
 * Tests for GeoPress::get_geo()
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class GetGeoTest extends TestCase {

    /** @var wpdb&\Mockery\MockInterface */
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();

        $this->wpdb             = \Mockery::mock( 'wpdb' );
        $this->wpdb->prefix     = 'wp_';
        $this->wpdb->postmeta   = 'wp_postmeta';
        $this->wpdb->posts      = 'wp_posts';
        $GLOBALS['wpdb']        = $this->wpdb;
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wpdb'] );
        parent::tearDown();
    }

    public function test_returns_null_when_post_has_no_geopress_id(): void {
        Functions\when( 'get_post_meta' )->justReturn( '' );

        $result = GeoPress::get_geo( 1 );

        $this->assertNull( $result );
    }

    public function test_returns_location_for_post_with_valid_geo_id(): void {
        Functions\when( 'get_post_meta' )->justReturn( '7' );

        $location        = new stdClass();
        $location->coord = '40.7 -74.0';
        $location->name  = 'New York';

        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT ...' );

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->once()
            ->andReturn( $location );

        $result = GeoPress::get_geo( 10 );

        $this->assertSame( $location, $result );
        $this->assertSame( '40.7 -74.0', $result->coord );
    }

    public function test_returns_null_when_db_returns_null(): void {
        Functions\when( 'get_post_meta' )->justReturn( '3' );

        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT ...' );

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->once()
            ->andReturnNull();

        $result = GeoPress::get_geo( 99 );

        $this->assertNull( $result );
    }

    public function test_casts_post_id_to_int(): void {
        // Passing a string post ID should not cause an error.
        Functions\when( 'get_post_meta' )->justReturn( '' );

        $result = GeoPress::get_geo( '42' );

        $this->assertNull( $result );
    }
}
