<?php

namespace Campaigns\Domain\Internal;

use MWException;
use BadFunctionCallException;
use Campaigns\Domain\IParticipationRepository;
use Campaigns\Domain\ICampaign;
use Campaigns\Persistence\IPersistenceManager;
use Campaigns\Persistence\Condition;
use Campaigns\Persistence\Operator;
use Campaigns\Persistence\Order;

/**
 * Implementation of IParticipationRepository.
 * @see IParticipationRepository
 * @see Campaigns\Persistence\IPersistenceManager
 */
class ParticipationRepository implements IParticipationRepository {

	/**
	 * @var IPersistenceManager
	 */
	private $pm;

	/**
	 * @var IParticipationFactory
	 */
	private $participationFactory;

	public function __construct( IPersistenceManager $pm,
		IParticipationFactory $participationFactory ) {

		$this->pm = $pm;
		$this->participationFactory = $participationFactory;
	}

	/**
	 * @see IParticipationRepository::setParticipant()
	 */
	public function setParticipant( ICampaign $campaign, $userId, $organizer ) {

		/*
		 * Possibilities are:
		 * 1) The user is already a participant and has the same organizer
		 *   status.
		 * 2) The user is already a participant and has the opposite status.
		 * 3) The user is not a participant.
		 *
		 * With the unique index on user id and campaign id, the following
		 * update-or-create should do nothing in case (1), change the status in
		 * case (2), and insert a row in case (3).
		 */

		// Get a new participation
		$newParticipation = $this->participationFactory->create(
			$campaign->getId(), $userId, $organizer );

		// Update or create. In addition to the object itself, we have to send
		// the fields to use for conditions in the case of update. We're sure
		// that together they'll identify a single participation, because
		// they form a unique index together.
		$this->pm->queueUpdateOrCreate( $newParticipation, array(
			ParticipationField::$USER_ID, ParticipationField::$CAMPAIGN_ID ) );
	}

	/**
	 * @see IParticipationRepository::removeParticipant()
	 */
	public function removeParticipant( ICampaign $campaign, $userId ) {

		$conditions = static::getCampaignAndUserConditions( $campaign, $userId );
		$this->pm->queueDelete( 'IParticipation', $conditions );
	}

	/**
	 * Get a user's participations across all campaigns. Not yet implemented.
	 * @see IParticipationRepository::getUserParticipations()
	 */
	public function getUserParticipations( $userId ) {
		throw new BadFunctionCallException(
			'getUserParticipations() not implemented.' );
	}

	/**
	 * Remove a user's participations across all campaigns. Not yet implemented.
	 * @see IParticipationRepository::removeParticipantFromAllCampaigns()
	 */
	public function removeParticipantFromAllCampaigns( $userId ) {
		throw new BadFunctionCallException(
			'removeParticipantFromAllCampaigns() not implemented.' );
	}

	/**
	 * @see IParticipationRepository::isOrganizer()
	 */
	public function isOrganizer( ICampaign $campaign, $userId ) {

		$conditions = static::getCampaignAndUserConditions( $campaign, $userId );
		$conditions[] = static::getOrganizerCondition( true );
		return $this->pm->existsWithConditions( 'IParticipation', $conditions );
	}

	/**
	 * @see IParticipationRepository::isParticipant()
	 */
	public function isParticipant( ICampaign $campaign, $userId ) {

		$conditions = static::getCampaignAndUserConditions( $campaign, $userId );
		return $this->pm->existsWithConditions( 'IParticipation', $conditions );
	}

	/**
	 * @see IParticipationRepository::countParticipants()
	 */
	public function countParticipants( ICampaign $campaign,
		$includeOrganizers ) {

		$conditions = static::getCampaignAndIncludeOrganizersConditions(
			$campaign, $includeOrganizers );

		return $this->pm->count( 'IParticipation', $conditions );
	}

	/**
	 * @see IParticipationRepository::getParticipations()
	 */
	public function getParticipations( ICampaign $campaign,
		$includeOrganizers, $fetchLimit=null, &$continueKey=null ) {

		$conditions = static::getCampaignAndIncludeOrganizersConditions(
			$campaign, $includeOrganizers );

		// Get the objects from the persistence manager.
		// The persistence manager will set up and use the continue key based
		// on the order-by field and the order (ascending or descending).
		return $this->pm->get( 'IParticipation', ParticipationField::$ID,
			Order::$ASCENDING, $conditions, $fetchLimit, $continueKey );
	}

	/**
	 * @see IParticipationRepository::getMaxFetchLimit()
	 */
	public function getMaxFetchLimit() {
		return $this->pm->getMaxFetchLimit( 'IParticipation' );
	}

	/**
	 * Create a Condition for selecting participations with this campaign.
	 *
	 * @param ICampaign $campaign
	 * @return Condition
	 */
	protected static function getCampaignCondition( ICampaign $campaign ) {

		return new Condition(
			ParticipationField::$CAMPAIGN_ID,
			Operator::$EQUAL,
			$campaign->getId()
		);
	}

	/**
	 * Create a Condition for selecting participations with this user ID.
	 *
	 * @param int $userId
	 * @return Condition
	 */
	protected static function getUserCondition( $userId ) {
		return new Condition(
			ParticipationField::$USER_ID,
			Operator::$EQUAL,
			$userId
		);
	}

	/**
	 * Create a Condition for selecting participations with this organizer
	 *   status.
	 *
	 * @param boolean $organizer
	 * @return Condition
	 */
	protected static function getOrganizerCondition( $organizer ) {
		return new Condition(
			ParticipationField::$ORGANIZER,
			Operator::$EQUAL,
			$organizer
		);
	}

	/**
	 * Create an array of conditions for selecting participations with this
	 *   campaign and user ID.
	 *
	 * @param ICampaign $campaign
	 * @return array
	 */
	protected static function getCampaignAndUserConditions( ICampaign $campaign,
		$userId ) {

		return array(
			static::getCampaignCondition( $campaign ),
			static::getUserCondition( $userId )
		);
	}

	/**
	 * Create an array of conditions for selecting participations with this
	 *   campaign and including or excluding organizers.
	 *
	 * @param ICampaign $campaign
	 * @param boolean $includeOrganizers
	 * @return array
	 */
	protected static function getCampaignAndIncludeOrganizersConditions(
		ICampaign $campaign, $includeOrganizers ) {

		$conditions = array( static::getCampaignCondition( $campaign ) );

		if ( $includeOrganizers !== true ) {
			$conditions[] = static::getOrganizerCondition( false );
		}

		return $conditions;
	}
}