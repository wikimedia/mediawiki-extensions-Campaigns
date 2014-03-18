<?php

namespace Campaigns\Services;

/**
 * Interface for a service object that sets a participant on a campaign.
 */
interface IParticipantSetter {

	/**
	 * Sets the user $userId as a participant on the campaign identified by
	 * $urlKey.
	 *
	 * Before using this service, consumers should be sure that a campaign
	 * exists with this $urlKey.
	 *
	 * @param string $urlKey
	 * @param int $userId
	 * @param boolean $organizer
	 */
	public function setParticipant( $urlKey, $userId, $organizer );
}