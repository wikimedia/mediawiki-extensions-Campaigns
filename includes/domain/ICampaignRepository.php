<?php

namespace Campaigns\Domain;

interface ICampaignRepository {

	/**
	 * Create a new campaign with this URL key and name. A persistence operation
	 * to save the new campaign will be queued automatically, and will be
	 * performed when ITransactionManager::flush() is called. Some exceptions
	 * related to this operation will be thrown only at that time.
	 *
	 * Note: the new campaign cannot be used in other operations (for example,
	 * in IParticipationRepository) until ITransactionManager::flush() has
	 * been called.
	 *
	 * @param string $urlKey
	 * @param string $name
	 */
	public function createCampaign( $urlKey, $name );

	/**
	 * Persist a campaign. Note that this only takes effect after
	 * ITranscationManager::flush() has been called. Some exceptions related to
	 * this operation will be thrown only at that time.
	 *
	 * @param ICampaign $campaign
	 */
	public function saveCampaign( ICampaign $campaign );

	/**
	 * Return the number of campaigns in the repository.
	 *
	 * @return int
	 */
	public function countCampaigns();

	/**
	 * Is there a campaign with this URL key?
	 *
	 * @param string $urlKey
	 * @return boolean
	 */
	public function existsCampaignWithUrlKey( $urlKey );

	/**
	 * Is there a campaign with this name?
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function existsCampaignWithName( $name );

	/**
	 * Get a campaign by ID.
	 *
	 * @param int $id
	 * @param boolean $useMaster Set to true to query the master persistence
	 *   store, which should be up-to-date with all transactions. (In the DB
	 *   implementation, this forces the query to use DB_MASTER.)
	 */
	public function getCampaignById( $id, $useMaster=false );

	/**
	 * Get a campaign by URL key.
	 *
	 * @param string $urlKey
	 * @param boolean $useMaster Set to true to query the master persistence
	 *   store, which should be up-to-date with all transactions. (In the DB
	 *   implementation, this forces the query to use DB_MASTER.)
	 */
	public function getCampaignByUrlKey( $urlKey, $useMaster=false );

	/**
	 * Get a campaign by name.
	 *
	 * @param string $name
	 * @param boolean $useMaster Set to true to query the master persistence
	 *   store, which should be up-to-date with all transactions. (In the DB
	 *   implementation, this forces the query to use DB_MASTER.)
	 */
	public function getCampaignByName( $name, $useMaster=false );

	/**
	 * Get a list of campaigns. Include only campaigns whose name starts
	 * with $namePrefix, or all campaigns, if $namePrefix is omitted or is null.
	 *
	 * Return up to $fetchLimit campaigns. If $fetchLimit is greater than the
	 * value provided by getMaxFetchLimit(), an error will be thrown. If
	 * $fetchLimit is null, return as many as possible.
	 *
	 * If there are more campaigns than are returned, $continueKey will be set
	 * to a string key. To fetch the next block of campaigns, call this method
	 * again with that key. When the last block is returned, $continueKey will
	 * be set to null. To start with the first block of campaigns, call this
	 * method with $continueKey set to null, or just omit it.
	 *
	 * Note that $prefix is not normalized for wiki page title constraints.
	 *
	 * @param string $namePrefix Include only campaigns whose name starts with
	 *   this string; set to null or omit to get all campaigns.
	 *
	 * @param int $fetchLimit Maximum number of campaigns to return in a single
	 *   call; must be less than getMaxFetchLimit(), or null
	 *
	 * @param string $continueKey Key for fetching additional blocks of
	 *   campaigns, null to start with the first block
	 *
	 * @return array An array of ICampaigns.
	 */
	public function getCampaigns( $namePrefix=null, $fetchLimit=null,
		&$continueKey=null );

	/**
	 * The maximum number of campaigns that may be retrieved at once using
	 * getCampaigns(). If that method is called with a higher $fetchLimit,
	 * an exception will be thrown.
	 *
	 * @return int
	 */
	public function getMaxFetchLimit();
}