<?php
/**
 * Site health helper.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Mailarchiver\System;

use Mailarchiver\System\Cache;
use Mailarchiver\System\I18n;
use Mailarchiver\System\Option;

/**
 * The class responsible to handle cache management.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Sitehealth {

	/**
	 * The slug of the calling plugin.
	 *
	 * @since  1.0.0
	 * @var    string    $slug    The slug.
	 */
	private static $slug = MAILARCHIVER_SLUG;

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Initializes properties.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::perfopsone_init();
		self::plugin_init();
	}

	/**
	 * Sets site health hooks for PerfOps One.
	 *
	 * @since 1.0.0
	 */
	private static function perfopsone_init() {
		self::perfopsone_test_init();
		self::perfopsone_info_init();
	}

	/**
	 * Sets site health hooks specific to this plugin.
	 *
	 * @since 1.0.0
	 */
	private static function plugin_init() {
		self::plugin_test_init();
		self::plugin_info_init();
	}

	/**
	 * Sets site health hooks for PerfOps One tests.
	 *
	 * @since 1.0.0
	 */
	private static function perfopsone_test_init() {
		add_filter( 'site_status_tests', [ self::class, 'perfopsone_test_objectcache' ] );
		add_filter( 'site_status_tests', [ self::class, 'perfopsone_test_opcache' ] );
		if ( 'en_US' !== get_locale() ) {
			add_filter( 'site_status_tests', [ self::class, 'perfopsone_test_i18n' ] );
		}
	}

	/**
	 * Sets site health hooks for PerfOps One infos.
	 *
	 * @since 1.0.0
	 */
	private static function perfopsone_info_init() {
		add_filter( 'debug_information', [ self::class, 'perfopsone_info' ] );
	}

	/**
	 * Sets site health hooks for plugin tests.
	 *
	 * @since 1.0.0
	 */
	private static function plugin_test_init() {

	}

	/**
	 * Sets site health hooks for plugin infos.
	 *
	 * @since 1.0.0
	 */
	private static function plugin_info_init() {
		add_filter( 'debug_information', [ self::class, 'plugin_info' ] );
	}

	/**
	 * Adds plugin infos section.
	 *
	 * @param array $debug_info The already set infos.
	 * @return array    The extended infos if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_info( $debug_info ) {
		$key = 'perfopsone_objectcache';
		if ( ! array_key_exists( $key, $debug_info ) ) {
			$debug_info[ $key ] = [
				'label'  => esc_html__( 'Object cache', 'mailarchiver' ),
				'fields' => Cache::debug_info(),
			];
		}
		$key = 'perfopsone_opcache';
		if ( ! array_key_exists( $key, $debug_info ) ) {
			$debug_info[ $key ] = [
				'label'       => 'OPcache',
				'description' => esc_html__( 'OPcache settings and status', 'mailarchiver' ),
				'fields'      => OPcache::debug_info(),
			];
		}
		return $debug_info;
	}

	/**
	 * Adds plugin infos section.
	 *
	 * @param array $debug_info The already set infos.
	 * @return array    The extended infos if needed.
	 * @since 1.0.0
	 */
	public static function plugin_info( $debug_info ) {
		$debug_info[ self::$slug ] = [
			'label'       => MAILARCHIVER_PRODUCT_NAME,
			'description' => esc_html__( 'Plugin diagnostic information', 'mailarchiver' ),
			'fields'      => Option::debug_info(),
		];
		return $debug_info;
	}

	/**
	 * Adds a test.
	 *
	 * @param array $tests The already set tests.
	 * @return array    The extended tests if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_objectcache( $tests ) {
		$key = 'perfopsone_objectcache';
		if ( ! array_key_exists( $key, $tests['direct'] ) ) {
			$tests['direct'][ $key ] = [
				'label' => __( 'Object Cache Test', 'mailarchiver' ),
				'test'  => [ self::class, 'perfopsone_test_objectcache_do' ],
			];
		}
		return $tests;
	}

	/**
	 * Does a test.
	 *
	 * @return array    The result of the test.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_objectcache_do() {
		$key       = 'perfopsone_objectcache';
		$analytics = Cache::get_analytics();
		if ( 'db_transient' === $analytics['type'] ) {
			$result = [
				'label'       => esc_html__( 'You should use object caching', 'mailarchiver' ),
				'status'      => 'recommended',
				'badge'       => [
					'label' => esc_html__( 'Performance', 'mailarchiver' ),
					'color' => 'orange',
				],
				'description' => sprintf( '<p>%s %s</p>', esc_html__( 'Your site uses database transient.', 'mailarchiver' ), esc_html__( 'You should consider using a dedicated object caching mechanism, like Memcached or Redis, to improve your site\'s speed.', 'mailarchiver' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		} elseif ( 'apcu' === $analytics['type'] ) {
			$result = [
				'label'       => esc_html__( 'You should improve object caching', 'mailarchiver' ),
				'status'      => 'recommended',
				'badge'       => [
					'label' => esc_html__( 'Performance', 'mailarchiver' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s %s</p>', esc_html__( 'Your site uses APCu, but only a few plugins know how to take advantage of it.', 'mailarchiver' ), esc_html__( 'You should consider using a dedicated object caching mechanism, like Memcached or Redis, to improve your site\'s speed.', 'mailarchiver' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		} elseif ( 'object_cache' === $analytics['type'] ) {
			$result = [
				'label'       => esc_html__( 'Your site uses object caching', 'mailarchiver' ),
				'status'      => 'good',
				'badge'       => [
					'label' => esc_html__( 'Performance', 'mailarchiver' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s</p>', esc_html__( 'Your site uses a dedicated object caching mechanism. That\'s great.', 'mailarchiver' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		}
		return $result;
	}

	/**
	 * Adds a test.
	 *
	 * @param array $tests The already set tests.
	 * @return array    The extended tests if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_opcache( $tests ) {
		$key = 'perfopsone_opcache';
		if ( ! array_key_exists( $key, $tests['direct'] ) ) {
			$tests['direct'][ $key ] = [
				'label' => __( 'OPcache Test', 'mailarchiver' ),
				'test'  => [ self::class, 'perfopsone_test_opcache_do' ],
			];
		}
		return $tests;
	}

	/**
	 * Does a test.
	 *
	 * @return array    The result of the test.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_opcache_do() {
		$key = 'perfopsone_opcache';
		if ( function_exists( 'opcache_invalidate' ) && function_exists( 'opcache_compile_file' ) && function_exists( 'opcache_is_script_cached' ) ) {
			$result = [
				'label'       => esc_html__( 'Your site uses OPcache', 'mailarchiver' ),
				'status'      => 'good',
				'badge'       => [
					'label' => esc_html__( 'Performance', 'mailarchiver' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s</p>', esc_html__( 'Your site uses OPcache to improve PHP performance. That\'s great.', 'mailarchiver' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		} else {
			$result = [
				'label'       => esc_html__( 'You should use OPcache', 'mailarchiver' ),
				'status'      => 'recommended',
				'badge'       => [
					'label' => esc_html__( 'Performance', 'mailarchiver' ),
					'color' => 'orange',
				],
				'description' => sprintf( '<p>%s</p>', esc_html__( 'You should consider using OPcache. It would improve PHP performance of your site.', 'mailarchiver' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		}
		return $result;
	}

	/**
	 * Adds a test.
	 *
	 * @param array $tests The already set tests.
	 * @return array    The extended tests if needed.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_i18n( $tests ) {
		$key = 'perfopsone_i18n';
		if ( ! array_key_exists( $key, $tests['direct'] ) ) {
			$tests['direct'][ $key ] = [
				'label' => __( 'I18n Extension Test', 'mailarchiver' ),
				'test'  => [ self::class, 'perfopsone_test_i18n_do' ],
			];
		}
		return $tests;
	}

	/**
	 * Does a test.
	 *
	 * @return array    The result of the test.
	 * @since 1.0.0
	 */
	public static function perfopsone_test_i18n_do() {
		$key = 'perfopsone_i18n';
		if ( I18n::is_extension_loaded() ) {
			$result = [
				'label'       => esc_html__( 'Your site uses Intl extension', 'mailarchiver' ),
				'status'      => 'good',
				'badge'       => [
					'label' => esc_html__( 'Internationalization', 'mailarchiver' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s</p>', esc_html__( 'Your site uses PHP Intl extension to improve localization features. That\'s great.', 'mailarchiver' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		} else {
			$result = [
				'label'       => esc_html__( 'You should use Intl extension', 'mailarchiver' ),
				'status'      => 'recommended',
				'badge'       => [
					'label' => esc_html__( 'Internationalization', 'mailarchiver' ),
					'color' => 'blue',
				],
				'description' => sprintf( '<p>%s</p>', esc_html__( 'You should consider using PHP Intl extension. It would improve localization features on your site.', 'mailarchiver' ) ),
				'actions'     => '',
				'test'        => $key,
			];
		}
		return $result;
	}

}
