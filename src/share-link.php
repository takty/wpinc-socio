<?php
/**
 * Share Links of Social Media
 *
 * @package Wpinc Socio
 * @author Takuto Yanagida
 * @version 2024-03-13
 */

declare(strict_types=1);

namespace wpinc\socio;

require_once __DIR__ . '/site-meta.php';

/**
 * The templates of social media sharing links.
 */
define(
	'SOCIAL_MEDIA_LINKS',
	array(
		'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=<U>&amp;t=<T>',
		'twitter'  => 'https://twitter.com/intent/tweet?url=<U>&amp;text=<T>',
		'pocket'   => 'https://getpocket.com/edit?url=<U>&title=<T>',
		'line'     => 'https://line.me/R/msg/text/?<T>%0d%0a<U>',
		'x'        => 'https://twitter.com/intent/tweet?url=<U>&amp;text=<T>',
	)
);

/**
 * The script for 'copy' function.
 */
const JS_ON_COPY_CLICK = "navigator.clipboard.writeText(this.title + ' ' + this.dataset.url);this.classList.add('copied');";

/** phpcs:ignore
 * Outputs share links.
 *
 * phpcs:ignore
 * @param array{
 *     before?             : string,
 *     after?              : string,
 *     before_link?        : string,
 *     after_link?         : string,
 *     do_append_site_name?: bool,
 *     separator?          : string,
 *     media?              : string[],
 * } $args (Optional) Post navigation arguments.
 *
 * $args {
 *     (Optional) Post navigation arguments.
 *
 *     @type string   'before'              Markup to prepend to the all links.
 *     @type string   'after'               Markup to append to the all links.
 *     @type string   'before_link'         Markup to prepend to each link.
 *     @type string   'after_link'          Markup to append to each link.
 *     @type bool     'do_append_site_name' Whether the site name is appended.
 *     @type string   'separator'           Separator between the page title and the site name.
 *     @type string[] 'media'               Social media names.
 * }
 */
function the_share_links( array $args = array() ): void {
	$args += array(
		'before'              => '<ul>',
		'after'               => '</ul>',
		'before_link'         => '<li>',
		'after_link'          => '</li>',
		'do_append_site_name' => true,
		'separator'           => ' - ',
		'media'               => array( 'facebook', 'x', 'pocket', 'line', 'copy', 'feed' ),
	);
	$title = \wpinc\socio\get_the_title( $args['do_append_site_name'], $args['separator'] );
	$url   = (string) \wpinc\get_current_url();

	$search  = array( '<T>', '<U>' );
	$replace = array( rawurlencode( $title ), rawurlencode( $url ) );

	$ret = '';
	foreach ( $args['media'] as $lab => $media ) {
		$href = SOCIAL_MEDIA_LINKS[ $media ] ?? '';
		$lab  = is_string( $lab ) ? $lab : ucfirst( $media );
		$link = '';
		if ( '' !== $href ) {
			$href = str_replace( $search, $replace, $href );
			$link = sprintf( '<a href="%s">%s</a>', esc_url( $href ), $lab );
		} elseif ( 'feed' === $media ) {
			list( 'text' => $text, 'href' => $href ) = _get_feed_link();
			if ( '' !== $href ) {
				$link = sprintf( '<a href="%s" title="%s">%s</a>', esc_url( $href ), esc_attr( $text ), $lab );
			}
		} elseif ( 'copy' === $media ) {
			$link = sprintf( '<a data-url="%s" title="%s" onclick="%s">%s</a>', esc_url( $url ), esc_attr( $title ), JS_ON_COPY_CLICK, $lab );
		}
		if ( '' !== $link ) {
			$ret .= $args['before_link'] . $link . $args['after_link'] . "\n";
		}
	}
	$tags = wp_kses_allowed_html( 'post' );

	$tags['a']['onclick'] = true;
	echo wp_kses( $args['before'] . "\n$ret" . $args['after'] . "\n", $tags );
}

/**
 * Retrieves links to the feeds.
 *
 * @access private
 *
 * @return array{ text: string, href: string } Feed title and href.
 */
function _get_feed_link(): array {
	$temps = array(
		/* translators: Separator between blog name and feed type in feed links. */
		'separator' => _x( '&raquo;', 'feed link' ),
		/* translators: 1: Blog name, 2: Separator (raquo), 3: Post type name. */
		'archive'   => __( '%1$s %2$s %3$s Feed' ),
		/* translators: 1: Blog name, 2: Separator (raquo), 3: Term name, 4: Taxonomy singular name. */
		'tx'        => __( '%1$s %2$s %3$s %4$s Feed' ),
		/* translators: 1: Blog name, 2: Separator (raquo), 3: Search query. */
		'search'    => __( '%1$s %2$s Search Results for &#8220;%3$s&#8221; Feed' ),
	);

	$text = '';
	$href = '';
	if ( is_post_type_archive() ) {
		$post_type = get_query_var( 'archive' );
		if ( is_array( $post_type ) ) {
			$post_type = reset( $post_type );
		}
		$pto  = get_post_type_object( $post_type );
		$ptn  = $pto ? $pto->labels->name : '';
		$text = sprintf( $temps['archive'], get_bloginfo( 'name' ), $temps['separator'], $ptn );
		$href = get_post_type_archive_feed_link( $post_type );
	} elseif ( is_tax() ) {
		$t = get_queried_object();
		if ( $t && $t instanceof \WP_Term ) {
			$tx   = get_taxonomy( $t->taxonomy );
			$txn  = $tx ? $tx->labels->singular_name : '';
			$text = sprintf( $temps['tx'], get_bloginfo( 'name' ), $temps['separator'], $t->name, $txn );
			$href = get_term_feed_link( $t->term_id, $t->taxonomy );
		}
	} elseif ( is_search() ) {
		$text = sprintf( $temps['search'], get_bloginfo( 'name' ), $temps['separator'], get_search_query( false ) );
		$href = get_search_feed_link();
	}
	if ( ! is_string( $href ) ) {
		$href = '';
	}
	return compact( 'text', 'href' );
}
