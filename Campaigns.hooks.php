<?php

class CampaignsHooks {
	public static function onRegistration() {
		global $wgHooks, $wgDisableAuthManager, $wgAuthManagerAutoConfig;

		if ( class_exists( MediaWiki\Auth\AuthManager::class ) && empty( $wgDisableAuthManager ) ) {
			$wgAuthManagerAutoConfig['secondaryauth'] += [
				CampaignsSecondaryAuthenticationProvider::class => [
					'class' => CampaignsSecondaryAuthenticationProvider::class,
					'sort' => 0, // non-UI secondaries should run early
				]
			];
			$wgHooks['AuthChangeFormFields'][] = 'CampaignsHooks::onAuthChangeFormFields';
		} else {
			$wgHooks['UserCreateForm'][] = 'CampaignsHooks::onUserCreateForm';
			$wgHooks['AddNewAccount'][] = 'CampaignsHooks::onAddNewAccount';
		}
	}

	public static function onAuthChangeFormFields(
		array $requests, array $fieldInfo, array &$formDescriptor, $action
	) {
		if ( isset( $formDescriptor['createOrLogin'] ) ) {
			$formDescriptor['createOrLogin']['linkQuery'] .=
				( $formDescriptor['createOrLogin']['linkQuery'] ? '&' : '' ) . 'campaign=loginCTA';
		}
	}

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

		$template->set( 'header',
			Html::hidden( 'campaign', $campaign )
		);

		return true;
	}

	public static function onAddNewAccount( $user, $byEmail ) {
		global $wgRequest, $wgUser;

		$userId = $user->getId();
		$creatorUserId = $wgUser->getId();

		// MediaWiki allows existing users to create accounts on behalf
		// of others. In such cases the ID of the newly-created user and
		// the ID of the user making this web request are different.
		$isSelfMade = ( $userId && $userId === $creatorUserId );

		$displayMobile = class_exists( 'MobileContext' ) &&
			MobileContext::singleton()->shouldDisplayMobileView();

		$event = [
			'userId' => $userId,
			'userName' => $user->getName(),
			'isSelfMade' => $isSelfMade,
			'campaign' =>  $wgRequest->getVal( 'campaign', '' ),
			'displayMobile' => $displayMobile,
			// @todo: Remove these unused fields when they're no longer required by the schema.
			'token' => '',
			'userBuckets' => '',
		];

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
}
