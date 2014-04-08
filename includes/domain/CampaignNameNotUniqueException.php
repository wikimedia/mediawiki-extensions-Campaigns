<?php

namespace Campaigns\Domain;

/**
 * Thrown when you try to save a campaign with a name that's already in use.
 */
class CampaignNameNotUniqueException extends CampaignValueNotUniqueException {

	public function __construct( ICampaign $campaign ) {

		parent::__construct( $campaign, 'Campaign name ' . $campaign->getName()
			. ' not unique.' );
	}
}