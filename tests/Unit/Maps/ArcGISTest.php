<?php
/**
 * Tests for the ArcGIS Maps SDK 5.0 provider.
 *
 * Covers the pure option-reading helpers, feature-layer HTML, the
 * standalone web-map embed, and the front-end config injection performed
 * by GeoPress::enqueue_scripts() when the provider is set to 'arcgis'.
 */

use Yoast\WPTestUtils\BrainMonkey\TestCase;
use Brain\Monkey\Functions;

class ArcGISTest extends TestCase {

    /**
     * Declares the WordPress helpers the ArcGIS code calls. Per the suite's
     * convention these live in each test rather than tests/bootstrap.php,
     * where Patchwork could not redefine them.
     */
    protected function setUp(): void {
        parent::setUp();

        $escape = static function ( $text ) {
            return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
        };
        Functions\when( 'esc_attr' )->alias( $escape );
        Functions\when( 'esc_html' )->alias( $escape );
        Functions\when( 'esc_url' )->alias( $escape );
        Functions\when( 'esc_url_raw' )->returnArg();
        Functions\when( 'sanitize_text_field' )->alias(
            static function ( $str ) {
                return trim( strip_tags( (string) $str ) );
            }
        );
        Functions\when( 'is_wp_error' )->justReturn( false );
        Functions\when( 'wp_remote_retrieve_body' )->alias(
            static function ( $response ) {
                return is_array( $response ) && isset( $response['body'] ) ? $response['body'] : '';
            }
        );
    }

    /**
     * Returns a callable to drive Functions\when('get_option')->alias() that
     * resolves $key from the supplied map, falling back to the test's $default.
     *
     * @param array $values  Map of option key => stored value.
     */
    private function optionResolver( array $values ): callable {
        return function ( $key, $default = false ) use ( $values ) {
            return array_key_exists( $key, $values ) ? $values[ $key ] : $default;
        };
    }

    /**
     * Stubs the asset calls the standalone embeds make. Kept out of setUp()
     * because the enqueue test sets its own expectation on
     * wp_add_inline_script().
     */
    private function stubArcGISAssets( bool $already_enqueued = false ): void {
        Functions\when( 'wp_script_is' )->justReturn( $already_enqueued );
        Functions\when( 'wp_enqueue_style' )->justReturn( null );
        Functions\when( 'wp_enqueue_script' )->justReturn( null );
        Functions\when( 'wp_add_inline_script' )->justReturn( true );
        Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
    }

    public function test_arcgis_options_returns_osm_default_basemap(): void {
        Functions\when( 'get_option' )->alias( $this->optionResolver( array() ) );

        $opts = geopress_arcgis_options();

        $this->assertSame( 'https://www.arcgis.com', $opts['portal_url'] );
        $this->assertSame( '',                       $opts['api_key'] );
        $this->assertSame( 'osm',                    $opts['basemap'] );
        $this->assertSame( '',                       $opts['webmap_item_id'] );
        $this->assertSame( '',                       $opts['webscene_item_id'] );
        $this->assertSame( '',                       $opts['feature_layer_url'] );
        $this->assertSame( '',                       $opts['feature_layer_item_id'] );
    }

    public function test_arcgis_options_reflects_stored_values(): void {
        Functions\when( 'get_option' )->alias( $this->optionResolver( array(
            '_geopress_arcgis_portal_url'    => 'https://portal.example.com',
            '_geopress_arcgis_api_key'       => 'AAPK-test-key',
            '_geopress_arcgis_basemap'       => 'arcgis/imagery',
            '_geopress_arcgis_webmap_item_id' => 'abc123',
        ) ) );

        $opts = geopress_arcgis_options();

        $this->assertSame( 'https://portal.example.com', $opts['portal_url'] );
        $this->assertSame( 'AAPK-test-key',              $opts['api_key'] );
        $this->assertSame( 'arcgis/imagery',             $opts['basemap'] );
        $this->assertSame( 'abc123',                     $opts['webmap_item_id'] );
    }

    public function test_feature_layer_html_returns_url_variant(): void {
        $html = geopress_arcgis_feature_layer_html( 'https://services.arcgis.com/abc/FeatureServer/0', '' );

        $this->assertStringContainsString( '<arcgis-feature-layer', $html );
        $this->assertStringContainsString( 'url="https://services.arcgis.com/abc/FeatureServer/0"', $html );
        $this->assertStringNotContainsString( 'item-id=', $html );
    }

    public function test_feature_layer_html_returns_item_id_variant(): void {
        $html = geopress_arcgis_feature_layer_html( '', 'feature-item-7' );

        $this->assertStringContainsString( '<arcgis-feature-layer', $html );
        $this->assertStringContainsString( 'item-id="feature-item-7"', $html );
        $this->assertStringNotContainsString( 'url=', $html );
    }

    public function test_feature_layer_html_returns_empty_when_no_inputs(): void {
        $this->assertSame( '', geopress_arcgis_feature_layer_html( '', '' ) );
    }

    public function test_webmap_embed_uses_item_id_and_basemap_toggle(): void {
        $this->stubArcGISAssets();
        Functions\when( 'get_option' )->alias( $this->optionResolver( array() ) );

        $html = geopress_arcgis_webmap_embed( 'webmap-xyz', 300, 500 );

        $this->assertStringContainsString( 'item-id="webmap-xyz"', $html );
        $this->assertStringContainsString( '<arcgis-map ',           $html );
        $this->assertStringContainsString( '<arcgis-zoom ',          $html );
        $this->assertStringContainsString( '<arcgis-basemap-toggle ', $html );
        $this->assertStringContainsString( 'height:300px',           $html );
        $this->assertStringContainsString( 'width:500px',            $html );
    }

    public function test_resolve_item_type_returns_webmap_for_web_map_response(): void {
        Functions\when( 'get_option' )->alias( $this->optionResolver( array() ) );
        Functions\when( 'get_transient' )->justReturn( false );
        Functions\when( 'set_transient' )->justReturn( true );
        Functions\when( 'wp_remote_get' )->justReturn( array(
            'body'     => '{"type":"Web Map","title":"London"}',
            'response' => array( 'code' => 200 ),
        ) );

        $this->assertSame( 'webmap', geopress_arcgis_resolve_item_type( 'abc123' ) );
    }

    public function test_resolve_item_type_returns_webscene_for_web_scene_response(): void {
        Functions\when( 'get_option' )->alias( $this->optionResolver( array() ) );
        Functions\when( 'get_transient' )->justReturn( false );
        Functions\when( 'set_transient' )->justReturn( true );
        Functions\when( 'wp_remote_get' )->justReturn( array(
            'body'     => '{"type":"Web Scene","title":"3D city"}',
            'response' => array( 'code' => 200 ),
        ) );

        $this->assertSame( 'webscene', geopress_arcgis_resolve_item_type( 'scene789' ) );
    }

    public function test_resolve_item_type_returns_unknown_for_other_item_types(): void {
        Functions\when( 'get_option' )->alias( $this->optionResolver( array() ) );
        Functions\when( 'get_transient' )->justReturn( false );
        Functions\when( 'set_transient' )->justReturn( true );
        Functions\when( 'wp_remote_get' )->justReturn( array(
            'body'     => '{"type":"Feature Service"}',
            'response' => array( 'code' => 200 ),
        ) );

        $this->assertSame( 'unknown', geopress_arcgis_resolve_item_type( 'feat456' ) );
    }

    public function test_resolve_item_type_uses_transient_cache(): void {
        Functions\when( 'get_transient' )->justReturn( 'webscene' );
        // No wp_remote_get stub on purpose: a cache hit must not touch the network.
        Functions\expect( 'wp_remote_get' )->never();

        $this->assertSame( 'webscene', geopress_arcgis_resolve_item_type( 'cached' ) );
    }

    public function test_map_embed_renders_webscene_when_item_is_webscene(): void {
        $this->stubArcGISAssets();
        Functions\when( 'get_option' )->alias( $this->optionResolver( array() ) );
        Functions\when( 'get_transient' )->justReturn( 'webscene' );

        $html = geopress_arcgis_map_embed( 'scene789', 300, 500 );

        $this->assertStringContainsString( '<arcgis-scene ',  $html );
        $this->assertStringContainsString( 'item-id="scene789"', $html );
        $this->assertStringNotContainsString( '<arcgis-map ', $html );
    }

    public function test_map_embed_defaults_to_webmap_for_unknown_items(): void {
        $this->stubArcGISAssets();
        Functions\when( 'get_option' )->alias( $this->optionResolver( array() ) );
        Functions\when( 'get_transient' )->justReturn( 'unknown' );

        $html = geopress_arcgis_map_embed( 'mystery', 300, 500 );

        $this->assertStringContainsString( '<arcgis-map ',     $html );
        $this->assertStringContainsString( 'item-id="mystery"', $html );
        $this->assertStringNotContainsString( '<arcgis-scene ', $html );
    }

    public function test_embed_loads_arcgis_sdk_when_another_provider_is_active(): void {
        // A site on the default OpenLayers provider never enqueues the SDK at
        // enqueue time, so an INSERT_ARCGIS_MAP embed must load it itself or it
        // renders as an empty element.
        Functions\when( 'get_option' )->alias( $this->optionResolver( array(
            '_geopress_map_format' => 'openlayers',
        ) ) );
        Functions\when( 'wp_script_is' )->justReturn( false );
        Functions\when( 'wp_enqueue_style' )->justReturn( null );
        Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
        Functions\when( 'wp_add_inline_script' )->justReturn( true );

        $enqueued = array();
        Functions\when( 'wp_enqueue_script' )->alias(
            static function ( $handle ) use ( &$enqueued ) {
                $enqueued[] = $handle;
            }
        );

        geopress_arcgis_webmap_embed( 'webmap-xyz', 300, 500 );

        $this->assertContains( 'geopress-arcgis-components', $enqueued );
        $this->assertContains( 'geopress-arcgis-js', $enqueued );
    }

    public function test_embed_does_not_add_config_twice_when_assets_already_enqueued(): void {
        Functions\when( 'get_option' )->alias( $this->optionResolver( array() ) );
        Functions\when( 'wp_script_is' )->justReturn( true );
        Functions\expect( 'wp_enqueue_script' )->never();
        Functions\expect( 'wp_add_inline_script' )->never();

        $html = geopress_arcgis_webmap_embed( 'webmap-xyz', 300, 500 );

        $this->assertStringContainsString( 'item-id="webmap-xyz"', $html );
    }

    public function test_enqueue_scripts_injects_arcgis_config_when_provider_is_arcgis(): void {
        Functions\when( 'get_option' )->alias( $this->optionResolver( array(
            '_geopress_map_format'        => 'arcgis',
            '_geopress_arcgis_api_key'    => 'AAPK-test-key',
            '_geopress_arcgis_portal_url' => 'https://portal.example.com',
        ) ) );

        // No-op handles we don't care about asserting on.
        Functions\when( 'wp_register_script' )->justReturn( true );
        Functions\when( 'wp_register_style' )->justReturn( true );
        Functions\when( 'wp_enqueue_script' )->justReturn( null );
        Functions\when( 'wp_enqueue_style' )->justReturn( null );
        Functions\when( 'add_filter' )->justReturn( true );
        Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
        Functions\when( 'wp_script_is' )->justReturn( false );

        $captured = array();
        Functions\expect( 'wp_add_inline_script' )
            ->once()
            ->with( 'geopress-arcgis-js', \Mockery::capture( $captured ), 'before' );

        GeoPress::enqueue_scripts();

        $this->assertStringContainsString( 'window.geopressArcGISConfig', $captured );
        $this->assertStringContainsString( '"apiKey":"AAPK-test-key"',    $captured );
        $this->assertStringContainsString( '"portalUrl":"https:\/\/portal.example.com"', $captured );
    }
}
