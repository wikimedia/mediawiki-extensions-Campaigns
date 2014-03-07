<?php

namespace Campaigns\Api;

use ApiQueryBase;
use ApiBase;
use Campaigns\Setup\Setup;
use Campaigns\Persistence\CampaignFields;

/**
 * Query module to enumerate all campagins.
 *
 * @ingroup API
 */
class ApiAllCampaigns extends ApiQueryBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'allc' );
	}

	public function execute() {

		$params = $this->extractRequestParams();

		$cRepo = Setup::getInstance()->get(
			'Campaigns\Domain\ICampaignRepository' );

		// prefix
		$prefix = $params['prefix'];
		$prefix = $prefix ? $prefix : null;

		// limit
		// superclass will ensure there's at least something in this parameter
		$limit = $params['limit'];
		$repoLimit = $cRepo->getMaxFetchLimit();
		$limit = $limit > $repoLimit ? $repoLimit : $limit;

		// continue
		if ( !is_null( $params['continue'] ) ) {
			$cont = explode( '|', $params['continue'] );
			$this->dieContinueUsageIf( count( $cont ) != 1 );
			$continue = $cont[0];
		} else {
			$continue = null;
		}

		// fetch the campaigns
		// this will modify $continue as needed
		$campaigns = $cRepo->getCampaigns( $prefix, $limit, $continue );

		// if there are more campaigns, set continue result property
		if ( !is_null( $continue ) ) {
			$this->setContinueEnumParameter( 'continue', $continue );
		}

		// fill up the results
		$r = $this->getResult();
		$pathForCampaigns = array( 'query', $this->getModuleName() );
		foreach ( $campaigns as $c ) {

			$r->addValue(
				$pathForCampaigns,
				null,
				array(
					'id' => $c->getId(),
					'name' => $c->getName(),
					'timecreated' => $c->getTimeCreated()
				)
			);
		}

		// set the tag name of the elements in the list
		$r->setIndexedTagName_internal( $pathForCampaigns, 'c' );
	}

	public function getAllowedParams() {
		return array(
			'prefix' => array(
				ApiBase::PARAM_TYPE => 'string'
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
			'prefix' => 'Return only campaigns whose name begins with this value',
			'limit' => 'How many campaigns to return'
		);
	}

	public function getResultProperties() {
		return array(
			'' => array(
				'id' => 'integer',
				'name' => 'string',
				'timecreated' => 'timestamp'
			)
		);
	}

	public function getDescription() {
		return 'Enumerate all campaigns.';
	}

	public function getExamples() {
		return 'api.php?action=query&list=allcampaigns&allclimit=10';
	}
}