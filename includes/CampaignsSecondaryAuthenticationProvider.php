<?php

namespace MediaWiki\Extension\Campaigns;

use MediaWiki\Auth\AbstractSecondaryAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\CentralAuth\SharedDomainUtils;
use MediaWiki\Extension\EventLogging\EventLogging;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Session\SessionManager;
use MediaWiki\User\TempUser\TempUserConfig;
use MobileContext;

/**
 * Log user creations to EventLogging, including the parameter "campaign" that
 * was set on account creation form link if one was present.
 */
class CampaignsSecondaryAuthenticationProvider
	extends AbstractSecondaryAuthenticationProvider {

	private TempUserConfig $tempUserConfig;

	/**
	 * @param TempUserConfig $tempUserConfig
	 */
	public function __construct( TempUserConfig $tempUserConfig ) {
		$this->tempUserConfig = $tempUserConfig;
	}

	/** @inheritDoc */
	public function getAuthenticationRequests( $action, array $options ) {
		if ( $action === AuthManager::ACTION_CREATE ) {
			$useCampaignField = !isset( $options['username'] ) ||
				( $this->tempUserConfig->isEnabled() && $this->tempUserConfig->isTempName( $options['username'] ) );

			return [ new CampaignsAuthenticationRequest(
				$this->manager->getRequest(),
				$useCampaignField
			) ];
		}

		return [];
	}

	/** @inheritDoc */
	public function beginSecondaryAuthentication( $user, array $reqs ) {
		return AuthenticationResponse::newAbstain();
	}

	/** @inheritDoc */
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

		$sul3Enabled = false;
		if ( ExtensionRegistry::getInstance()->isLoaded( 'CentralAuth' ) ) {
			// Check if we're in SUL3 mode or not and notify event schema.
			/** @var SharedDomainUtils $sharedDomainUtils */
			$sharedDomainUtils = MediaWikiServices::getInstance()
				->getService( 'CentralAuth.SharedDomainUtils' );
			$sul3Enabled = $sharedDomainUtils->isSul3Enabled( $request );
		}

		// Default of -1 for wikis which don't have hCaptcha loaded
		$hCaptchaScore = -1;
		if ( ExtensionRegistry::getInstance()->isLoaded( 'hCaptcha' ) ) {
			// get() may return null if no score was returned by the hCaptcha api, or otherwise not inserted
			// on the ConfirmEdit side.
			// Make sure we still default to using -1 as value outside range potentially returned by
			// hCaptcha (0.00-1.00), and something that would be acceptable to the event schema.
			$hCaptchaScore = SessionManager::getGlobalSession()->get( 'hCaptcha-score', -1 );
		}

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
			'sul3Enabled' => $sul3Enabled,
			'hCaptchaScore' => $hCaptchaScore,
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
