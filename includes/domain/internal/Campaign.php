<?php

namespace Campaigns\Domain\Internal;

use Campaigns\Domain\ICampaign;

/**
 * Implementation of ICampaign.
 * @see ICampaign
 * @see Campaigns\Persistence\IPersistenceManager
 */
class Campaign implements ICampaign {

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
	 * @var MW_TS
	 * @required
	 */
	private $timeCreated;

	/**
	 * @var MW_TS
	 */
	private $timeEnded;

	/**
	 * @var string
	 * @unique
	 * @required
	 */
	private $urlKey;

	/**
	 * @var string
	 * @unique
	 * @required
	 */
	private $name;

	/**
	 * @var string
	 */
	private $homePageTitleText;

	/**
	 * @param string $urlKey
	 * @param string $name
	 */
	public function __construct( $urlKey, $name ) {

		// Set required fields.
		// Note: constructor will be bypassed by the persistence layer when
		// instantiating a non-new object.

		$this->urlKey = $urlKey;
		$this->name = $name;
		$this->timeCreated = wfTimestampNow();
	}

	/**
	 * @see ICampaign::getId()
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @see ICampaign::getTimeCreated()
	 */
	public function getTimeCreated() {
		return $this->timeCreated;
	}

	/**
	 * @see ICampaign::getTimeEnded()
	 */
	public function getTimeEnded() {
		return $this->timeEnded;
	}

	/**
	 * @see ICampaign::getUrlKey()
	 */
	public function getUrlKey() {
		return $this->urlKey;
	}

	/**
	 * @see ICampaign::setUrlKey()
	 */
	public function setUrlKey( $urlKey ) {
		$this->urlKey = $urlKey;
	}

	/**
	 * @see ICampaign::getName()
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @see ICampaign::setName()
	 */
	public function setName( $name ) {
		$this->name = $name;
	}

	/**
	 * @see ICampaign::getHomePageTitleText()
	 */
	public function getHomePageTitleText() {
		return $this->homePageTitleText;
	}

	/**
	 * @see ICampaign::setHomePageTitleText()
	 */
	public function setHomePageTitleText( $homePageTitleText ) {
		$this->homePageTitleText = $homePageTitleText;
	}

	/**
	 * @see ICampaign::isActive()
	 */
	public function isActive() {

		// A campaign is active when the time left ended is null.
		// Note: this rule is also used in CampaignRepository.
		return ( is_null( $this->timeEnded ) );
	}
}