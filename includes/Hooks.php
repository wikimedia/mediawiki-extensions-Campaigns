<?php

namespace MediaWiki\Extension\Campaigns;

class Hooks {
	public static function onAuthChangeFormFields(
		array $requests, array $fieldInfo, array &$formDescriptor, $action
	) {
		if ( isset( $formDescriptor['createOrLogin'] ) ) {
			$formDescriptor['createOrLogin']['linkQuery'] .=
				( $formDescriptor['createOrLogin']['linkQuery'] ? '&' : '' ) . 'campaign=loginCTA';
		}
	}
}
