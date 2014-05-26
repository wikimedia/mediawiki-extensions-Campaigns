<?php

namespace Campaigns\PHPUnit\Domain\Internal;

use MediaWikiTestCase;
use ReflectionClass;
use Campaigns\Domain\Internal\Participation;

/**
 * @group Campaigns
 */
class ParticipationTest extends MediaWikiTestCase {

	public function testConstructorSetsTimeJoinedToNow() {

		// Check the time, create a participation, check the time again
		$before = wfTimestampNow();
		$p = new Participation( 1, 1, true );
		$after = wfTimestampNow();

		// Test that participation's time joined was neither before $before nor
		// after $after
		$timeJoined = $p->getTimeJoined();
		$this->assertGreaterThanOrEqual( $before, $timeJoined );
		$this->assertGreaterThanOrEqual( $timeJoined, $after );
	}

	public function testIdIsInitallyNull() {

		$p = new Participation( 1, 1, true );

		// Use reflection to get at the ID value.
		// This may seem odd for a test. However, note that the data mapper will
		// also use reflection to interact with the id property, so even in the
		// absence of a public handle for ID, the protected property is part of
		// this class's interface with the data mapper.
		$pClass =
			new ReflectionClass( "Campaigns\Domain\Internal\Participation" );

		$idProp = $pClass->getProperty( 'id' );
		$idProp->setAccessible( true );
		$id = $idProp->getValue( $p );

		$this->assertNull( $id );
	}
}