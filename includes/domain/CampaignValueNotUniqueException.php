<?php

namespace Campaigns\Domain;

use MWException;
/**
 * Base class for exceptions that are thrown when you try to save a campaign
 * with a unique field that's already in use.
 */
abstract class CampaignValueNotUniqueException extends MWException {

	protected $campaign;

	/**
	 * @param ICampaign $campaign
	 * @param string $msg
	 */
	public function __construct( ICampaign $campaign, $msg ) {
		$this->campaign = $campaign;
		parent::__construct( $msg );
	}

	/**
	 * Get the campaign that was not saved due to the unique field collision.
	 * @return ICampaign
	 */
	public function getCampaign() {
		return $this->campaign;
	}
}