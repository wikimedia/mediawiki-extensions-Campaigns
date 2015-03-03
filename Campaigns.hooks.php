<?php

class CampaignsHooks {
	public static function onUserCreateForm( &$template ) {
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

	public static function onAddNewAccount( $user, $byEmail ) {
		global $wgRequest, $wgUser, $wgCookiePrefix;

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

		EventLogging::logEvent( 'ServerSideAccountCreation', 5487345, $event );
		return true;
	}

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
