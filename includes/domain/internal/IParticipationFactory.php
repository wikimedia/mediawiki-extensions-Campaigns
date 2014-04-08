<?php

namespace Campaigns\Domain\Internal;

/**
 * A factory for Campaigns\Domain\IParticipation objects. Used internally by
 * ParticipationRepostiory.
 */
interface IParticipationFactory {

	/**
	 * Create a Campaigns\Domain\IParticipation object.
	 *
	 * @param int $campaignID
	 * @param int $userID
	 * @param boolean $organizer
	 * @return Campaigns\Domain\IParticipation
	 */
	public function create( $campaignID, $userID, $organizer );
}