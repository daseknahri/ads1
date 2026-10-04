<?php
/**
 * Plugin Name: Get Emoji — speed
 * Description: YouTube click-to-play facade. A video embed otherwise pulls ~1 MB of YouTube JavaScript on every
 *              page view, even when nobody presses play. Until the visitor clicks, the post shows the video's
 *              thumbnail with a play button; the real (privacy-friendly youtube-nocookie) player loads on click.
 *              GE_YT_FACADE=off (environment) disables it.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Set when at least one facade was rendered on this request, so the click script is only printed when needed. */
$GLOBALS['ge_yt_facade_used'] = false;

function ge_yt_facade_enabled() {
	if ( 'off' === trim( (string) getenv( 'GE_YT_FACADE' ) ) ) { return false; }
	if ( is_admin() || is_feed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { return false; }
	return true;
}

add_filter( 'the_content', function ( $html ) {
	if ( ! ge_yt_facade_enabled() || false === stripos( $html, '<iframe' ) ) { return $html; }
	return preg_replace_callback(
		'#<iframe\b([^>]*?)\bsrc=["\'](?:https?:)?//(?:www\.)?youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{11})[^"\']*["\']([^>]*)>\s*</iframe>#i',
		function ( $m ) {
			$id    = $m[2];
			$title = '';
			if ( preg_match( '#\btitle=["\']([^"\']*)["\']#i', $m[1] . ' ' . $m[3], $t ) ) { $title = html_entity_decode( $t[1], ENT_QUOTES, 'UTF-8' ); }
			if ( '' === $title ) { $title = 'YouTube video'; }
			$GLOBALS['ge_yt_facade_used'] = true;
			return '<a class="ge-yt" href="https://www.youtube.com/watch?v=' . esc_attr( $id ) . '" data-id="' . esc_attr( $id ) . '" data-title="' . esc_attr( $title ) . '" aria-label="' . esc_attr( 'Play video: ' . $title ) . '">'
				. '<img src="https://i.ytimg.com/vi/' . esc_attr( $id ) . '/hqdefault.jpg" alt="" data-no-pin width="480" height="360" loading="lazy" decoding="async" fetchpriority="low">'
				. '<span class="ge-yt__play" aria-hidden="true"></span></a>'
				. '<noscript><iframe title="' . esc_attr( $title ) . '" width="680" height="383" src="https://www.youtube-nocookie.com/embed/' . esc_attr( $id ) . '?feature=oembed" frameborder="0" allowfullscreen></iframe></noscript>';
		},
		$html
	);
}, 20 );

add_action( 'wp_head', function () {
	if ( ! ge_yt_facade_enabled() ) { return; }
	echo '<style id="ge-yt-css">'
		. '.ge-yt{display:block;position:relative;width:100%;aspect-ratio:16/9;background:#000;overflow:hidden;border-radius:var(--radius,12px);text-decoration:none;cursor:pointer}'
		/* Self-contained 16:9 box: neutralise core's padding-top aspect hack (it assumes an absolutely-positioned iframe). */
		. '.wp-block-embed__wrapper:has(.ge-yt)::before{content:none!important;display:none!important}'
		. '.ge-yt img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.92;transition:opacity .2s}'
		. '.ge-yt__play{position:absolute;left:50%;top:50%;width:68px;height:68px;margin:-34px 0 0 -34px;border-radius:50%;background:rgba(0,0,0,.62);box-shadow:inset 0 0 0 3px rgba(255,255,255,.92);transition:background .2s}'
		. '.ge-yt__play::after{content:"";position:absolute;left:27px;top:20px;border-style:solid;border-width:14px 0 14px 22px;border-color:transparent transparent transparent #fff}'
		. '.ge-yt:hover img,.ge-yt:focus-visible img{opacity:1}.ge-yt:hover .ge-yt__play,.ge-yt:focus-visible .ge-yt__play{background:rgba(176,69,94,.94)}'
		. '.ge-yt:focus-visible{outline:3px solid #B0455E;outline-offset:2px}'
		. '</style>' . "\n";
}, 99 );

add_action( 'wp_footer', function () {
	if ( empty( $GLOBALS['ge_yt_facade_used'] ) ) { return; }
	echo "<script id=\"ge-yt-js\">document.addEventListener('click',function(e){var a=e.target.closest&&e.target.closest('a.ge-yt');if(!a){return;}e.preventDefault();var f=document.createElement('iframe');f.src='https://www.youtube-nocookie.com/embed/'+a.getAttribute('data-id')+'?autoplay=1&rel=0&feature=oembed';f.title=a.getAttribute('data-title')||'YouTube video';f.allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';f.setAttribute('allowfullscreen','');f.referrerPolicy='strict-origin-when-cross-origin';f.style.cssText='position:static;width:100%;height:auto;aspect-ratio:16/9;border:0;display:block';a.replaceWith(f);f.focus();});</script>\n";
}, 99 );
