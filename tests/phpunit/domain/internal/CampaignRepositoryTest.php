<?php

namespace Campaigns\PHPUnit\Domain\Internal;

use MediaWikiTestCase;
use Campaigns\ConnectionType;
use Campaigns\PHPUnit\TestHelper;
use Campaigns\Domain\ICampaign;
use Campaigns\Domain\Internal\CampaignRepository;
use Campaigns\Domain\Internal\ICampaignFactory;
use Campaigns\Domain\Internal\CampaignField;
use Campaigns\Persistence\IPersistenceManager;
use Campaigns\Persistence\IField;
use Campaigns\Persistence\Operator;
use Campaigns\Persistence\Order;

/**
 * @group Campaigns
 */
class CampaignRepositoryTest extends MediaWikiTestCase {

	protected $testHelper;

	protected function setUp() {
		parent::setUp();
		$this->testHelper = new TestHelper( $this );
	}

	public function testCreateCampaignCallsFactory() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications and return value for mock campaign factory
		$repoAndM->cFactory->expects( $this->once() )
			->method( 'create' )
			->with( $this->equalTo( 'urlKey' ), $this->equalTo( 'name' ) )
			->will( $this->returnValue( $repoAndM->c ) );

		// These are used in the next test
		return array( $repoAndM->c,
			$repoAndM->cRepo->createCampaign( 'urlKey', 'name' ) );
	}

	/**
	 * @depends testCreateCampaignCallsFactory
	 */
	public function
		testCreateCampaignReturnsNewCampaign( $providedAndReceivedCampaigns ) {

		$receivedC = $providedAndReceivedCampaigns[0];
		$providedC = $providedAndReceivedCampaigns[1];
		$this->assertSame( $providedC, $receivedC );
	}

	public function testCreateCampaignQueuesSave() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Campaign factory return value
		$repoAndM->cFactory->expects( $this->any() )
			->method( 'create' )
			->will( $this->returnValue( $repoAndM->c ) );

		// Verifications for mock persistence manager
		// Second argument of queueSave ($duplicatesCallback) is tested later
		$repoAndM->pm->expects( $this->once() )
			->method( 'queueSave' )
			->with( $this->identicalTo( $repoAndM->c ), $this->anything() );

		$repoAndM->cRepo->createCampaign( 'urlKey', 'name' );
	}

	public function testSaveCampaignQueuesSave() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications for mock persistence manager
		// Second argument of queueSave ($duplicatesCallback) is tested later
		$repoAndM->pm->expects( $this->once() )
			->method( 'queueSave' )
			->with( $this->identicalTo( $repoAndM->c ), $this->anything() );

		$repoAndM->cRepo->saveCampaign( $repoAndM->c );
	}

	/**
	 * @expectedException Campaigns\Domain\CampaignUrlKeyNotUniqueException
	 * @expectedExceptionMessage Campaign url key duplicateUrlKey not unique
	 */
	public function
		testExceptionThrownOnDuplicateUrlKeyByCallbackWhenCampaignIsSaved() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Get the $duplicatesCallback
		$duplicatesCallback =
			$this->getDuplicatesCallbackOnSaveCampaign( $repoAndM );

		// Make the mock campaign provide the correct value
		$repoAndM->c->expects( $this->any() )
			->method( 'getUrlKey' )
			->will( $this->returnValue( 'duplicateUrlKey' ) );

		// This will throw the exception.
		// Note that the exception itself is not mocked, and does contain
		// a wee bit of logic.
		$duplicatesCallback( $repoAndM->c, 'URL_KEY');
	}

	/**
	 * @expectedException Campaigns\Domain\CampaignNameNotUniqueException
	 * @expectedExceptionMessage Campaign name duplicateName not unique
	 */
	public function
		testExceptionThrownOnDuplicateNameByCallbackWhenCampaignIsSaved() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Get the $duplicatesCallback
		$duplicatesCallback =
			$this->getDuplicatesCallbackOnSaveCampaign( $repoAndM );

		// Make the mock campaign provide the correct value
		$repoAndM->c->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( 'duplicateName' ) );

		// This will throw the exception.
		// Note that the exception itself is not mocked, and does contain
		// a wee bit of logic.
		$duplicatesCallback( $repoAndM->c, 'NAME');
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage Problem handling duplicate values
	 */
	public function
		testExceptionThrownOnUnkownUniqueIndexDuplicateByCallbackWhenCampaignIsSaved() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Get the $duplicatesCallback
		$duplicatesCallback =
			$this->getDuplicatesCallbackOnSaveCampaign( $repoAndM );

		// This will throw the exception
		$duplicatesCallback( $repoAndM->c, 'NON_EXISTENT_FIELD');
	}

	public function testCountCampaigns() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'count' )
			->with( $this->equalTo( 'ICampaign' ) )
			->will( $this->returnValue( 42 ) );

		$repoAndM->cRepo->countCampaigns();
	}

	public function testExistsCampaignWithUrlKey() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications and argument capture for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'existsWithConditions' )
			->with( $this->equalTo( 'ICampaign' ),
				$this->captureArg( $condition ) )
			->will( $this->returnValue( true ) );

		// Result should be what the persistence manager replies
		$this->assertTrue(
			$repoAndM->cRepo->existsCampaignWithUrlKey( 'urlKey' ) );

		// Test the condition
		$this->assertCondition(
			$condition,
			CampaignField::$URL_KEY,
			Operator::$EQUAL,
			'urlKey'
		);
	}

	public function testExistsCampaignWithName() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications and argument capture for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'existsWithConditions' )
			->with( $this->equalTo( 'ICampaign' ),
				$this->captureArg( $condition ) )
			->will( $this->returnValue( true ) );

		// Result should be what the persistence manager replies
		$this->assertTrue(
			$repoAndM->cRepo->existsCampaignWithName( 'name' ) );

		// Test the condition
		$this->assertCondition(
			$condition,
			CampaignField::$NAME,
			Operator::$EQUAL,
			'name'
		);
	}

	public function testGetCampaignById() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'getOneById' )
			->with(
				$this->equalTo( 'ICampaign' ),
				$this->equalTo( 7 ),
				$this->equalTo( ConnectionType::$MASTER ) )
			->will( $this->returnValue( $repoAndM->c ) );

		$this->assertSame( $repoAndM->c,
			$repoAndM->cRepo->getCampaignById( 7, ConnectionType::$MASTER ) );
	}

	public function testGetCampaignByUrlKey() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'getOne' )
			->with(
				$this->equalTo( 'ICampaign' ),
				$this->captureArg( $condition ),
				$this->equalTo( ConnectionType::$MASTER ) )
			->will( $this->returnValue( $repoAndM->c ) );

		$this->assertSame( $repoAndM->c,
			$repoAndM->cRepo->getCampaignByUrlKey(
			'urlKey', ConnectionType::$MASTER ) );

		// Test the condition
		$this->assertCondition(
			$condition,
			CampaignField::$URL_KEY,
			Operator::$EQUAL,
			'urlKey'
		);
	}

	public function testGetCampaignByName() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'getOne' )
			->with(
				$this->equalTo( 'ICampaign' ),
				$this->captureArg( $condition ),
				$this->equalTo( ConnectionType::$MASTER ) )
			->will( $this->returnValue( $repoAndM->c ) );

		$this->assertSame( $repoAndM->c,
			$repoAndM->cRepo->getCampaignByName(
			'name', ConnectionType::$MASTER ) );

		// Test the condition
		$this->assertCondition(
			$condition,
			CampaignField::$NAME,
			Operator::$EQUAL,
			'name'
		);
	}

	public function testGetCampaignsWithNamePrefix() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'get' )
			->with(
				$this->equalTo( 'ICampaign' ),
				$this->identicalTo( CampaignField::$NAME ),
				$this->identicalTo( Order::$ASCENDING ),
				$this->captureArg( $condition ),
				$this->equalTo( 10 ),
				$this->equalTo( 'continueKey' ) )
			->will( $this->returnValue( array( $repoAndM->c ) ) );

		$repoAndM->pm->expects( $this->once() )
			->method( 'getAnyStringForLikeOperator' )
			->will( $this->returnValue( '*' ) );

		// We need a variable to pass by reference
		$continueKey = 'continueKey';

		// Call the method and test the result
		$this->assertSame( array( $repoAndM->c ),
			$repoAndM->cRepo->getCampaigns( 'Name prefix', 10, $continueKey ) );

		// Test the condition
		$this->assertCondition(
			$condition,
			CampaignField::$NAME,
			Operator::$LIKE,
			array( 'Name prefix', '*')
		);
	}

	public function testGetCampaignsWithoutNamePrefix() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'get' )
			->with(
				$this->equalTo( 'ICampaign' ),
				$this->identicalTo( CampaignField::$NAME ),
				$this->identicalTo( Order::$ASCENDING ),
				$this->captureArg( $condition ),
				$this->equalTo( 10 ),
				$this->equalTo( 'continueKey' ) )
			->will( $this->returnValue( array( $repoAndM->c ) ) );

		// We need a variable to pass by reference
		$continueKey = 'continueKey';

		// Call the method and test the result
		$this->assertSame( array( $repoAndM->c ),
			$repoAndM->cRepo->getCampaigns( null, 10, $continueKey ) );
	}

	public function testGetMaxFetchLimit() {

		$repoAndM = $this->getCampaignRepoAndMocks();

		// Verifications for mock persistence manager
		$repoAndM->pm->expects( $this->once() )
			->method( 'getMaxFetchLimit' )
			->with( $this->equalTo( 'ICampaign' ) )
			->will( $this->returnValue( 200 ) );

		// Call the method and test the result
		$this->assertSame( 200,
			$repoAndM->cRepo->getMaxFetchLimit() );
	}

	// TODO: test getOrCreateCampaignEnsureUrlKey()

	/**
	 * Create and set up a fresh CampaignRepository and some mocks, and return
	 * them wrapped in a stdClass.
	 */
	protected function getCampaignRepoAndMocks() {

		$pm = $this->getMock( 'Campaigns\Persistence\IPersistenceManager' );

		$cFactory =
			$this->getMock( 'Campaigns\Domain\Internal\ICampaignFactory' );

		$cRepo = new CampaignRepository( $pm, $cFactory );
		$c = $this->getMock( 'Campaigns\Domain\ICampaign' );

		return (object) array(
			'pm' => $pm,
			'cFactory' => $cFactory,
			'cRepo' => $cRepo,
			'c' => $c,
		);
	}

	/**
	 * Call CampaignRepository::saveCampaign(), capture the $duplicatesCallback
	 * parameter passed to IPersistenceManager::queueSave(), and return it.
	 */
	protected function getDuplicatesCallbackOnSaveCampaign( $repoAndM ) {

		// Set up mock behavior and capture parameter
		$repoAndM->pm->expects( $this->any() )
			->method( 'queueSave' )
			->with( $this->identicalTo( $repoAndM->c ),
			$this->captureArg( $duplicatesCallback ) );

		// Here, the repo should call the above method on the mock
		$repoAndM->cRepo->saveCampaign( $repoAndM->c );

		// This should now contain the $duplicatesCallback the repo passed
		// to the mock
		return $duplicatesCallback;
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
