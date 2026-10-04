<?php
/**
 * Plugin Name: Get Emoji — ad layout
 * Description: Fixed-size, mobile-first AdSense layout (replaces Google's automatic in-page ads). Responsive "auto"
 *              units resize themselves after the page paints, and Auto ads drop 8-9 containers into every page, which
 *              shoves the content around (layout shift) and costs seconds of JavaScript. Here every slot has a size
 *              reserved up front: 300x250 on phones, a slim 728x90 on desktop (decided on the server, so a desktop
 *              visitor never loads the big format), and a "keep reading" card of the same size replaces the top ad when
 *              Google has none. The loader script comes from Site Kit; the placements are driven through the plugin's
 *              own ad settings (option filter below), so nothing is stored in the database.
 *              GE_ADS=off (environment) hands control back to the plugin settings / Auto ads.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const GE_ADSENSE_CLIENT = 'ca-pub-6869205417923902';
/* Ad units (AdSense → Ads → By ad unit). */
const GE_SLOT_TOP       = '7665278074';   /* LL - top (under header) */
const GE_SLOT_FEED      = '7396675601';   /* LL - in feed */
const GE_SLOT_SIDEBAR   = '9489054690';   /* LL - sidebar */
const GE_SLOT_INSTORY   = '6654084358';   /* LL - in article after intro */
const GE_SLOT_REPEAT    = '1406461252';   /* LL - in article repeat */
const GE_SLOT_MULTIPLEX = '3314528585';   /* LL - end of article (multiplex) */

function ge_ads_on() {
	return 'off' !== trim( (string) getenv( 'GE_ADS' ) );
}

function ge_ad_client() {
	$c = trim( (string) getenv( 'GE_ADSENSE_CLIENT' ) );
	return preg_match( '/^ca-pub-\d{10,20}$/', $c ) ? $c : GE_ADSENSE_CLIENT;
}

/* Fixed-size display unit: 300x250 on phones, 728x90 on desktop. */
function ge_ad_ins( $slot ) {
	$size = wp_is_mobile() ? array( 300, 250 ) : array( 728, 90 );
	return '<ins class="adsbygoogle" style="display:inline-block;width:' . $size[0] . 'px;height:' . $size[1] . 'px" data-ad-client="' . esc_attr( ge_ad_client() ) . '" data-ad-slot="' . esc_attr( $slot ) . '"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
}

/* "Keep reading" card shown in the top strip when Google has no ad for a view. Same size as the ad. */
function ge_top_fallback() {
	static $html = null;
	if ( null !== $html ) { return $html; }
	if ( ! did_action( 'wp' ) ) { return ''; }   /* query not ready: don't cache an empty answer, try again later */
	$html = '';
	$args = array( 'numberposts' => 1, 'ignore_sticky_posts' => true, 'no_found_rows' => true );
	if ( is_singular() ) {
		$args['post__not_in'] = array( get_queried_object_id() );
		$cats = is_singular( 'post' ) ? wp_get_post_categories( get_queried_object_id() ) : array();
		if ( $cats ) { $args['category__in'] = $cats; }
	}
	$found = get_posts( $args );
	if ( ! $found && ! empty( $args['category__in'] ) ) {
		unset( $args['category__in'] );
		$found = get_posts( $args );
	}
	if ( $found ) {
		$html = '<a class="ge-top-fb" href="' . esc_url( get_permalink( $found[0] ) ) . '"><span>Keep reading</span><strong>' . esc_html( get_the_title( $found[0] ) ) . '</strong><em>Read next &rarr;</em></a>';
	}
	return $html;
}

/* The plugin's ad settings, produced in code. Slots: one in-article ad after the intro, more every 6 blocks on phones
   only, a multiplex grid at the end of the story; zones: top strip under the header and the sidebar. */
function ge_ads_option( $opt ) {
	if ( ! ge_ads_on() || is_admin() ) { return $opt; }
	$opt    = is_array( $opt ) ? $opt : array();
	$client = ge_ad_client();
	$mobile = wp_is_mobile();
	$push   = '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
	$story  = function ( $slot ) use ( $client, $push ) {
		return '<ins class="adsbygoogle" style="display:block;text-align:center" data-ad-layout="in-article" data-ad-format="fluid" data-ad-client="' . $client . '" data-ad-slot="' . $slot . '"></ins>' . $push;
	};
	$multiplex = '<ins class="adsbygoogle" style="display:block" data-ad-format="autorelaxed" data-matched-content-ui-type="image_stacked,image_stacked" data-matched-content-rows-num="3,2" data-matched-content-columns-num="2,4" data-ad-client="' . $client . '" data-ad-slot="' . GE_SLOT_MULTIPLEX . '"></ins>' . $push;

	$opt['enabled']      = 1;
	$opt['inject_front'] = 1;   /* build-final: explicit opt-in for in-article ads */
	$opt['scope_all']    = 1;
	$opt['auto_front']   = 0;   /* Site Kit already prints the one adsbygoogle.js loader */
	$opt['auto_code']    = '';
	$opt['min_gap']      = 3;
	$opt['max_ads']      = 3;
	$opt['label']        = 0;   /* the theme prints the "Advertisement" label itself */
	$opt['slots']        = array(
		'top'       => array( 'on' => 0, 'code' => '' ),
		'incontent' => array( 'on' => 1, 'code' => $story( GE_SLOT_INSTORY ), 'after' => 2 ),
		'repeat'    => array( 'on' => $mobile ? 1 : 0, 'code' => $story( GE_SLOT_REPEAT ), 'every' => 6, 'max' => 2 ),
		'bottom'    => array( 'on' => 1, 'code' => $multiplex ),
	);
	$top = '<div class="ge-top ' . ( $mobile ? 'ge-top--m' : 'ge-top--d' ) . '">' . ge_ad_ins( GE_SLOT_TOP ) . ge_top_fallback() . '</div>';
	$opt['zones']        = array(
		'header'  => array( 'on' => 1, 'code' => $top ),
		'sidebar' => array( 'on' => 1, 'code' => '<ins class="adsbygoogle" style="display:inline-block;width:300px;height:250px" data-ad-client="' . $client . '" data-ad-slot="' . GE_SLOT_SIDEBAR . '"></ins>' . $push ),
		'footer'  => array( 'on' => 0, 'code' => '' ),
	);
	return $opt;
}
add_filter( 'option_wpap_ads_inject', 'ge_ads_option', 20 );
add_filter( 'default_option_wpap_ads_inject', 'ge_ads_option', 20 );   /* option row not saved yet */

/* Phone and desktop get different ad sizes from the same URL: tell any cache in between. */
add_action( 'send_headers', function () { if ( ge_ads_on() ) { header( 'Vary: User-Agent', false ); } } );

/* In-feed ads: one after every 4 story cards on the home page and archive lists (3 on phones, 1 on desktop). */
const GE_FEED_EVERY = 4;
add_action( 'the_post', function ( $post, $query ) {
	static $counts = array();
	if ( ! ge_ads_on() || is_admin() || is_singular() || ! ( is_home() || is_front_page() || is_archive() || is_search() ) ) { return; }
	if ( 'post' !== $post->post_type || ! function_exists( 'wpap_get_ads' ) ) { return; }
	$key            = spl_object_hash( $query );
	$counts[ $key ] = isset( $counts[ $key ] ) ? $counts[ $key ] + 1 : 1;
	$n              = $counts[ $key ];
	$max            = wp_is_mobile() ? 3 : 1;
	/* $n is the card about to render; insert before cards 5, 9, 13 (after every 4th). */
	if ( $n <= 1 || 0 !== ( $n - 1 ) % GE_FEED_EVERY || ( $n - 1 ) / GE_FEED_EVERY > $max ) { return; }
	$ads = wpap_get_ads();
	if ( empty( $ads['enabled'] ) ) { return; }
	echo '<div class="ge-feed-ad"><div class="wpap-ad wpap-ad-feed">' . ge_ad_ins( GE_SLOT_FEED ) . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in ge_ad_ins()
}, 10, 2 );

/* Layout CSS. Top strip = a FIXED box (phone: 300x250 ad + label; desktop: 728x90 + label). The parent theme hides the
   whole strip when Google serves no ad, which made the page jump up; here the box stays and the same-size card shows. */
add_action( 'wp_head', function () {
	if ( ! ge_ads_on() ) { return; }
	$u = 'ins.adsbygoogle[data-ad-status="unfilled"]';
	echo '<style id="ge-ads-css">'
		. '.vr-ad-strip__inner:has(.ge-top){position:relative;overflow:hidden}'
		. '.vr-ad-strip__inner:has(.ge-top--m){width:100%;height:300px;min-height:300px;padding:12px 0}'
		. '.vr-ad-strip__inner:has(.ge-top--d){height:140px;min-height:140px}'
		. '.vr-ad-strip .wpap-ad-zone-header:has(.ge-top){position:static}'
		. '.vr-ad-strip:has(.ge-top):has(' . $u . '){display:block}'
		. '.wpap-ad-zone-header:has(.ge-top):has(' . $u . '){display:block}'
		. '.wpap-ad-zone-header:has(.ge-top):has(' . $u . ')::before{visibility:hidden}'
		. '.ge-top ' . $u . '{visibility:hidden}'
		. '.ge-top-fb{display:none;position:absolute;inset:12px;flex-direction:column;align-items:center;justify-content:center;gap:.4rem;padding:14px 18px;text-align:center;text-decoration:none;color:var(--ink);background:var(--surface);border:1px solid var(--line);border-radius:var(--radius)}'
		. '.ge-top-fb:hover{text-decoration:none;color:var(--ink)}'
		. '.ge-top-fb span{font:700 .64rem/1 var(--sans);letter-spacing:.14em;text-transform:uppercase;color:var(--eyebrow,var(--clay))}'
		. '.ge-top-fb strong{font-family:var(--serif);font-size:1.18rem;line-height:1.3;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden}'
		. '.ge-top-fb em{font:600 .95rem/1 var(--sans);font-style:normal;color:var(--olive-dark,var(--clay))}'
		. '.vr-ad-strip__inner:has(.ge-top--d) .ge-top-fb{flex-direction:row;gap:1rem;padding:8px 18px}'
		. '.vr-ad-strip__inner:has(.ge-top--d) .ge-top-fb strong{-webkit-line-clamp:2;font-size:1.05rem}'
		/* Fallback shows when Google says "unfilled", or if no ad status arrives within 4 s (ad blocker, slow network). */
		. '.vr-ad-strip__inner:has(.ge-top):has(' . $u . ') .ge-top-fb{display:flex}'
		. '.vr-ad-strip__inner:has(.ge-top):not(:has(ins.adsbygoogle[data-ad-status])) .ge-top-fb{display:flex;visibility:hidden;animation:ge-fb-in 0s linear 4s forwards}'
		. '@keyframes ge-fb-in{to{visibility:visible}}'
		/* In-feed ad spans the whole card row. In-story fluid ads reserve a sensible minimum. */
		. '.ge-feed-ad{grid-column:1/-1;min-width:0;margin:.4rem 0}.ge-feed-ad .wpap-ad{margin:0 auto;max-width:728px}'
		. '.entry-content .wpap-ad-incontent ins.adsbygoogle,.entry-content .wpap-ad-repeat ins.adsbygoogle{min-height:250px}'
		. '</style>' . "\n";
}, 99 );
