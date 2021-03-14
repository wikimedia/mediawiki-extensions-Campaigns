<?php

namespace MediaWiki\Extension\Campaigns;

use MediaWiki\Auth\AuthenticationRequest;
use WebRequest;

/**
 * An authentication request to grab the custom field passed to the creation form.
 */
class CampaignsAuthenticationRequest extends AuthenticationRequest {
	public $required = self::OPTIONAL;

	/** @var bool Whether to use the campaign field from the request */
	private $useCampaignField;

	/** @var string Campaign to log */
	public $campaign = '';

	/** @var string|null returnto title to log */
	public $returnTo;

	/** @var string|null returntoquery to log */
	public $returnToQuery;

	/**
	 * @param WebRequest $request Request to load data from
	 * @param bool $useCampaignField Whether to actually use the 'campaign' field
	 */
	public function __construct( WebRequest $request, $useCampaignField ) {
		$this->useCampaignField = $useCampaignField;
		if ( $useCampaignField ) {
			$this->campaign = $request->getVal( 'campaign', '' );
			if ( strlen( $this->campaign ) > 40 ) {
				$this->campaign = '';
			}
		}

		$this->returnTo = $request->getVal( 'returnto' );
		$this->returnToQuery = $request->getVal( 'returntoquery' );
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldInfo() {
		if ( $this->useCampaignField ) {
			return [
				'campaign' => [
					'type' => 'hidden',
					'value' => $this->campaign,
					'label' => wfMessage( 'campaigns-campaign-label' ),
					'help' => wfMessage( 'campaigns-campaign-help' ),
					'optional' => true,
				],
			];
		}

		return [];
	}

	public function loadFromSubmission( array $data ) {
		// We always want to use this request, even if the 'campaign' field got
		// lost or is invalid, so ignore parent's return value.
		parent::loadFromSubmission( $data );

		// Ignore invalid campaigns, but still use the request.
		if ( strlen( $this->campaign ) > 40 ) {
			$this->campaign = '';
		}

		return true;
	}
}
