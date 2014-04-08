<?php

namespace Campaigns\Domain;

interface IParticipationRepository {

	/**
	 * Is the user an organizer of this campaign?
	 *
	 * @param ICampaign $campaign
	 * @param int $userId
	 * @return boolean
	 */
	public function isOrganizer( ICampaign $campaign, $userId );

	/**
	 * Is the user a participant in this campaign? (Note: organizers are also
	 *   participants in campaigns.)
	 *
	 * @param ICampaign $campaign
	 * @param int $userId
	 * @return boolean
	 */
	public function isParticipant( ICampaign $campaign, $userId );

	/**
	 * How many participants does this campaign have?
	 *
	 * @param ICampaign $campaign
	 * @param boolean $includeOrganizers Set to true to count all participants
	 *   including organizers, false to count only participants who are not
	 *   organizers
	 * @return int
	 */
	public function countParticipants( ICampaign $campaign,
		$includeOrganizers );

	/**
	 * Get a list of participations in a campaign.
	 *
	 * Return up to $fetchLimit participations. If $fetchLimit is greater than
	 * the value provided by getMaxFetchLimit(), an error will be thrown. If
	 * $fetchLimit is null, return as many as possible.
	 *
	 * If the campaign has more participations than are returned, $coninueKey
	 * will be set to a string key. To fetch the next block of participations,
	 * call this method again with that key. When the last block is returned,
	 * $continueKey will be set to null. To start with the first block of
	 * participations, call this method with $continueKey set to null, or just
	 * omit it.
	 *
	 * @param ICampaign $campaign The campaign whose participations to get
	 *
	 * @param boolean $includeOrganizers Include organizers' participations
	 *
	 * @param int $fetchLimit Maximum number of participations to return in a
	 *   single call; must be less than getMaxFetchLimit(), or null
	 *
	 * @param string $continueKey
	 */
	public function getParticipations( ICampaign $campaign,
		$includeOrganizers, $fetchLimit=null, &$continueKey=null );

	/**
	 * The maximum number of participations that may be retrieved at once using
	 * getParticipations(). If that method is called with a higher $fetchLimit,
	 * an exception will be thrown.
	 *
	 * @return int
	 */
	public function getMaxFetchLimit();

	/**
	 * Add a participant to a campaign, possibly as an organizer, or change
	 * a participant's organizer status.
	 *
	 * If the user already participates in this campaign and already has the
	 * status indicated by $organizer, do nothing.
	 *
	 * Changes set here are only persisted when ITransactionManager::flush()
	 * is called.
	 *
	 * @param ICampaign $campaign The campaign to set the user as a partcipant
	 *   in
	 *
	 * @param int $userId User ID of the participant to add
	 *
	 * @param boolean $organizer Set as true to set make the participant an
	 *   organizer of this campaign, false to make him or her not an organizer
	 */
	public function setParticipant( ICampaign $campaign, $userId, $organizer );

	/**
	 * Remove a user from a campaign. The change is only persisted when
	 * ITransactionManager::flush() is called.
	 *
	 * @param ICampaign $campaign
	 * @param int $userId
	 */
	public function removeParticipant( ICampaign $campaign, $userId );

	/**
	 * Get a user's participations across all campaigns. Note: the current
	 * realization of this interface,
	 * Campaigns\Internal\ParticipationRepository, does not yet implement this
	 * method.
	 *
	 * @param int $userId
	 */
	public function getUserParticipations( $userId );

	/**
	 * Remove a user's participations across all campaigns. Note: the current
	 * realization of this interface,
	 * Campaigns\Internal\ParticipationRepository, does not yet implement this
	 * method.
	 *
	 * @param int $userId
	 */
	public function removeParticipantFromAllCampaigns( $userId );
}