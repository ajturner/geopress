<?php
/**
 * Tests for GeoPress::save_geo()
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class SaveGeoTest extends TestCase {

    /** @var wpdb&\Mockery\MockInterface */
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();

        // Create a partial mock of wpdb.
        $this->wpdb             = \Mockery::mock( 'wpdb' );
        $this->wpdb->prefix     = 'wp_';
        $this->wpdb->insert_id  = 0;

        // Replace the global $wpdb with the mock.
        $GLOBALS['wpdb'] = $this->wpdb;

        // Stub sanitize functions to return their input unchanged.
        Functions\stubs( array(
            'sanitize_text_field' => null,
            'sanitize_key'        => null,
            'esc_url_raw'         => null,
        ) );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wpdb'] );
        parent::tearDown();
    }

    public function test_insert_new_location_when_no_existing_record(): void {
        // No existing record found.
        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT geopress_id FROM wp_geopress WHERE (name = %s AND coord = %s) OR loc = %s' );

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->once()
            ->andReturnNull();

        // Expect an INSERT.
        $this->wpdb->insert_id = 42;
        $this->wpdb
            ->shouldReceive( 'insert' )
            ->once()
            ->with( 'wp_geopress', \Mockery::type( 'array' ), \Mockery::type( 'array' ) )
            ->andReturn( 1 );

        $geo_id = GeoPress::save_geo( -1, 'Home', '123 Main St', '40.7 -74.0', 'point', '', '', 1 );

        $this->assertSame( 42, $geo_id );
    }

    public function test_update_existing_location_by_id(): void {
        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT geopress_id FROM wp_geopress WHERE geopress_id = 5' );

        $existing              = new stdClass();
        $existing->geopress_id = 5;

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->once()
            ->andReturn( $existing );

        $this->wpdb
            ->shouldReceive( 'update' )
            ->once()
            ->with(
                'wp_geopress',
                \Mockery::type( 'array' ),
                array( 'geopress_id' => 5 ),
                \Mockery::type( 'array' ),
                array( '%d' )
            )
            ->andReturn( 1 );

        $geo_id = GeoPress::save_geo( 5, 'Office', '456 Elm Ave', '51.5 -0.1', 'point', '', '', 1 );

        $this->assertSame( 5, $geo_id );
    }

    public function test_empty_name_sets_visible_to_zero(): void {
        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT geopress_id FROM wp_geopress WHERE ...' );

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->once()
            ->andReturnNull();

        $this->wpdb->insert_id = 99;

        $this->wpdb
            ->shouldReceive( 'insert' )
            ->once()
            ->with(
                'wp_geopress',
                \Mockery::on( function ( $data ) {
                    // Empty name forces visible = 0.
                    return $data['visible'] === 0;
                } ),
                \Mockery::any()
            )
            ->andReturn( 1 );

        GeoPress::save_geo( -1, '', '', '', 'point', '', '', 1 );
    }

    public function test_save_geo_uses_wpdb_insert_not_raw_sql(): void {
        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( '' );

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->once()
            ->andReturnNull();

        $this->wpdb->insert_id = 1;

        // Verify insert() is called (not query() with a raw string).
        $this->wpdb
            ->shouldReceive( 'insert' )
            ->once()
            ->andReturn( 1 );

        $this->wpdb
            ->shouldNotReceive( 'query' );

        GeoPress::save_geo( -1, 'Test', 'addr', '0 0', 'point', '', '', 1 );
    }
}
