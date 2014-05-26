<?php

namespace Campaigns\PHPUnit\Domain\Internal;

use MediaWikiTestCase;
use Campaigns\Domain\Internal\Campaign;

/**
 * @group Campaigns
 */
class CampaignTest extends MediaWikiTestCase {

	public function testConstructorSetsTimeCreatedToNow() {

		// Check the time, create a campaign, check the time again
		$before = wfTimestampNow();
		$c = new Campaign( 'urlKey', 'name' );
		$after = wfTimestampNow();

		// Test that campaign's time created was neither before $before nor
		// after $after
		$timeCreated = $c->getTimeCreated();
		$this->assertGreaterThanOrEqual( $before, $timeCreated );
		$this->assertGreaterThanOrEqual( $timeCreated, $after );
	}

	public function testIdIsInitallyNull() {

		$c = new Campaign( 'urlKey', 'name' );
		$this->assertNull( $c->getId() );
	}

	public function testCampaignIsInitiallyActive() {

		$c = new Campaign( 'urlKey', 'name' );
		$this->assertTrue( $c->isActive() );
		$this->assertNull( $c->getTimeEnded() );
	}
}