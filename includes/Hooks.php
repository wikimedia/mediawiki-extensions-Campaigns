<?php

namespace MediaWiki\Extension\Campaigns;

use MediaWiki\SpecialPage\Hook\AuthChangeFormFieldsHook;

class Hooks implements AuthChangeFormFieldsHook {
	public function onAuthChangeFormFields(
		$requests, $fieldInfo, &$formDescriptor, $action
	) {
		if ( isset( $formDescriptor['createOrLogin'] ) ) {
			$formDescriptor['createOrLogin']['linkQuery'] .=
				( $formDescriptor['createOrLogin']['linkQuery'] ? '&' : '' ) . 'campaign=loginCTA';
		}
	}
}
