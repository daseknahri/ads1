<?php
/**
 * Plugin Name: Get Emoji — Histats visitor statistics
 * Description: Histats counter in the footer, loaded 1.2 s after the page has finished so it never competes with
 *              content. Front end only; logged-in users are skipped so the owner's own visits don't count.
 *              HISTATS_ID in the environment sets the site id; unset/"off" falls back to this site's id (5064107).
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', function () {
	$id = trim( (string) getenv( 'HISTATS_ID' ) );
	if ( '' === $id || 'off' === $id ) { $id = '5064107'; }
	if ( '' === $id || 'off' === $id || ! ctype_digit( $id ) || is_admin() || is_user_logged_in() ) { return; }
	echo "<script>var _Hasync=_Hasync||[];_Hasync.push(['Histats.start','1," . esc_js( $id ) . ",4,0,0,0,00010000']);_Hasync.push(['Histats.fasi','1']);_Hasync.push(['Histats.track_hits','']);addEventListener('load',function(){setTimeout(function(){var hs=document.createElement('script');hs.async=true;hs.src='//s10.histats.com/js15_as.js';(document.head||document.body).appendChild(hs);},1200);});</script>"
		. '<noscript><img src="//sstatic1.histats.com/0.gif?' . esc_attr( $id ) . '&amp;101" alt="" width="1" height="1" style="position:absolute;left:-9999px"></noscript>' . "\n";
}, 50 );
