<?php

namespace Campaigns\Domain\Internal;

/**
 * @see IParticipationFactory
 */
class ParticipationFactory implements IParticipationFactory {

	/**
	 * @see IParticipationFactory::create()
	 */
	public function create( $campaignID, $userID, $organizer ) {
		return new Participation( $campaignID, $userID, $organizer );
	}
}