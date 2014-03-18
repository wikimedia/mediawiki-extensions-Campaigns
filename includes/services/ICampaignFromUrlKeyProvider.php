<?php

namespace Campaigns\Services;

/**
 * Interface for a service object that gets or creates a campaign for a URL key.
 */
interface ICampaignFromUrlKeyProvider {

	/**
	 * Attempts to get a campaign with this URL key from ConnectionType::$SLAVE.
	 * If none is found, calls the rather hardcore
	 * ICampaignRepository::getOrCreateCampaignEnsureUrlKey(), which uses
	 * ConnectionType::$MASTER and creates a campaign if none is found, futzing
	 * around with possible names until it finds one that's unique.
	 *
	 * @param string $urlKey
	 */
	public function getOrCreateCampaign( $urlKey );
}