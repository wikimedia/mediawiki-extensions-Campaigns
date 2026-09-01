<?php

namespace MediaWiki\Extension\Campaigns;

use MediaWiki\Auth\Hook\AuthPreserveQueryParamsHook;
use MediaWiki\Context\RequestContext;
use MediaWiki\SpecialPage\Hook\AuthChangeFormFieldsHook;

class Hooks implements
	AuthChangeFormFieldsHook,
	AuthPreserveQueryParamsHook
{
	/** @inheritDoc */
	public function onAuthChangeFormFields(
		$requests, $fieldInfo, &$formDescriptor, $action
	) {
		if ( isset( $formDescriptor['createOrLogin']['linkQuery'] ) ) {
			$linkQuery = $formDescriptor['createOrLogin']['linkQuery'] ?? '';
			// check for string campaign=* (do not match x-campaign=*) [T436681]
			$hasCampaign = strpos( $linkQuery, 'campaign=' ) === 0 ||
				strpos( $linkQuery, '&campaign=' ) !== false;
			if ( !$hasCampaign ) {
				$formDescriptor['createOrLogin']['linkQuery'] .=
					( $linkQuery !== '' ? '&' : '' ) . 'campaign=loginCTA';
			}
		}
	}

	/** @inheritDoc */
	public function onAuthPreserveQueryParams( array &$params, array $options ) {
		$request = RequestContext::getMain()->getRequest();
		$params['campaign'] = $request->getRawVal( 'campaign' );
	}
}
