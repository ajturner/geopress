<?php
/**
 * Tests for GeoPress::get_location()
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;

class GetLocationTest extends TestCase {

    /** @var wpdb&\Mockery\MockInterface */
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();

        $this->wpdb         = \Mockery::mock( 'wpdb' );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb']    = $this->wpdb;
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wpdb'] );
        parent::tearDown();
    }

    public function test_returns_null_for_zero_id(): void {
        $this->wpdb->shouldNotReceive( 'prepare' );
        $this->wpdb->shouldNotReceive( 'get_row' );

        $result = GeoPress::get_location( 0 );

        $this->assertNull( $result );
    }

    public function test_returns_null_for_negative_id(): void {
        $this->wpdb->shouldNotReceive( 'prepare' );

        $result = GeoPress::get_location( -5 );

        $this->assertNull( $result );
    }

    public function test_returns_location_row_for_valid_id(): void {
        $location              = new stdClass();
        $location->geopress_id = 3;
        $location->name        = 'London';
        $location->coord       = '51.5 -0.1';

        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->with( \Mockery::type( 'string' ), 3 )
            ->andReturn( 'SELECT * FROM wp_geopress WHERE geopress_id = 3' );

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->once()
            ->andReturn( $location );

        $result = GeoPress::get_location( 3 );

        $this->assertSame( $location, $result );
        $this->assertSame( 'London', $result->name );
    }

    public function test_returns_null_when_location_not_found(): void {
        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT ...' );

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->once()
            ->andReturnNull();

        $result = GeoPress::get_location( 999 );

        $this->assertNull( $result );
    }

    public function test_query_uses_prepare_not_raw_sql(): void {
        // Verifies that SQL is parameterized, not concatenated.
        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'prepared-query' );

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->with( 'prepared-query' )
            ->once()
            ->andReturnNull();

        $this->wpdb->shouldNotReceive( 'query' );

        GeoPress::get_location( 1 );
    }
}
