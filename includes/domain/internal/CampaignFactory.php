<?php

namespace Campaigns\Domain\Internal;

/**
 * @see ICampaignFactory
 */
class CampaignFactory implements ICampaignFactory {

	/**
	 * @see ICampaignFactory::create()
	 */
	public function create( $urlKey, $name ) {
		return new Campaign( $urlKey, $name );
	}
}