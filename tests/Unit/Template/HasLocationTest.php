<?php
/**
 * Tests for has_location()
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class HasLocationTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();

        $post     = new stdClass();
        $post->ID = 1;
        $GLOBALS['post'] = $post;

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

    public function test_returns_true_when_post_has_location(): void {
        $geo        = new stdClass();
        $geo->coord = '40.7 -74.0';

        Functions\when( 'get_post_meta' )->justReturn( '5' );

        $GLOBALS['wpdb']
            ->shouldReceive( 'prepare' )->once()->andReturn( 'SELECT ...' );
        $GLOBALS['wpdb']
            ->shouldReceive( 'get_row' )->once()->andReturn( $geo );

        $this->assertTrue( has_location() );
    }

    public function test_returns_false_when_post_has_no_location(): void {
        Functions\when( 'get_post_meta' )->justReturn( '' );

        $this->assertFalse( has_location() );
    }
}
