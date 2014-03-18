<?php

namespace Campaigns\Services;

use MWException;
use Campaigns\Domain\ICampaignRepository;

/**
 * @see ICampaignFromUrlKeyProvider
 */
class CampaignFromUrlKeyProvider implements ICampaignFromUrlKeyProvider {

	const UNIQUE_NAME_ATTEMPTS_LIMIT = 5;

	protected $cRepo;

	public function __construct( ICampaignRepository $cRepo ) {

		$this->cRepo = $cRepo;
	}

	/**
	 * @see ICampaignFromUrlKeyProvider::getOrCreateCampaign()
	 */
	public function getOrCreateCampaign( $urlKey ) {

		// Try to get an existing campaign for this URL key
		$campaign = $this->cRepo->getCampaignByUrlKey( $urlKey );

		// Null means none was found
		if ( is_null( $campaign ) ) {

			// This will either get the campaign from MASTER or create a new
			// one. As the suggested name, we send the same URL key.
			$campaign = $this->cRepo->getOrCreateCampaignEnsureUrlKey(
				$urlKey, $urlKey, self::UNIQUE_NAME_ATTEMPTS_LIMIT );
		}

		return $campaign;
	}
}