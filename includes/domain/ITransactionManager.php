<?php

namespace Campaigns\Domain;

/**
 * Helps manage domain persistence units of work.
 */
interface ITransactionManager {

	/**
	 * Perform persistence operations queued via
	 * ICampaignRepository::createCampaign(),
	 * ICampaignRepository::saveCampaign(),
	 * IParticipationRepository::setParticipant() or
	 * IParticipationRepository::removeParticipant().
	 *
	 * See those methods for further details.
	 *
	 * @throws CampaignUrlKeyNotUniqueException
	 * @throws CampaignValueNotUniqueException
	 * @throws Domain\Persistence\RequiredFieldNotSetException
	 */
	public function flush();
}
