<?php

namespace Campaigns;

/**
 * Static methods for hooks.
 */
class Hooks {

	/**
	 * If there's a ?campaign=someName in the query string and the user is not
	 * logged in, send JavaScript with the page to process campaign.
	 *
	 * Don't set the cookie in PHP because we believe the Squid cache will not
	 * send the Set-Cookie header along with a cached version of the page.  The
	 * Squid cache fragments on query string, hence the right campaign value and
	 * JS module will be sent to the client for different ?campaign=foo parameters.
	 *
	 * @param &$template template instance for the form
	 * @return bool True
	 */
	public static function onUserCreateForm( &$template ) {
		global $wgCookiePrefix;
		$maxCampaignLen = 40;

		$skin = $template->getSkin();

		$request = $skin->getRequest();
		$campaign = $request->getVal( 'campaign', '' );
		if ( $campaign === '' || strlen( $campaign ) > $maxCampaignLen ) {
			return true;
		}

		// The version of the page for a logged-in user will not be cached and
		// served to anonymous users, so it's OK to check on server.
		if ( !$skin->getUser()->isAnon() ) {
			return true;
		};

		$out = $skin->getOutput();
		$out->addJsConfigVars( 'wgCampaignsCampaign', $campaign );
		$out->addModules( 'ext.campaigns' );

		return true;
	}

	/**
	 * Log account creation.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/AddNewAccount
	 *
	 * @param User $user The User object that was created.
	 * @param boolean $byEmail The form has a [By e-mail] button.
	 * @return bool True
	 */
	public static function onAddNewAccount( $user, $byEmail ) {
		global $wgEventLoggingSchemaRevs, $wgRequest, $wgUser, $wgCookiePrefix;

		$userId = $user->getId();
		$creatorUserId = $wgUser->getId();

		// MediaWiki allows existing users to create accounts on behalf
		// of others. In such cases the ID of the newly-created user and
		// the ID of the user making this web request are different.
		$isSelfMade = ( $userId && $userId === $creatorUserId );

		$displayMobile = class_exists( 'MobileContext' ) &&
			MobileContext::singleton()->shouldDisplayMobileView();

		$event = array(
			'token' => $wgRequest->getCookie( 'mediaWiki.user.id', '', '' ),
			'userId' => $userId,
			'userName' => $user->getName(),
			'isSelfMade' => $isSelfMade,
			'campaign' =>  $wgRequest->getCookie( '-campaign', $wgCookiePrefix, '' ),
			'userBuckets' => $wgRequest->getCookie( 'userbuckets', '', '' ),
			'displayMobile' => $displayMobile,
		);

		$returnTo = $wgRequest->getVal( 'returnto' );
		if ( $returnTo !== null ) {
			$event[ 'returnTo' ] = $returnTo;
		}

		$returnToQuery = $wgRequest->getVal( 'returntoquery' );
		if ( $returnToQuery !== null ) {
			$event[ 'returnToQuery' ] = $returnToQuery;
		}

		efLogServerSideEvent( 'ServerSideAccountCreation', 5487345, $event );
		return true;
	}

	/**
	 * Adds campaign param to link on login form.
	 *
	 * @param &$template template instance for the form
	 * @return bool True
	 */
	public static function onUserLoginForm( &$template ) {
		if ( $template->haveData( 'createOrLoginHref' ) ) {
			$url = $template->data[ 'createOrLoginHref' ];
			$url .=  strpos( $url, '?' ) ? '&' : '?';
			$url .= 'campaign=loginCTA';
			$template->set( 'createOrLoginHref', $url );
		}

		return true;
	}
}
