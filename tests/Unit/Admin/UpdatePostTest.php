<?php
/**
 * Tests for GeoPress::update_post()
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class UpdatePostTest extends TestCase {

    /** @var wpdb&\Mockery\MockInterface */
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();

        $this->wpdb         = \Mockery::mock( 'wpdb' );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb']    = $this->wpdb;
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wpdb'], $_POST );
        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_bails_during_autosave(): void {
        define( 'DOING_AUTOSAVE', true );

        $this->wpdb->shouldNotReceive( 'prepare' );

        GeoPress::update_post( 1 );
    }

    public function test_bails_without_nonce(): void {
        $_POST = array(); // No nonce present.

        Functions\when( 'wp_verify_nonce' )->justReturn( false );
        $this->wpdb->shouldNotReceive( 'prepare' );

        GeoPress::update_post( 1 );
    }

    public function test_bails_when_addr_is_empty(): void {
        $_POST = array(
            'geopress_nonce' => 'valid',
            'addr'           => '',
            'geometry'       => '',
            'locname'        => '',
        );

        Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
        Functions\when( 'current_user_can' )->justReturn( true );

        $post                = new stdClass();
        $post->post_content  = '';
        Functions\when( 'get_post' )->justReturn( $post );

        $this->wpdb->shouldNotReceive( 'prepare' );

        GeoPress::update_post( 5 );
    }

    public function test_parses_coordinate_syntax_without_geocoding(): void {
        $_POST = array(
            'geopress_nonce' => 'valid',
            'addr'           => '[51.5,-0.1]',
            'geometry'       => '',
            'locname'        => 'London',
        );

        Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
        Functions\when( 'current_user_can' )->justReturn( true );
        Functions\when( 'sanitize_text_field' )->returnArg();
        Functions\when( 'wp_unslash' )->returnArg();

        $post               = new stdClass();
        $post->post_content = '';
        Functions\when( 'get_post' )->justReturn( $post );

        // Stub yahoo_mapurl to avoid HTTP.
        Functions\when( 'yahoo_mapurl' )->justReturn( array( '', '' ) );

        // Expect save_geo is effectively called: mock the DB chain.
        $this->wpdb
            ->shouldReceive( 'prepare' )
            ->once()
            ->andReturn( 'SELECT ...' );

        $this->wpdb
            ->shouldReceive( 'get_row' )
            ->once()
            ->andReturnNull();

        $this->wpdb->insert_id = 7;
        $this->wpdb
            ->shouldReceive( 'insert' )
            ->once()
            ->with(
                'wp_geopress',
                \Mockery::on( function ( $data ) {
                    // Coordinates should be "51.5 -0.1" (note: [51.5,-0.1] → lat=51.5 lon=-0.1).
                    return strpos( $data['coord'], '51.5' ) !== false;
                } ),
                \Mockery::any()
            )
            ->andReturn( 1 );

        Functions\when( 'update_post_meta' )->justReturn( true );

        GeoPress::update_post( 5 );
    }

    public function test_input_sanitization_is_applied(): void {
        // Verify that sanitize_text_field is called on POST values.
        $dirty_addr = '<script>alert(1)</script>123 Main St';

        $_POST = array(
            'geopress_nonce' => 'valid',
            'addr'           => $dirty_addr,
            'geometry'       => '',
            'locname'        => '<b>Bad</b>',
        );

        Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
        Functions\when( 'current_user_can' )->justReturn( true );
        Functions\when( 'wp_unslash' )->returnArg();

        // sanitize_text_field should strip tags.
        Functions\expect( 'sanitize_text_field' )
            ->atLeast()->once()
            ->andReturnUsing( function ( $val ) {
                return strip_tags( $val );
            } );

        $post               = new stdClass();
        $post->post_content = '';
        Functions\when( 'get_post' )->justReturn( $post );

        // No HTTP calls, no DB calls — we only care that sanitization ran.
        Functions\when( 'yahoo_mapurl' )->justReturn( array( '', '' ) );
        Functions\when( 'geocode' )->justReturn( array( '', '' ) );

        $this->wpdb->shouldReceive( 'prepare' )->andReturn( '' );
        $this->wpdb->shouldReceive( 'get_row' )->andReturnNull();
        $this->wpdb->insert_id = 1;
        $this->wpdb->shouldReceive( 'insert' )->andReturn( 1 );
        Functions\when( 'update_post_meta' )->justReturn( true );

        GeoPress::update_post( 5 );
    }
}
