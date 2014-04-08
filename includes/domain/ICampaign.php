<?php

namespace Campaigns\Domain;

use \MWException;

/**
 * Interface for an object that represents a campaign.
 */
interface ICampaign {

	/**
	 * Get the ID of this campaign. (Will be null if this is a newly created
	 * campaign that has not yet been persisted.)
	 *
	 * @return int
	 */
	public function getId();

	/**
	 * @return string TS_MW
	 */
	public function getTimeCreated();

	/**
	 * @return string TS_MW
	 */
	public function getTimeEnded();

	/**
	 * Get the string used to identify this campaign in URLs.
	 *
	 * @return string
	 */
	public function getUrlKey();

	/**
	 * Set the string used to identify this campaign in URLs.
	 *
	 * @param $urlKey
	 */
	public function setUrlKey( $urlKey );

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @param string $name
	 */
	public function setName( $name );

	/**
	 * Get the string title of the campaign's home page on this wiki.
	 *
	 * @return string
	 */
	public function getHomePageTitleText();

	/**
	 * Set the string title of the campaign's home page on this wiki.
	 *
	 * @param string $homePageTitleText
	 */
	public function setHomePageTitleText( $homePageTitleText );

	/**
	 * Is this campaign active?
	 *
	 * @return boolean
	 */
	public function isActive();
}