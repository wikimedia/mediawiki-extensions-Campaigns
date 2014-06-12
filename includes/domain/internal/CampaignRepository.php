<?php

namespace Campaigns\Domain\Internal;

use MWException;
use Campaigns\Domain\ICampaign;
use Campaigns\Domain\ICampaignRepository;
use Campaigns\Domain\CampaignNameNotUniqueException;
use Campaigns\Domain\CampaignUrlKeyNotUniqueException;
use Campaigns\Persistence\IPersistenceManager;
use Campaigns\Persistence\Condition;
use Campaigns\Persistence\Operator;
use Campaigns\Persistence\Order;

/**
 * Implementation of ICampaignRepository.
 * @see ICampaignRepository
 * @see Campaigns\Persistence\IPersistenceManager
 */
class CampaignRepository implements ICampaignRepository {

	/**
	 * @var IPersistenceManager
	 */
	private $pm;

	/**
	 * @var ICampaignFactory
	 */
	private $campaignFactory;

	public function __construct( IPersistenceManager $pm,
		ICampaignFactory $campaignFactory ) {

		$this->pm = $pm;
		$this->campaignFactory = $campaignFactory;
	}

	/**
	 * @see ICampaignRepository::createCampaign()
	 */
	public function createCampaign( $urlKey, $name ) {
		$campaign = $this->campaignFactory->create( $urlKey, $name );
		$this->saveCampaign( $campaign );
		return $campaign;
	}

	/**
	 * @see ICampaignRepository::saveCampaign()
	 */
	public function saveCampaign( ICampaign $campaign ) {

		// If there were problems due to duplicate values, throw the appropriate
		// exception.
		$duplicatesCallback =
			function ( $c, $uniqueIndexName ) use ( $campaign ) {

			if ( $uniqueIndexName === CampaignField::$URL_KEY->getName() ) {
				throw new CampaignUrlKeyNotUniqueException( $campaign );
			}

			if ( $uniqueIndexName === CampaignField::$NAME->getName() ) {
				throw new CampaignNameNotUniqueException( $campaign );
			}

			// There shouldn't be any other duplicate values errors, so
			// if we got here, something went wrong.
			throw new MWException( 'Problem handling duplicate values ' .
				'on unique index ' . $uniqueIndexName . '.' );
		};

		$this->pm->queueSave( $campaign, $duplicatesCallback );
	}

	/**
	 * @see ICampaignRepository::countCampaigns()
	 */
	public function countCampaigns() {
		return $this->pm->count( 'ICampaign' );
	}

	/**
	 * @see ICampaignRepository::existsCampaignWithUrlKey()
	 */
	public function existsCampaignWithUrlKey( $urlKey ) {

		$condition = new Condition(
			CampaignField::$URL_KEY,
			Operator::$EQUAL,
			$urlKey
		);

		return $this->pm->existsWithConditions( 'ICampaign', $condition );
	}

	/**
	 * @see ICampaignRepository::existsCampaignWithName()
	 */
	public function existsCampaignWithName( $name ) {

		$condition = new Condition(
			CampaignField::$NAME,
			Operator::$EQUAL,
			$name
		);

		return $this->pm->existsWithConditions( 'ICampaign', $condition );
	}

	/**
	 * @see ICampaignRepository::getCampaignById()
	 */
	public function getCampaignById( $id, $useMaster=false ) {
		return $this->pm->getOneById( 'ICampaign', $id, $useMaster );
	}

	/**
	 * @see ICampaignRepository::getCampaignByUrlKey()
	 */
	public function getCampaignByUrlKey( $urlKey, $useMaster=false ) {

		$condition = new Condition(
			CampaignField::$URL_KEY,
			Operator::$EQUAL,
			$urlKey
		);

		return $this->pm->getOne( 'ICampaign', $condition, $useMaster );
	}

	/**
	 * @see ICampaignRepository::getCampaignByName()
	 */
	public function getCampaignByName( $name, $useMaster=false ) {

		$condition = new Condition(
			CampaignField::$NAME,
			Operator::$EQUAL,
			$name
		);

		return $this->pm->getOne( 'ICampaign', $condition, $useMaster );
	}

	/**
	 * @see ICampaignRepository::getCampaigns()
	 */
	public function getCampaigns( $namePrefix=null, $fetchLimit=null, &$continueKey=null ) {

		// Set up prefix condition as needed
		if ( is_null( $namePrefix ) ) {
			$condition = null;

		} else {

			$condition = new Condition(
				CampaignField::$NAME,
				Operator::$LIKE,
				array( $namePrefix, $this->pm->getAnyStringForLikeOperator() )
			);
		}

		// Get the objects from the persistence manager.
		// The persistence manager will set up and use the continue key based
		// on the order-by field and the order (ascending or descending).
		return $this->pm->get( 'ICampaign', CampaignField::$NAME,
			Order::$ASCENDING, $condition, $fetchLimit, $continueKey );
	}

	/**
	 * @see ICampaignRepository::getMaxFetchLimit()
	 */
	public function getMaxFetchLimit() {
		return $this->pm->getMaxFetchLimit( 'ICampaign' );
	}
}