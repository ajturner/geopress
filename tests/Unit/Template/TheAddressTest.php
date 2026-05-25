<?php
/**
 * Tests for the_address(), the_location_name(), the_adr_mf(), the_loc_mf()
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class TheAddressTest extends TestCase {

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

    private function mockGeo( string $loc = '', string $name = '' ): stdClass {
        $geo        = new stdClass();
        $geo->coord = '0 0';
        $geo->loc   = $loc;
        $geo->name  = $name;
        return $geo;
    }

    public function test_the_address_returns_loc_field(): void {
        Functions\when( 'get_post_meta' )->justReturn( '2' );
        $GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( '' );
        $GLOBALS['wpdb']->shouldReceive( 'get_row' )->andReturn( $this->mockGeo( '10 Downing St', 'PM Office' ) );

        $this->assertSame( '10 Downing St', the_address() );
    }

    public function test_the_address_returns_empty_when_no_geo(): void {
        Functions\when( 'get_post_meta' )->justReturn( '' );

        $this->assertSame( '', the_address() );
    }

    public function test_the_location_name_returns_name_field(): void {
        Functions\when( 'get_post_meta' )->justReturn( '2' );
        $GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( '' );
        $GLOBALS['wpdb']->shouldReceive( 'get_row' )->andReturn( $this->mockGeo( '10 Downing St', 'PM Office' ) );

        $this->assertSame( 'PM Office', the_location_name() );
    }

    public function test_adr_mf_escapes_address(): void {
        Functions\when( 'get_post_meta' )->justReturn( '2' );
        $GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( '' );
        $GLOBALS['wpdb']->shouldReceive( 'get_row' )->andReturn( $this->mockGeo( '<script>xss</script>' ) );

        $html = the_adr_mf();

        $this->assertStringContainsString( "class='adr'", $html );
        $this->assertStringNotContainsString( '<script>', $html );
    }

    public function test_loc_mf_returns_empty_when_no_name(): void {
        Functions\when( 'get_post_meta' )->justReturn( '2' );
        $GLOBALS['wpdb']->shouldReceive( 'prepare' )->andReturn( '' );
        $GLOBALS['wpdb']->shouldReceive( 'get_row' )->andReturn( $this->mockGeo( '', '' ) );

        $this->assertSame( '', the_loc_mf() );
    }
}
