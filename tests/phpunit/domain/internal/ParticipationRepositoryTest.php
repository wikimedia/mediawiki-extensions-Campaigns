<?php

namespace Campaigns\PHPUnit\Domain\Internal;

use MediaWikiTestCase;
use Campaigns\PHPUnit\TestHelper;
use Campaigns\Domain\IParticipation;
use Campaigns\Domain\Internal\ParticipationRepository;
use Campaigns\Domain\Internal\IParticipationFactory;
use Campaigns\Domain\Internal\ParticipationField;
use Campaigns\Persistence\IPersistenceManager;
use Campaigns\Persistence\IField;
use Campaigns\Persistence\Operator;
use Campaigns\Persistence\Order;

class ParticipationRepositoryTest extends MediaWikiTestCase {

	protected $testHelper;
	protected $testUserId = 1;
	protected $testCampaignId = 2;

	protected function setUp() {
		parent::setUp();
		$this->testHelper = new TestHelper( $this );
	}

	public function testSetParticipant() {

		$repoAndM = $this->getParticipationRepoAndMocks();

		// Verifications and return value for mock participation factory
		$repoAndM->pFactory->expects( $this->once() )
			->method( 'create' )
			->with( $this->equalTo( $this->testCampaignId ),
				$this->equalTo( $this->testUserId ),
				$this->equalTo( true ) )
			->will( $this->returnValue( $repoAndM->p ) );

		// Verifications and argument capture for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'queueUpdateOrCreate' )
			->with( $this->identicalTo( $repoAndM->p ),
				$this->identicalTo( array(
				ParticipationField::$USER_ID,
				ParticipationField::$CAMPAIGN_ID ) ) );

		// Call the method
		$repoAndM->pRepo->setParticipant( $repoAndM->c, $this->testUserId,
			true );
	}

	public function testRemoveParticipant() {

		$repoAndM = $this->getParticipationRepoAndMocks();

		// Verifications and argument capture for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'queueDelete' )
			->with(
				$this->equalTo( 'IParticipation' ),
				$this->captureArg( $conditions ) );

		// Call the method
		$repoAndM->pRepo->removeParticipant( $repoAndM->c, $this->testUserId );

		// Test the conditions passed to the persistence manager
		$this->assertCampaignCondition( $conditions[0] );
		$this->assertUserCondition( $conditions[1] );
		$this->assertEquals( 2, count( $conditions ) );
	}

	/**
	 * @expectedException BadFunctionCallException
	 * @expectedExceptionMessage getUserParticipations() not implemented
	 */
	public function testExceptionThownWhenGetUserParticipationsCalled() {
		$this->getParticipationRepoAndMocks()->pRepo->getUserParticipations(
			$this->testUserId );
	}

	/**
	 * @expectedException BadFunctionCallException
	 * @expectedExceptionMessage removeParticipantFromAllCampaigns() not implemented
	 */
	public function
		testExceptionThownWhenRemoveParticipantFromAllCampaignsCalled() {

		$this->getParticipationRepoAndMocks()->pRepo
			->removeParticipantFromAllCampaigns( $this->testUserId );
	}

	public function testIsOrganizer() {

		$repoAndM = $this->getParticipationRepoAndMocks();

		// Verifications, argument capture and return value for mock
		// persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'existsWithConditions' )
			->with( $this->equalTo( 'IParticipation' ),
				$this->captureArg( $conditions ) )
			->will( $this->returnValue( true ) );

		// Perform the call and test the return value
		$this->assertEquals( true,
			$repoAndM->pRepo->isOrganizer( $repoAndM->c, $this->testUserId ) );

		// Test the conditions passed to the persistence manager
		$this->assertCampaignCondition( $conditions[0] );
		$this->assertUserCondition( $conditions[1] );
		$this->assertOrganizerCondition( $conditions[2], true );
		$this->assertEquals( 3, count( $conditions ) );
	}

	public function testIsParticipant() {

		$repoAndM = $this->getParticipationRepoAndMocks();

		// Verifications, argument capture and return value for mock
		// persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'existsWithConditions' )
			->with( $this->equalTo( 'IParticipation' ),
				$this->captureArg( $conditions ) )
			->will( $this->returnValue( true ) );

		// Perform the call and test the return value
		$this->assertEquals( true, $repoAndM->pRepo->isParticipant(
			$repoAndM->c, $this->testUserId ) );

		// Test the conditions passed to the persistence manager
		$this->assertCampaignCondition( $conditions[0] );
		$this->assertUserCondition( $conditions[1] );

		$this->assertEquals( 2, count( $conditions ) );
	}

	public function testCountParticipantsIncludingOrganizers() {

		$repoAndM = $this->getParticipationRepoAndMocks();

		// Verifications, argument capture and return value for mock
		// persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'count' )
			->with( $this->equalTo( 'IParticipation' ),
				$this->captureArg( $conditions ) )
			->will( $this->returnValue( 5 ) );

		// Perform the call and test the return value
		$this->assertEquals( 5,
			$repoAndM->pRepo->countParticipants( $repoAndM->c, true ) );

		// Test the conditions passed to the persistence manager
		$this->assertCampaignCondition( $conditions[0] );
		$this->assertEquals( 1, count( $conditions ) );
	}

	public function testCountParticipantsExcludingOrganizers() {

		$repoAndM = $this->getParticipationRepoAndMocks();

		// Verifications, argument capture and return value for mock
		// persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'count' )
			->with( $this->equalTo( 'IParticipation' ),
				$this->captureArg( $conditions ) )
			->will( $this->returnValue( 5 ) );

		// Perform the call and test the return value
		$this->assertEquals( 5,
			$repoAndM->pRepo->countParticipants( $repoAndM->c, false ) );

		// Test the conditions passed to the persistence manager
		$this->assertCampaignCondition( $conditions[0] );
		$this->assertOrganizerCondition( $conditions[1], false );

		$this->assertEquals( 2, count( $conditions ) );
	}

	public function testGetParticipationsIncludingOrganizers() {

		$repoAndM = $this->getParticipationRepoAndMocks();

		// Verifications, argument capture and return value for mock
		// persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'get' )
			->with(
				$this->equalTo( 'IParticipation' ),
				$this->identicalTo( ParticipationField::$ID ),
				$this->identicalTo( Order::$ASCENDING ),
				$this->captureArg( $conditions ),
				$this->equalTo( 10 ),
				$this->equalTo( 'continueKey' ) )
			->will( $this->returnValue( array( $repoAndM->p ) ) );

		// We need a variable to pass by reference
		$continueKey = 'continueKey';

		// Call the method and test the result
		$this->assertSame( array( $repoAndM->p ),
			$repoAndM->pRepo->getParticipations( $repoAndM->c, true, 10,
			$continueKey ) );

		// Test the conditions passed to the persistence manager
		$this->assertCampaignCondition( $conditions[0] );
		$this->assertEquals( 1, count( $conditions ) );
	}

	public function testGetParticipationsExcludingOrganizers() {

		$repoAndM = $this->getParticipationRepoAndMocks();

		// Verifications, argument capture and return value for mock
		// persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'get' )
			->with(
				$this->equalTo( 'IParticipation' ),
				$this->identicalTo( ParticipationField::$ID ),
				$this->identicalTo( Order::$ASCENDING ),
				$this->captureArg( $conditions ),
				$this->equalTo( 10 ),
				$this->equalTo( 'continueKey' ) )
			->will( $this->returnValue( array( $repoAndM->p ) ) );

		// We need a variable to pass by reference
		$continueKey = 'continueKey';

		// Call the method and test the result
		$this->assertSame( array( $repoAndM->p ),
			$repoAndM->pRepo->getParticipations( $repoAndM->c, false, 10,
			$continueKey ) );

		// Test the conditions passed to the persistence manager
		$this->assertCampaignCondition( $conditions[0] );
		$this->assertOrganizerCondition( $conditions[1], false );

		$this->assertEquals( 2, count( $conditions ) );
	}

	public function testGetMaxFetchLimit() {
		$repoAndM = $this->getParticipationRepoAndMocks();

		// Verifications and return value for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'getMaxFetchLimit' )
			->with( $this->equalTo( 'IParticipation' ) )
			->will( $this->returnValue( 200 ) );

		// Call the method and test the result
		$this->assertSame( 200,
			$repoAndM->pRepo->getMaxFetchLimit() );
	}

	/**
	 * Create and set up a fresh ParticipationRepository and some mocks, and
	 * return them wrapped in a stdClass.
	 */
	protected function getParticipationRepoAndMocks() {

		$pm = $this->getMock( 'Campaigns\Persistence\IPersistenceManager' );

		$pFactory =
			$this->getMock( 'Campaigns\Domain\Internal\IParticipationFactory' );

		$pRepo = new ParticipationRepository( $pm, $pFactory );
		$p = $this->getMock( 'Campaigns\Domain\IParticipation' );
		$c = $this->getMock( 'Campaigns\Domain\ICampaign' );

		// Set up the mock campaign to return an id
		$c->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $this->testCampaignId ) );

		return (object) array(
			'pm' => $pm,
			'pFactory' => $pFactory,
			'pRepo' => $pRepo,
			'p' => $p,
			'c' => $c
		);
	}

	/**
	 * Perform assertions with a condition to identify a campaign
	 */
	protected function assertCampaignCondition( $condition ) {

		$this->assertCondition(
			$condition,
			ParticipationField::$CAMPAIGN_ID,
			Operator::$EQUAL,
			$this->testCampaignId
		);
	}

	/**
	 * Perform assertions with a condition to identify a user
	 */
	protected function assertUserCondition( $condition ) {

		$this->assertCondition(
			$condition,
			ParticipationField::$USER_ID,
			Operator::$EQUAL,
			$this->testUserId
		);
	}

	/**
	 * Perform assertions with a condition to identify organizer status
	 */
	protected function assertOrganizerCondition( $condition, $val ) {

		$this->assertCondition(
			$condition,
			ParticipationField::$ORGANIZER,
			Operator::$EQUAL,
			$val
		);
	}

	/**
	 * Convenience method that delegates to TestHelper
	 * @see TestHelper::captureArg()
	 */
	protected function captureArg( &$arg ) {
		return $this->testHelper->captureArg( $arg );
	}

	/**
	 * Convenience method that delegates to TestHelper
	 * @see TestHelper::assertCondition()
	 */
	protected function assertCondition( $c, IField $field, Operator $operator,
		$val) {

		$this->testHelper->assertCondition( $c, $field, $operator, $val);
	}
}