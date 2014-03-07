<?php

namespace Campaigns\Api;

use ApiQueryBase;
use ApiBase;
use Campaigns\Setup\Setup;
use Campaigns\Persistence\ParticipationFields;

/**
 * Query module to fetch participants in a campaign.
 *
 * @ingroup API
 */
class ApiCampaignParticipants extends ApiQueryBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'campp' );
	}

	public function execute() {

		$params = $this->extractRequestParams();

		$setup = Setup::getInstance();
		$cRepo = $setup->get( 'Campaigns\Domain\ICampaignRepository' );
		$pRepo = $setup->get( 'Campaigns\Domain\IParticipationRepository' );

		// fetch the campaign
		if ( $params['id'] ) {
			$campaign = $cRepo->getCampaignById( $params['id'] );
		} elseif( $params['name'] ) {
			$campaign = $cRepo->getCampaignByName( $params['name'] );
		} else {
			$this->dieUsage( 'Either the id or the name parameter must be set.',
				'no_id_or_name' );
		}

		// we get null if there's no such campaign
		if ( is_null( $campaign ) ) {
			$this->dieUsage( 'No such campaign found.', 'no_such_campaign' );
		}

		// limit
		$limit = $params['limit'];
		$climit = $pRepo->getMaxFetchLimit();
		$limit = $limit > $climit ? $climit : $limit;

		// continue
		if ( !is_null( $params['continue'] ) ) {
			$cont = explode( '|', $params['continue'] );
			$this->dieContinueUsageIf( count( $cont ) != 1 );
			$continue = $cont[0];
		} else {
			$continue = null;
		}

		// fetch the participations
		// this will modify $continue as needed
		// for now just include organizers
		$participations = $pRepo->getParticipations( $campaign,
			true, $limit, $continue );

		// if there are more campaigns, set continue result property
		if ( !is_null( $continue ) ) {
			$this->setContinueEnumParameter( 'continue', $continue );
		}

		// fill up the results
		$r = $this->getResult();
		$pathForParticipants = array( 'query', $this->getModuleName() );
		foreach ( $participations as $p ) {

			$r->addValue(
				$pathForParticipants,
				null,
				array(
					'id' => $p->getUserId(),
				)
			);
		}

		// set the tag name of the elements in the list
		$r->setIndexedTagName_internal( $pathForParticipants, 'p' );
	}

	public function getAllowedParams() {
		return array(
			'id' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			),
			'name' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'limit' => array(
				ApiBase::PARAM_TYPE => 'limit', // see ApiBase
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1, // max for users
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2 // max for bots
			),
			'continue' => null
		);
	}

	public function getParamDescription() {
		return array(
			'id' => 'The ID of the campaign to get a list of participants for;' .
				'either this parameter or camppname must be provided.',
			'name' => 'The name of the campaign to get a list of participants' .
				'for; either this parameter or camppid must be provided.',
			'limit' => 'How many participants to return'
		);
	}

	public function getResultProperties() {
		return array(
			'' => array(
				'id' => 'integer'
			)
		);
	}

	public function getDescription() {
		return 'Enumerate the current participants in a campaign.';
	}

	public function getPossibleErrors() {
		return array_merge(
			parent::getPossibleErrors(),
			array(
				array(
					'code' => 'no_such_campaign',
					'info' => 'No such campaign found.'
				),
				array(
					'code' => 'no_id_or_name',
					'info' => 'Either the id or the name parameter must be set.'
				)
			)
		);
	}

	public function getExamples() {
		return 'api.php?action=query&list=campaignparticipants&camppid=1&campplimit=100';
	}
}