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
		if ( isset( $formDescriptor['createOrLogin'] ) ) {
			$formDescriptor['createOrLogin']['linkQuery'] .=
				( $formDescriptor['createOrLogin']['linkQuery'] ? '&' : '' ) . 'campaign=loginCTA';
		}
	}

	/** @inheritDoc */
	public function onAuthPreserveQueryParams( array &$params, array $options ) {
		$request = RequestContext::getMain()->getRequest();
		$params['campaign'] = $request->getRawVal( 'campaign' );
	}
}
