<?php

namespace Campaigns\Domain\Internal;

use MWException;
use Campaigns\Domain\IParticipation;

/**
 * Implementation of IParticipation.
 * @see IParticipation
 * @see Campaigns\Persistence\IPersistenceManager
 */
class Participation implements IParticipation {

	// These properties will be set via reflection by the persistence layer.
	// Setup is through annotations and, for DB persistence, the global
	// $wgCampaignsDBPersistence.
	// See Campaigns\Persistence\IPersistenceManager for an explanation of
	// the annotations.

	/**
	 * @var int
	 * @id
	 */
	private $id;

	/**
	 * @var int
	 * @required
	 * @unique user_campaign_index
	 */
	private $campaignId;

	/**
	 * @var int
	 * @required
	 * @unique user_campaign_index
	 */
	private $userId;

	/**
	 * @var MW_TS
	 * @required
	 */
	private $timeJoined;

	/**
	 * @var boolean
	 * @required
	 */
	private $organizer;

	/**
	 * @param int $campaignId
	 * @param int $userId
	 * @param boolean $organizer
	 */
	public function __construct( $campaignId, $userId, $organizer ) {

		// Set required fields.
		// Note: constructor will be bypassed by the persistence layer when
		// instantiating a non-new object.

		$this->campaignId = $campaignId;
		$this->userId = $userId;
		$this->organizer = $organizer;
		$this->timeJoined = wfTimestampNow();
	}

	/**
	 * @see IParticipation::getCampaignId()
	 */
	public function getCampaignId() {
		return $this->campaignId;
	}

	/**
	 * @see IParticipation::getUserId()
	 */
	public function getUserId() {
		return $this->userId;
	}

	/**
	 * @see IParticipation::getTimeJoined()
	 */
	public function getTimeJoined() {
		return $this->timeJoined;
	}

	/**
	 * @see IParticipation::isOrganizer()
	 */
	public function isOrganizer() {
		return $this->organizer;
	}
}