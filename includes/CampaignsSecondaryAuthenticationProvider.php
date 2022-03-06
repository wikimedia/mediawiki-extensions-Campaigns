<?php

namespace MediaWiki\Extension\Campaigns;

use ExtensionRegistry;
use MediaWiki\Auth\AbstractSecondaryAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\EventLogging\EventLogging;
use MobileContext;

/**
 * Log user creations to EventLogging, including the parameter "campaign" that
 * was set on account creation form link if one was present.
 */
class CampaignsSecondaryAuthenticationProvider
	extends AbstractSecondaryAuthenticationProvider {

	/**
	 * @param array $params
	 */
	public function __construct( $params = [] ) {
	}

	public function getAuthenticationRequests( $action, array $options ) {
		if ( $action === AuthManager::ACTION_CREATE ) {
			return [ new CampaignsAuthenticationRequest(
				$this->manager->getRequest(),
				!isset( $options['username'] )
			) ];
		}

		return [];
	}

	public function beginSecondaryAuthentication( $user, array $reqs ) {
		return AuthenticationResponse::newAbstain();
	}

	public function beginSecondaryAccountCreation( $user, $creator, array $reqs ) {
		$req = AuthenticationRequest::getRequestByClass(
			$reqs, CampaignsAuthenticationRequest::class
		);

		$request = $this->manager->getRequest();
		$userId = $user->getId();
		$creatorUserId = $creator->getId();

		// MediaWiki allows existing users to create accounts on behalf
		// of others. In such cases the ID of the newly-created user and
		// the ID of the user making this web request are different.
		$isSelfMade = ( $userId && $userId === $creatorUserId ) || !$creatorUserId;

		$displayMobile = ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) &&
			MobileContext::singleton()->shouldDisplayMobileView();

		$event = [
			'userId' => $userId,
			'userName' => $user->getName(),
			'isSelfMade' => $isSelfMade,
			'campaign' => $req ? $req->campaign : '',
			'displayMobile' => $displayMobile,
			// @todo: Remove these unused fields when they're no longer required by the schema.
			'token' => '',
			'userBuckets' => '',
			'isApi' => defined( 'MW_API' ),
		];

		$returnTo = $request->getVal( 'returnto', $req ? $req->returnTo : null );
		if ( $returnTo !== null ) {
			$event[ 'returnTo' ] = $returnTo;
		}

		$returnToQuery = $request->getVal( 'returntoquery', $req ? $req->returnToQuery : null );
		if ( $returnToQuery !== null ) {
			$event[ 'returnToQuery' ] = $returnToQuery;
		}

		// This has been migrated to an Event Platform schema; schema revision is no longer used
		// in this call. Versioned schema URI is set in extension.json.
		EventLogging::logEvent( 'ServerSideAccountCreation', -1, $event );

		return AuthenticationResponse::newPass();
	}
}
