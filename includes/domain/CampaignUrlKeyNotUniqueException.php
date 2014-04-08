<?php

namespace Campaigns\Domain;

/**
 * Thrown when you try to save a campaign with a URL key that's already in use.
 */
class CampaignUrlKeyNotUniqueException extends CampaignValueNotUniqueException {

	public function __construct( ICampaign $campaign ) {

		parent::__construct( $campaign, 'Campaign url key ' .
			$campaign->getUrlKey() . ' not unique.' );
	}
}