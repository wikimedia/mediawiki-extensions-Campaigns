/**
 * If server detected a campaign, set a cookie.
 *
 * @module ext.campaigns.js
 * @author S Page <spage@wikimedia.org>
 */
( function ( mw, $ ) {
	'use strict';

	var campaign = mw.config.get( 'wgCampaignsCampaign' );

	if ( !campaign ) {
		mw.log.warn( 'No campaign, why did server load me?' );
		return;
	}

	if ( !mw.user.isAnon() ) {
		mw.log.warn( 'User is logged-in, why did server load me?' );
		return;
	}

	// Set a campaign session cookie.
	$.cookie( mw.config.get( 'wgCookiePrefix' ) +'-campaign', campaign,
		{ 'expires': null, 'path': '/' } );

	// TODO (spage, 2013-06-11) We could remove the query string parameter from
	// the browser history state, so users don't unintentionally propagate
	// campaigns by bookmarking or sharing the landing page.

} ( mediaWiki, jQuery ) );
