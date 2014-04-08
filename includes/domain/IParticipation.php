<?php

namespace Campaigns\Domain;

interface IParticipation {

	/**
	 * Get the ID of the campaign that this is a participation in.
	 *
	 * @return int
	 */
	public function getCampaignId();

	/**
	 * Get the user ID of the user who participates in the campaign.
	 *
	 * @return int
	 */
	public function getUserId();

	/**
	 * Get the time the user joined the campaign.
	 *
	 * @return string TS_MW
	 */
	public function getTimeJoined();

	/**
	 * Is the user an organizer of this campaign?
	 *
	 * @return boolean
	 */
	public function isOrganizer();
}