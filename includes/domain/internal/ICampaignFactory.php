<?php

namespace Campaigns\Domain\Internal;

/**
 * A factory for Campaigns\Domain\ICampaign objects. Used internally by
 * CampaignRepostiory.
 */
interface ICampaignFactory {

	/**
	 * Create a Campaigns\Domain\ICampaign object.
	 *
	 * @param string $urlKey
	 * @param string $name
	 * @return Campaigns\Domain\ICampaign
	 */
	public function create( $urlKey, $name );
}