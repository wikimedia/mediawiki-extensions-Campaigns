<?php

namespace Campaigns\Services;

use MWException;
use Campaigns\ConnectionType;
use Campaigns\Domain\ICampaignRepository;
use Campaigns\Domain\IParticipationRepository;
use Campaigns\Domain\ITransactionManager;

/**
 * @see IParticipantSetter
 */
class ParticipantSetter implements IParticipantSetter {

	protected $cRepo;
	protected $pRepo;
	protected $tm;

	public function __construct( ICampaignRepository $cRepo,
		IParticipationRepository $pRepo, ITransactionManager $tm) {

		$this->cRepo = $cRepo;
		$this->pRepo = $pRepo;
		$this->tm = $tm;
	}

	/**
	 * @see IParticipantSetter::setParticipant()
	 */
	public function setParticipant( $urlKey, $userId, $organizer ) {

		$campaign = $this->cRepo->getCampaignByUrlKey( $urlKey );

		// Maybe the campaign was just created and we need to get it from MASTER
		if ( is_null( $campaign ) ) {
			$campaign = $this->cRepo->getCampaignByUrlKey( $urlKey,
				ConnectionType::$MASTER );
		}

		// This shouldn't happen
		if ( is_null( $campaign ) ) {
			throw new MWException( 'Couldn\'t find a campaign for URL key ' .
				$urlKey . ',' );
		}

		$this->pRepo->setParticipant( $campaign, $userId, $organizer );
			$this->tm->flush();
	}
}