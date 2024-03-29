<?php
/**
 * Site Information
 *
 * @package Wpinc Socio
 * @author Takuto Yanagida
 * @version 2024-03-13
 */

declare(strict_types=1);

namespace wpinc\socio;

require_once __DIR__ . '/assets/url.php';

/**
 * Sets site icon (favicon).
 *
 * @param string                  $dir_url The url to image directory.
 * @param array<int, string>|null $icons   Array of icon sizes to icon file names.
 */
function set_site_icon( string $dir_url, ?array $icons = null ): void {
	$dir_url = trailingslashit( $dir_url );
	if ( null === $icons ) {
		$icons = array(
			32  => 'favicon.ico',   // Default favicon.
			180 => 'icon-180.png',  // Apple touch icon.
			270 => '',              // Windows tile image (not used).
			192 => 'icon-192.png',  // Android icon.
			512 => 'icon-192.png',  // Tp pass through 'has_site_icon' function.
		);
	}
	add_filter(
		'get_site_icon_url',
		function ( $_url, $size ) use ( $dir_url, $icons ) {
			$icon = $icons[ $size ] ?? '';
			if ( $icon ) {
				return _add_timestamp( $dir_url . $icon );
			}
			return '';
		},
		10,
		2
	);
}


// -----------------------------------------------------------------------------


/**
 * Outputs the site description.
 */
function the_site_description(): void {
	echo '<meta name="description" content="' . esc_attr( get_site_description() ) . '">' . "\n";
}


// -----------------------------------------------------------------------------


/**
 * Retrieves the title of the current page.
 *
 * @psalm-suppress NullableReturnStatement, PossiblyNullOperand, InvalidNullableReturnType
 *
 * @param bool   $do_append_site_name Whether the site name is appended.
 * @param string $separator           Separator between the page title and the site name.
 * @return string The title.
 */
function get_the_title( bool $do_append_site_name, string $separator ): string {
	$site_name = get_site_name();
	if ( ! is_front_page() && is_singular() ) {
		$title = _strip_custom_tags( \get_the_title() );
		if ( $do_append_site_name ) {
			$title .= $separator . $site_name;
		}
		return $title;
	} elseif ( is_archive() ) {
		$title = '';
		if ( is_year() ) {
			$title = (string) get_the_date( _x( 'Y', 'yearly archives date format' ) );
		} elseif ( is_month() ) {
			$title = (string) get_the_date( _x( 'F Y', 'monthly archives date format' ) );
		} elseif ( is_day() ) {
			$title = (string) get_the_date();
		} elseif ( is_post_type_archive() ) {
			$title = (string) post_type_archive_title( '', false );
		} elseif ( is_tax() ) {
			$title = (string) single_term_title( '', false );
		}
		if ( is_date() || is_tax() ) {
			$pt = get_post_type();
			if ( is_string( $pt ) ) {
				$pto = get_post_type_object( $pt );
				if ( $pto && $pto->label ) {
					$title .= $separator . $pto->label;
				}
			}
		}
		if ( '' !== $title ) {
			if ( $do_append_site_name ) {
				$title .= $separator . $site_name;
			}
			return $title;
		}
	}
	return $site_name;
}

/**
 *
 * Retrieves the website name.
 *
 * @return string The name of the website.
 */
function get_site_name(): string {
	$ret = get_bloginfo( 'name' );
	$ret = _strip_custom_tags( $ret );
	return $ret;
}

/**
 *
 * Retrieves the website description.
 *
 * @return string The description of the website.
 */
function get_site_description(): string {
	$ret = get_bloginfo( 'description' );
	$ret = _strip_custom_tags( $ret );
	return $ret;
}


// -----------------------------------------------------------------------------


/**
 * Strips all tags and custom 'br'.
 *
 * @access private
 *
 * @param string $text The text.
 * @return string The stripped text.
 */
function _strip_custom_tags( string $text ): string {
	// Replace double full-width spaces and br tags to single space.
	$text = preg_replace( '/　　|<\s*br\s*\/?>/ui', ' ', $text ) ?? $text;
	$text = wp_strip_all_tags( $text, true );
	return $text;
}

/**
 * Adds timestamp for ensuring updating resources.
 *
 * @param string $src URL.
 * @return string URL that timestamp (hash) is appended as a query.
 */
function _add_timestamp( string $src ): string {
	if ( strpos( $src, get_template_directory_uri() ) === false ) {
		return $src;
	}
	$removed_src = strtok( $src, '?' );
	if ( false === $removed_src ) {
		$removed_src = $src;
	}
	$path          = wp_normalize_path( ABSPATH );
	$resource_file = str_replace( trailingslashit( site_url() ), trailingslashit( $path ), $removed_src );
	$resource_file = realpath( $resource_file );
	if ( false === $resource_file ) {
		return $src;
	}
	$fmt = filemtime( $resource_file );
	if ( false === $fmt ) {
		return $src;
	}
	$fts  = gmdate( 'Ymdhis', $fmt );
	$hash = hash( 'crc32b', $resource_file . $fts );
	return add_query_arg( 'v', $hash, $src );
}
