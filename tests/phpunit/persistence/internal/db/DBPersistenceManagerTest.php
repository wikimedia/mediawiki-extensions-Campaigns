<?php

namespace Campaigns\PHPUnit\Persistence\Internal\Db;

use MediaWikiTestCase;
use Campaigns\PHPUnit\TestHelper;
use Campaigns\TypesafeEnum;
use Campaigns\Persistence\IField;
use Campaigns\Persistence\Condition;
use Campaigns\Persistence\Operator;
use Campaigns\Persistence\Order;
use Campaigns\Persistence\Internal\Db\DBPersistenceManager;
use Campaigns\Persistence\Internal\Db\SaveOperation;
use Campaigns\Persistence\Internal\Db\UpdateOrCreateOperation;
use Campaigns\Persistence\Internal\Db\DeleteOperation;

/**
 * @group Database
 */
class DBPersistenceManagerTest extends MediaWikiTestCase {

	protected $testHelper;
	protected $table = 'campaigns_db_persistence_manager_test';

	protected function setUp() {

		parent::setUp();

		// Create a table to use for test
		$db = $this->db;

		$db->query( 'CREATE TEMPORARY TABLE IF NOT EXISTS '
			. $db->tableName( $this->table ) . ' (' .
			'id int unsigned NOT NULL PRIMARY KEY auto_increment,' .
			'field1 varchar(255) UNIQUE,' .
			'field2 varchar(255) );',
			__METHOD__ );

		// TestHelper provides some convenience functionality that we use
		// across tests
		$this->testHelper = new TestHelper( $this );
	}

	public function testConstructorCallsEnsureConfiguredOnMapper() {

		$mapper = $this->getMock(
			'Campaigns\Persistence\Internal\Db\IDBMapper' );

		$mapper->expects( $this->once() )
			->method( 'ensureConfigured' );

		new DBPersistenceManager( $mapper );
	}

	public function testSaveVerifiesRequiredFields() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'SaveVerifiesRequiredFields' );
		$this->prepareMocks( $rowData, $pmAndM, null );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'verifyRequiredFields' )
			->with( $pmAndM->e );

		// Queue and perform the save
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();
	}

	public function testSaveGetsDbMaster() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'SaveGetsDbMaster' );

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( $rowData, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( true ) ) // requesting master
			->will( $this->returnValue( $this->db ) );

		// Queue and perform the save
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();
	}

	public function testSaveGetsTableNameForObject() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'SaveGetsTableNameForObject' );

		// Set up everything _except_ the call to getTableNameForObj
		$this->prepareMocks( $rowData, $pmAndM, null, 'getTableNameForObj' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getTableNameForObj' )
			->with( $this->identicalTo( $pmAndM->e ) )
			->will( $this->returnValue( $this->table ) );

		// Queue and perform the save
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();
	}

	public function testSavePreparesColsAndValsForDbWrite() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'SavePreparesColsAndValsForDbWrite' );

		// Set up everything _except_ the call to preparesColsAndValsForDbWrite
		$this->prepareMocks(
			$rowData, $pmAndM, null, 'prepareColsAndValsForDbWrite' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'prepareColsAndValsForDbWrite' )
			->with( $this->identicalTo( $pmAndM->e ),
				$this->identicalTo( $this->db ) )
			->will( $this->returnValue( $rowData ) );

		// Queue and perform the save
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();
	}

	public function testSaveGetsId() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'SaveGetsId' );

		// Set up everything _except_ the call to getId
		$this->prepareMocks( $rowData, $pmAndM, null, 'getId' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getId' )
			->with( $this->identicalTo( $pmAndM->e ) )
			->will( $this->returnValue( null ) );

		// Queue and perform the save
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();
	}

	public function testSaveInsertsRowAndSetsIdOfNewObjOnInsert() {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		$rowData = $this->prepareRowData(
			'SaveInsertsRowAndSetsIdOfNewObjOnInsert' );

		$this->prepareMocks( $rowData, $pmAndM, null );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'setIdOfNewObj' )
			->with(
				$this->identicalTo( $pmAndM->e ),
				$this->captureArg( $newId ) );

		// Queue and perform the save
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();

		// Verify inserted row in the DB
		$this->assertSelect(
			$this->table,
			array_keys( $rowData ),
			array( 'id' => $newId ),
			array( array_values( $rowData ) )
		);
	}

	public function
		testSaveDoesNotCallDuplicatesCallbackWhenThereAreNoDuplicatesOnInsert() {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		$rowData = $this->prepareRowData(
			'SaveDoesNotCallDuplicatesCallbackWhenThereAreNoDuplicatesOnInsert' );

		$this->prepareMocks( $rowData, $pmAndM, null );

		// Queue and perform the save
		$self = $this;
		$pmAndM->pm->queueSave( $pmAndM->e, function( $obj, $i ) use( $self ) {
			$self->fail( 'Duplicates callback called when it shouldn\'t be.' );
		} );

		$pmAndM->pm->flush();

		// If we got here, the test was a success
		$this->assertTrue( true );
	}

	public function
		testSaveCallsDuplicatesCallbackWhenThereAreDuplicatesOnInsert() {

		// The string we'll use for values in all fields
		$val = 'SaveCallsDuplicatesCallbackWhenThereAreDuplicatesOnInsert';

		$this->insertRow( $val );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		$rowData = $this->prepareRowData( $val );
		$this->prepareMocks( $rowData, $pmAndM, null, null, $val );

		// Save the row with the duplicate values, sending duplicates callback
		$self = $this;
		$called = false;
		$pmAndM->pm->queueSave( $pmAndM->e, function( $obj, $i )
			use( $self, $pmAndM, &$called ) {

			$called = true;
			$self->assertEquals( 'FIELD1', $i );
			$self->assertSame( $pmAndM->e, $obj );
		} );

		$pmAndM->pm->flush();
		$this->assertTrue( $called, 'Duplicates callback not called.' );
	}

	public function testSaveGetsUniqueIndexesWhenThereAreDuplicates() {

		// The string we'll use for values in all fields
		$val = 'SaveGetsUniqueIndexesWhenThereAreDuplicates';

		$this->insertRow( $val );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Row contents are the same as above
		$rowData = $this->prepareRowData( $val );

		// Set up everything _except_ the call to getUniqueIndexes
		$this->prepareMocks(
			$rowData, $pmAndM, null, 'getUniqueIndexes', $val );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getUniqueIndexes' )
			->with( $this->identicalTo( $pmAndM->e ) )
			->will( $this->returnValue(
				array( 'FIELD1' => array( $pmAndM->field1 ) ) ) );

		// Save the row with the duplicate values
		// Also, send an empty callback to avoid throwing an exception
		$pmAndM->pm->queueSave( $pmAndM->e, function( $obj, $i ) { } );
		$pmAndM->pm->flush();
	}

	public function testSaveGetsTypeForObjWhenThereAreDuplicates() {

		// The string we'll use for values in all fields
		$val = 'SaveGetsTypeForObjWhenThereAreDuplicates';

		$this->insertRow( $val );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Row contents are the same as above
		$rowData = $this->prepareRowData( $val );
		$this->prepareMocks( $rowData, $pmAndM, null, null, $val );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getTypeForObj' )
			->with( $this->identicalTo( $pmAndM->e ) );

		// Save the row with the duplicate values
		// Also, send an empty callback to avoid throwing an exception
		$pmAndM->pm->queueSave( $pmAndM->e, function( $obj, $i ) { } );
		$pmAndM->pm->flush();
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage with duplicate value(s) for the unique index FIELD1.
	 */
	public function
		testExcpetionThrownWhenSaveCalledAndThereAreDuplicatesAndNoDuplicatesCallbackWasSent() {

		// The string we'll use for values in all fields
		$val = 'ExcpetionThrownWhenSaveCalledAndThereAreDuplicatesAndNoDuplicatesCallbackWasSent';

		$this->insertRow( $val );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Row contents are the same as above
		$rowData = $this->prepareRowData( $val );
		$this->prepareMocks( $rowData, $pmAndM, null, null, $val );

		// Save the row with the duplicate values
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage Reason: unknown. Possibly due to an attempt to save an entity with a duplicate value for a unique field that was changed immediately after transaction
	 */
	public function
		testExcpetionThrownWhenSaveCalledAndNoRowsAffectedAndNoDuplicatesIdentified() {

		// The string we'll use for values in all fields
		$val = 'ExcpetionThrownWhenSaveCalledAndNoRowsAffectedAndNoDuplicatesIdentified';

		$this->insertRow( $val );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Row contents are the same as above
		$rowData = $this->prepareRowData( $val );

		// Make the DBPersistenceManager look for the wrong duplicate value and
		// thereby not identify the duplicate
		$this->prepareMocks( $rowData, $pmAndM, null, null, 'someOtherVal' );

		// Save the row with the duplicate values
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();
	}

	public function testSaveUpdatesRowOnUpdate() {

		// Initial value of all fields
		$val = 'testSaveUpdatesRowOnUpdate';

		$r = $this->insertRow( $val );

		// Set up everything
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Row with a new value
		$rowData = $this->prepareRowData( $val . 'Updated' );

		$this->prepareMocks( $rowData, $pmAndM, $r->id );

		// Queue and perform the save
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();

		// Verify inserted row in the DB
		$this->assertSelect(
			$this->table,
			array_keys( $rowData ),
			array( 'id' => $r->id ),
			array( array_values( $rowData ) )
		);
	}

	public function testSaveGetsIdFieldForObjOnUpdate() {

		// Initial value of all fields
		$val = 'SaveGetsIdFieldForObjOnUpdate';

		$r = $this->insertRow( $val );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Row with a new value
		$rowData = $this->prepareRowData( $val . 'Updated' );

		// Set up everything _except_ the call to getIdFieldForObj
		$this->prepareMocks( $rowData, $pmAndM, $r->id, 'getIdFieldForObj' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getIdFieldForObj' )
			->with( $this->identicalTo( $pmAndM->e ) )
			->will( $this->returnValue( $pmAndM->idField ) );

		// Queue and perform the save
		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();
	}

	public function
		testSaveDoesNotCallDuplicatesCallbackWhenThereAreNoDuplicatesOnUpdate() {

		// Initial value of all fields
		$val =
			'SaveDoesNotCallDuplicatesCallbackWhenThereAreNoDuplicatesOnUpdate';

		$r = $this->insertRow( $val );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Row with a new value
		$rowData = $this->prepareRowData( $val . 'Updated' );

		$this->prepareMocks( $rowData, $pmAndM, $r->id );

		// Queue and perform the save
		$self = $this;
		$pmAndM->pm->queueSave( $pmAndM->e, function( $obj, $i ) use( $self ) {
			$self->fail( 'Duplicates callback called when it shouldn\'t be.' );
		} );

		$pmAndM->pm->flush();

		// If we got here, the test was a success
		$this->assertTrue( true );
	}

	public function
		testSaveCallsDuplicatesCallbackAndGetsIdWhenThereAreDuplicatesOnUpdate() {

		// Initial value of all fields
		$val = 'SaveCallsDuplicatesCallbackWhenThereAreDuplicatesOnUpdate';

		// Insert two rows with different values
		$r1 = $this->insertRow( $val );
		$r2 = $this->insertRow( $val . 'Different' );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// We'll try to update the second row with the values of the first
		$rowData = $this->prepareRowData( $val );

		// Set up everything _except_ the call to getId
		// Because getId is set up below, no need to send in the id here
		$this->prepareMocks( $rowData, $pmAndM, null, 'getId', $val );

		// These calls are one of the things we're testing here
		$pmAndM->mapper->expects( $this->exactly( 2 ) )
			->method( 'getId' )
			->will( $this->onConsecutiveCalls( $r2->id , $r1->id ) );

		// We need to send in a different object than $pmAndM->e (which will
		// be returned by mapper->makeObjectFromDbRow) and make sure
		// that it won't evaluated as equal to $pmAndM->anotherE.
		$pmAndM->anotherE->field1 = 'someOtherValue';

		// Save the row with the duplicate values, sending duplicates callback
		$self = $this;
		$called = false;
		$pmAndM->pm->queueSave( $pmAndM->anotherE, function ( $obj, $i )
			use ( $self, $pmAndM, &$called ) {

			$called = true;
			$self->assertEquals( 'FIELD1', $i );
			$self->assertSame( $pmAndM->anotherE, $obj );
		} );

		$pmAndM->pm->flush();
		$this->assertTrue( $called, 'Duplicates callback not called.' );
	}


	/**
	 * Test that if a row is updated with the same values that it already had,
	 * the duplicates callback is not called.
	 * Note that this is potentially a problem under MySQL, which will say that
	 * no rows were affected in that case. Since we use rows affected to detect
	 * unique index collisions, there are special checks for this exact
	 * situation. However it's not an issue under SQLite, because in the same
	 * circumstances SQLite _will_ report one row affected.
	 */
	public function
		testSaveDoesNotCallDuplicatesCallbackWhenUpdateMakesNoChanges() {

		// Value for all fields
		$val = 'SaveDoesNotCallDuplicatesCallbackWhenUpdateMakesNoChanges';
		$r = $this->insertRow( $val );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// We'll try to update the same row with the same values
		$rowData = $this->prepareRowData( $val );
		$this->prepareMocks( $rowData, $pmAndM, $r->id, null, $val );

		// Save the row, sending duplicates callback
		$called = false;
		$pmAndM->pm->queueSave( $pmAndM->e,
			function ( $obj, $i ) use ( &$called ) {

			$called = true;
		} );

		$pmAndM->pm->flush();
		$this->assertFalse( $called, 'Duplicates callback called.' );
	}

	public function testUpdateOrCreateGetsId() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'UpdateOrCreateGetsId' );

		// Set up everything _except_ the call to getId
		$this->prepareMocks( $rowData, $pmAndM, null, 'getId' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getId' )
			->with( $this->identicalTo( $pmAndM->e ) )
			->will( $this->returnValue( null ) );

		// Queue and perform the update-or-create
		$pmAndM->pm->queueUpdateOrCreate( $pmAndM->e, array( $pmAndM->field1 ) );
		$pmAndM->pm->flush();
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage that has already been saved. Id: 2
	 */
	public function
		testExceptionThrownWhenUpdateOrCreateIsCalledOnAnObjectWithAnId() {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		$rowData = $this->prepareRowData(
			'ExceptionThrownWhenUpdateOrCreateIsCalledOnAnObjectWithAnId' );

		// 2 will be returned by getId
		$this->prepareMocks( $rowData, $pmAndM, 2 );

		// Queue and perform the update-or-create
		$pmAndM->pm->queueUpdateOrCreate( $pmAndM->e, array( $pmAndM->field1 ) );
		$pmAndM->pm->flush();
	}

	public function testUpdateOrCreateVerifiesRequiredFields() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'UpdateOrCreateVerifiesRequiredFields' );
		$this->prepareMocks( $rowData, $pmAndM, null );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'verifyRequiredFields' )
			->with( $pmAndM->e );

		// Queue and perform the update-or-create
		$pmAndM->pm->queueUpdateOrCreate( $pmAndM->e, array( $pmAndM->field1 ) );
		$pmAndM->pm->flush();
	}

	public function testUpdateOrCreateGetsDbMaster() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'UpdateOrCreateGetsDbMaster' );

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( $rowData, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( true ) ) // requesting master
			->will( $this->returnValue( $this->db ) );

		// Queue and perform the update-or-create
		$pmAndM->pm->queueUpdateOrCreate( $pmAndM->e, array( $pmAndM->field1 ) );
		$pmAndM->pm->flush();
	}

	public function testUpdateOrCreateGetsTableNameForObject() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'UpdateOrCreateGetsTableNameForObject' );

		// Set up everything _except_ the call to getTableNameForObj
		$this->prepareMocks( $rowData, $pmAndM, null, 'getTableNameForObj' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getTableNameForObj' )
			->with( $this->identicalTo( $pmAndM->e ) )
			->will( $this->returnValue( $this->table ) );

		// Queue and perform the update-or-create
		$pmAndM->pm->queueUpdateOrCreate( $pmAndM->e, array( $pmAndM->field1 ) );
		$pmAndM->pm->flush();
	}

	public function testUpdateOrCreatePreparesColsAndValsForDbWrite() {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		$rowData = $this->prepareRowData(
			'UpdateOrCreatePreparesColsAndValsForDbWrite' );

		// Set up everything _except_ the call to prepareColsAndValsForDbWrite
		$this->prepareMocks(
			$rowData, $pmAndM, null, 'prepareColsAndValsForDbWrite' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'prepareColsAndValsForDbWrite' )
			->with( $this->identicalTo( $pmAndM->e ),
				$this->identicalTo( $this->db ) )
			->will( $this->returnValue( $rowData ) );

		// Queue and perform the update-or-create
		$pmAndM->pm->queueUpdateOrCreate( $pmAndM->e, array( $pmAndM->field1 ) );
		$pmAndM->pm->flush();
	}

	public function testUpdateOrCreateGetsDbColumn() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( 'UpdateOrCreateGetsDbColumn' );

		// Set up everything _except_ the call to getDbColumn
		$this->prepareMocks( $rowData, $pmAndM, null, 'getDbColumn' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getDbColumn' )
			->with( $this->identicalTo( $pmAndM->field1 ) )
			->will( $this->returnValue( 'field1' ) );

		// Queue and perform the update-or-create
		$pmAndM->pm->queueUpdateOrCreate( $pmAndM->e, array( $pmAndM->field1 ) );
		$pmAndM->pm->flush();
	}

	public function testUpdateOrCreateInsertsRowWhenExpected() {

		// Value of all fields
		$val = 'UpdateOrCreateInsertsRowWhenExpected';

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( $val );
		$this->prepareMocks( $rowData, $pmAndM, null );

		// Queue and perform the update-or-create
		$pmAndM->pm->queueUpdateOrCreate( $pmAndM->e, array( $pmAndM->field1 ) );
		$pmAndM->pm->flush();

		// Verify inserted row in the DB
		$this->assertSelect(
			$this->table,
			array( 'field2' ),
			array( 'field1' => $val ),
			array( array( $val ) )
		);
	}

	public function testUpdateOrCreateUpdatesRowWhenExpected() {

		// Initial value of all fields
		$val = 'UpdateOrCreateUpdatesRowWhenExpected';

		$r = $this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// To trigger and test the update, field1 stays the same as before and
		// will be used to find the row, and field2 is modified and will be
		// updated.
		$rowData = array(
			'field1' => $val,
			'field2' => $val . 'Updated'
		);

		$this->prepareMocks( $rowData, $pmAndM, null );

		// Queue and perform the update-or-create
		$pmAndM->pm->queueUpdateOrCreate( $pmAndM->e, array( $pmAndM->field1 ) );
		$pmAndM->pm->flush();

		// Verify updated row in the DB
		// Find the row using its ID to make sure it's the not a new row
		$this->assertSelect(
			$this->table,
			array_keys( $rowData ),
			array( 'id' => $r->id ),
			array( array_values( $rowData ) )
		);
	}

	public function testDeleteGetsDbMaster() {

		$r = $this->insertRow( 'DeleteGetsDbMaster' );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( true ) ) // requesting master
			->will( $this->returnValue( $this->db ) );

		// Make condition and queue and perform the delete
		$condition = new Condition(
			$pmAndM->idField,
			Operator::$EQUAL,
			$r->id
		);

		$pmAndM->pm->queueDelete( 'MockEntity', $condition );
		$pmAndM->pm->flush();
	}

	public function testDeleteGetsTableNameForType() {

		$r = $this->insertRow( 'DeleteGetsTableNameForType' );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getTableNameForType' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getTableNameForType' )
			->with( $this->equalTo( 'MockEntity' ) )
			->will( $this->returnValue( $this->table ) );

		// Make condition and queue and perform the delete
		$condition = new Condition(
			$pmAndM->idField,
			Operator::$EQUAL,
			$r->id
		);

		$pmAndM->pm->queueDelete( 'MockEntity', $condition );
		$pmAndM->pm->flush();
	}

	public function testDeleteDeletesRow() {

		$r = $this->insertRow( 'DeleteDeletesRow' );

		$pmAndM = $this->getPersistenceManagerAndMocks();

		$this->prepareMocks( null, $pmAndM, null );

		// Make condition and queue and perform the delete
		$condition = new Condition(
			$pmAndM->idField,
			Operator::$EQUAL,
			$r->id
		);

		$pmAndM->pm->queueDelete( 'MockEntity', $condition );
		$pmAndM->pm->flush();

		$this->assertFalse( $this->db->selectRow(
			$this->table,
			'*',
			array( 'id' => $r->id )
		) );
	}

	public function testFlushPerformsOperationsInTheOrderReceived() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$pm = new PMForTestingFlush( $pmAndM->mapper );

		// Queue and flush some operations
		// We're abusing the intent of the second parameter of some queue
		// methods, but it works well enough for this test.
		// Here we're also making sure that consecutive operations that have
		// the same class but different contents don't get combined.
		$pm->queueSave( $pmAndM->e );
		$pm->queueUpdateOrCreate( $pmAndM->e, array( 1 ) );
		$pm->queueDelete( $pmAndM->e, array( 2 ) );
		$pm->queueUpdateOrCreate( $pmAndM->e, array( 3 ) );
		$pm->queueUpdateOrCreate( $pmAndM->e, array( 4 ) );
		$pm->flush();

		$op = $pm->opsPerformed;
		$ns = 'Campaigns\Persistence\Internal\Db\\';

		$this->assertInstanceOf( $ns . 'SaveOperation', $op[0] );

		$this->assertInstanceOf( $ns . 'UpdateOrCreateOperation', $op[1] );
		$this->assertEquals( 1, $op[1]->conditionFields[0] );

		$this->assertInstanceOf( $ns . 'DeleteOperation', $op[2] );
		$this->assertEquals( 2, $op[2]->conditions[0] );

		$this->assertInstanceOf( $ns . 'UpdateOrCreateOperation', $op[3] );
		$this->assertEquals( 3, $op[3]->conditionFields[0] );

		$this->assertInstanceOf( $ns . 'UpdateOrCreateOperation', $op[4] );
		$this->assertEquals( 4, $op[4]->conditionFields[0] );

		$this->assertEquals( 5, count( $op ) );
	}

	public function testFlushCombinesEquivalentConsecutiveOperations() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$pm = new PMForTestingFlush( $pmAndM->mapper );

		// Queue and flush some operations
		// We're abusing the intent of the second parameter of some queue
		// methods, but it works well enough for this test.
		$pm->queueSave( $pmAndM->e );
		$pm->queueSave( $pmAndM->e );
		$pm->queueUpdateOrCreate( $pmAndM->e, array( 1 ) );
		$pm->queueUpdateOrCreate( $pmAndM->e, array( 1 ) );
		$pm->queueDelete( $pmAndM->e, array( 2 ) );
		$pm->queueUpdateOrCreate( $pmAndM->e, array( 3 ) );
		$pm->queueUpdateOrCreate( $pmAndM->e, array( 3 ) );
		$pm->flush();

		$op = $pm->opsPerformed;
		$ns = 'Campaigns\Persistence\Internal\Db\\';

		$this->assertInstanceOf( $ns . 'SaveOperation', $op[0] );

		$this->assertInstanceOf( $ns . 'UpdateOrCreateOperation', $op[1] );
		$this->assertEquals( 1, $op[1]->conditionFields[0] );

		$this->assertInstanceOf( $ns . 'DeleteOperation', $op[2] );
		$this->assertEquals( 2, $op[2]->conditions[0] );

		$this->assertInstanceOf( $ns . 'UpdateOrCreateOperation', $op[3] );
		$this->assertEquals( 3, $op[3]->conditionFields[0] );

		$this->assertEquals( 4, count( $op ) );
	}

	public function testExistsWithConditionsReturnsTrueWhenExpected() {

		$val = 'ExistsWithConditionsReturnsTrueWhenExpected';
		$this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();
		$this->prepareMocks( null, $pmAndM, null );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$this->assertTrue(
			$pmAndM->pm->existsWithConditions( 'MockEntity', $condition ) );
	}

	public function testExistsWithConditionsReturnsFalseWhenExpected() {

		$val = 'ExistsWithConditionsReturnsFalseWhenExpected';
		$pmAndM = $this->getPersistenceManagerAndMocks();
		$this->prepareMocks( null, $pmAndM, null );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$this->assertFalse(
			$pmAndM->pm->existsWithConditions( 'MockEntity', $condition ) );
	}

	public function testExistsWithConditionsGetsMasterDbWhenRequested() {

		$val = 'ExistsWithConditionsGetsMasterDbWhenRequested';
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( true ) ) // requesting master
			->will( $this->returnValue( $this->db ) );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$pmAndM->pm->existsWithConditions( 'MockEntity', $condition, true );
	}

	public function testExistsWithConditionsGetsSlaveDbWhenRequested() {

		$val = 'ExistsWithConditionsGetsSlaveDbWhenRequested';
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( false ) ) // not requesting master
			->will( $this->returnValue( $this->db ) );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$pmAndM->pm->existsWithConditions( 'MockEntity', $condition );
	}

	public function testGetOneGetsMasterDbWhenRequested() {

		$val = 'GetOneGetsMasterDbWhenRequested';
		$this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( true ) ) // requesting master
			->will( $this->returnValue( $this->db ) );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$pmAndM->pm->getOne( 'MockEntity', $condition, true );
	}

	public function testGetOneGetsSlaveDbWhenRequested() {

		$val = 'GetOneGetsSlaveDbWhenRequested';
		$this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( false ) ) // not requesting master
			->will( $this->returnValue( $this->db ) );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$pmAndM->pm->getOne( 'MockEntity', $condition );
	}

	public function testGetOneGetsTableNameForType() {

		$val = 'GetOneGetsTableNameForType';
		$this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getTableNameForType
		$this->prepareMocks( null, $pmAndM, null, 'getTableNameForType' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getTableNameForType' )
			->with( $this->equalTo( 'MockEntity' ) )
			->will( $this->returnValue( $this->table ) );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$pmAndM->pm->getOne( 'MockEntity', $condition );
	}

	public function testGetOneCallsMakeObjFromDbRow() {

		$val = 'GetOneCallsMakeObjFromDbRow';
		$r = $this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to makeObjectFromDbRow
		$this->prepareMocks( null, $pmAndM, null, 'makeObjectFromDbRow' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'makeObjectFromDbRow' )
			->with( $this->equalTo( 'MockEntity' ),
				$this->equalTo( $r ) )
			->will( $this->returnValue( $pmAndM->e ) );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$pmAndM->pm->getOne( 'MockEntity', $condition );
	}

	public function testGetOneReturnsEntityWhenExpected() {

		$val = 'GetOneReturnsEntityWhenExpected';
		$this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();
		$this->prepareMocks( null, $pmAndM, null );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$this->assertSame(
			$pmAndM->e, $pmAndM->pm->getOne( 'MockEntity', $condition ) );
	}

	public function testGetOneReturnsNullWhenExpected() {

		$val = 'GetOneReturnsNullWhenExpected';
		$pmAndM = $this->getPersistenceManagerAndMocks();
		$this->prepareMocks( null, $pmAndM, null );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$this->assertNull( $pmAndM->pm->getOne( 'MockEntity', $condition ) );
	}

	public function testGetOneByIdGetsIdFieldForType() {

		$val = 'testGetOneByIdGetsIdFieldForType';
		$r = $this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getIdFieldForType
		$this->prepareMocks( null, $pmAndM, null, 'getIdFieldForType' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getIdFieldForType' )
			->with( $this->equalTo( 'MockEntity' ) )
			->will( $this->returnValue( $pmAndM->idField ) );

		$pmAndM->pm->getOneById( 'MockEntity', $r->id, true );
	}

	public function testGetOneByIdGetsMasterDbWhenRequested() {

		$val = 'GetOneByIdGetsMasterDbWhenRequested';
		$r = $this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( true ) ) // requesting master
			->will( $this->returnValue( $this->db ) );

		$pmAndM->pm->getOneById( 'MockEntity', $r->id, true );
	}

	public function testGetOneByIdGetsSlaveDbWhenRequested() {

		$val = 'GetOneByIdGetsSlaveDbWhenRequested';
		$r = $this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( false ) ) // not requesting master
			->will( $this->returnValue( $this->db ) );

		$pmAndM->pm->getOneById( 'MockEntity', $r->id );
	}

	public function testGetOneByIdReturnsEntityWhenExpected() {

		$val = 'GetOneByIdReturnsEntityWhenExpected';
		$r = $this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();
		$this->prepareMocks( null, $pmAndM, null );

		$this->assertSame(
			$pmAndM->e, $pmAndM->pm->getOneById( 'MockEntity', $r->id ) );
	}

	public function testGetOneByIdReturnsNullWhenExpected() {

		$val = 'GetOneByIdReturnsNullWhenExpected';
		$pmAndM = $this->getPersistenceManagerAndMocks();
		$this->prepareMocks( null, $pmAndM, null );

		// Send in very unlikely ID
		$this->assertNull( $pmAndM->pm->getOneById( 'MockEntity', 241231 ) );
	}

	public function testCountGetsMasterDbWhenRequested() {

		$val = 'CountGetsMasterDbWhenRequested';
		$this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( true ) ) // requesting master
			->will( $this->returnValue( $this->db ) );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$pmAndM->pm->count( 'MockEntity', $condition, true );
	}

	public function testCountGetsSlaveDbWhenRequested() {

		$val = 'CountGetsSlaveDbWhenRequested';
		$this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( false ) ) // not requesting master
			->will( $this->returnValue( $this->db ) );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$pmAndM->pm->count( 'MockEntity', $condition );
	}

	public function testCountGetsTableNameForType() {

		$val = 'CountGetsTableNameForType';
		$this->insertRow( $val );
		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getTableNameForType
		$this->prepareMocks( null, $pmAndM, null, 'getTableNameForType' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getTableNameForType' )
			->with( $this->equalTo( 'MockEntity' ) )
			->will( $this->returnValue( $this->table ) );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$EQUAL,
			$val
		);

		$pmAndM->pm->count( 'MockEntity', $condition );
	}

	public function testCountReturnsCorrectValue() {

		// Insert three rows with the similar values
		$this->insertRow( 'CountReturnsCorrectValue1' );
		$this->insertRow( 'CountReturnsCorrectValue2' );
		$this->insertRow( 'CountReturnsCorrectValue3' );

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$this->prepareMocks( null, $pmAndM, null );

		// Set up a condition that should select them all
		$condition = new Condition(
			$pmAndM->field1,
			Operator::$LIKE,
			array( 'CountReturnsCorrectValue', $this->db->anyString() )
		);

		$this->assertEquals( 3, $pmAndM->pm->count( 'MockEntity', $condition ) );
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage $orderByField must be unique or an id
	 */
	public function
		testExceptionThrownWhenGetIsCalledWithUnusableOrderByField() {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// This is the only setup required to trigger the exception
		$pmAndM->mapper->expects( $this->any() )
			->method( 'fieldHasOption' )
			->will( $this->returnValue( false ) );

		$pmAndM->pm->get( 'MockEntity', $pmAndM->field1, Order::$ASCENDING );
	}

	public function testGetGetsSlaveDb() {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDb
		$this->prepareMocks( null, $pmAndM, null, 'getDb' );

		// This is the call that we're testing
		$pmAndM->pm->expects( $this->once() )
			->method( 'getDb' )
			->with( $this->equalTo( false ) ) // not requesting master
			->will( $this->returnValue( $this->db ) );

		$pmAndM->pm->get( 'MockEntity', $pmAndM->field1, Order::$ASCENDING );
	}

	public function testGetGetsTableNameForType() {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getTableNameForType
		$this->prepareMocks( null, $pmAndM, null, 'getTableNameForType' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getTableNameForType' )
			->with( $this->equalTo( 'MockEntity' ) )
			->will( $this->returnValue( $this->table ) );

		$pmAndM->pm->get( 'MockEntity', $pmAndM->field1, Order::$ASCENDING );
	}

	public function testGetGetsOrderByDbColumn() {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Set up everything _except_ the call to getDbColumn
		$this->prepareMocks( null, $pmAndM, null, 'getDbColumn' );

		// This is the call that we're testing
		$pmAndM->mapper->expects( $this->once() )
			->method( 'getDbColumn' )
			->with( $this->identicalTo( $pmAndM->field1 ) )
			->will( $this->returnValue( 'field1' ) );

		$pmAndM->pm->get( 'MockEntity', $pmAndM->field1, Order::$ASCENDING );
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage Illegal argument: $conditions must be a Condition or an array of Conditions
	 */
	public function
		testExceptionThrownWhenGetIsCalledWithIncorrectConditionsParameter() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$this->prepareMocks( null, $pmAndM, null );

		$pmAndM->pm->get( 'MockEntity', $pmAndM->field1, Order::$ASCENDING,
			'not null, not an array, not a Condition' );
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage entities of the type MockEntity requested, but the  maximum is
	 */
	public function
		testExceptionThrownWhenGetIsCalledWithAFetchLimitThatIsTooLarge() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$this->prepareMocks( null, $pmAndM, null );

		$pmAndM->pm->get( 'MockEntity', $pmAndM->field1, Order::$ASCENDING,
			null, $pmAndM->pm->getMaxFetchLimit( 'MockEntity' ) + 1 );
	}

	public function testGetReturnsEntitiesInCorrectOrder() {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$valBase = 'GetEntity';

		// Insert some rows, not in the order we expect to receive them in
		$r2 = $this->insertRow( $valBase . '2' );
		$this->insertRow( 'NonMatchingPrefix' . $valBase );
		$r0 = $this->insertRow( $valBase . '0' );
		$r1 = $this->insertRow( $valBase . '1' );

		// Mock setup; get() will return rows as sent to makeObjectFromDbRow
		$this->prepareMocksForGetEntities( $pmAndM, $valBase );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$LIKE,
			array( $valBase, $this->db->anyString() )
		);

		$result = $pmAndM->pm->get( 'MockEntity', $pmAndM->field1,
			Order::$ASCENDING, $condition );

		// Check that we got all three matching rows in the correct order
		$orderedRowObjs = array( $r0, $r1, $r2 );
		$this->assertEquals( $orderedRowObjs, $result );

		// Send some stuff to dependent tests
		return (object) array(
			'orderedRowObjs' => $orderedRowObjs,
			'valBase' => $valBase
		);
	}

	/**
	 * @depends testGetReturnsEntitiesInCorrectOrder
	 */
	public function
		testGetUsesFetchLimitAndSetsContinueKeyAsExpected( $fromPrevious ) {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Mock setup; get() will return rows as sent to makeObjectFromDbRow
		$this->prepareMocksForGetEntities( $pmAndM, $fromPrevious->valBase );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$LIKE,
			array( $fromPrevious->valBase, $this->db->anyString() )
		);

		$continueKey = null;
		$result = $pmAndM->pm->get( 'MockEntity', $pmAndM->field1,
			Order::$ASCENDING, $condition, 2, $continueKey );

		// Test that we only got the first two rows
		$this->assertEquals(
			array_slice( $fromPrevious->orderedRowObjs, 0, 2 ), $result );

		// Test that $continueKey was set as expected
		$this->assertEquals( $fromPrevious->valBase . '2', $continueKey );

		return $fromPrevious;
	}

	/**
	 * @depends testGetUsesFetchLimitAndSetsContinueKeyAsExpected
	 */
	public function
		testGetUsesAndResetsContinueKeyAsExpected( $fromPrevious ) {

		$pmAndM = $this->getPersistenceManagerAndMocks();

		// Mock setup; get() will return rows as sent to makeObjectFromDbRow
		$this->prepareMocksForGetEntities( $pmAndM, $fromPrevious->valBase );

		$condition = new Condition(
			$pmAndM->field1,
			Operator::$LIKE,
			array( $fromPrevious->valBase, $this->db->anyString() )
		);

		$continueKey = $fromPrevious->valBase . '2';

		$result = $pmAndM->pm->get( 'MockEntity', $pmAndM->field1,
			Order::$ASCENDING, $condition, 2, $continueKey );

		// Test that we only got the last row
		$this->assertEquals(
			array_slice( $fromPrevious->orderedRowObjs, 2 ), $result );

		// Since there are no more blocks, $continueKey should be nulled
		$this->assertNull( $continueKey );

		return $fromPrevious;
	}

	/**
	 * Insert a row in the test table with all fields set to $val.
	 * This is useful in tests that require a row with known values already in
	 * the DB.
	 *
	 * @param string $val The value to insert in all the fields
	 * @return stdClass An object like the one that would be returned by
	 *   ResultWrapper for the new row
	 */
	protected function insertRow( $val ) {

		$pmAndM = $this->getPersistenceManagerAndMocks();
		$rowData = $this->prepareRowData( $val );
		$this->prepareMocks( $rowData, $pmAndM, null );

		// Capture the new id
		$pmAndM->mapper->expects( $this->any() )
			->method( 'setIdOfNewObj' )
			->with( $this->identicalTo( $pmAndM->e ),
				$this->captureArg( $newId ) );

		$pmAndM->pm->queueSave( $pmAndM->e );
		$pmAndM->pm->flush();

		return (object) array_merge( $rowData, array(
			'id' => $newId
		) );
	}

	/**
	 * Prepare an associative array of the form columnName => value
	 *
	 * @param string $val The value to give all the fields
	 * @return array
	 */
	protected function prepareRowData( $val ) {
		return array(
			'field1' => $val,
			'field2' => $val
		);
	}

	/**
	 * Prepare several common mocks' responses.
	 *
	 * Here we set up the mocks so the various operations succeed, but we avoid
	 * verifying calls to mocks, instead expecting any number of calls
	 * ($this->any()) and omitting checks on arguemts (->with(...)).
	 *
	 * The setup of any method can be skipped by setting $omit to the name of
	 * that method. Tests can thus select a call to verify and set up
	 * different, more stringent expectations for that specific call. They use
	 * this method to set up only standard responses without verifications,
	 * sending $omit to omit the setup of the method being verified.
	 *
	 * Note that here we only set up calls that must return a value for
	 * operations to succeed.
	 *
	 * @param array $rowData The object data for prepareColsAndValsForDbWrite
	 * @param stdClass $pmAndM DBPersistenceManager and mocks
	 * @param int $objId The ID to return for getId
	 * @param string $omit String for indicating a response not to set up
	 * @param string $mockDupVal String for getFieldValue (used when checking
	 *   duplicate values)
	 */
	protected function prepareMocks( $rowData, $pmAndM, $objId, $omit=null,
		$mockDupVal='mockDupVal' ) {

		// getDb
		if ( $omit !== 'getDb' ) {

			$pmAndM->pm->expects( $this->any() )
				->method( 'getDb' )
				->will( $this->returnValue( $this->db ) );
		}

		// getTableNameForObj
		if ( $omit !== 'getTableNameForObj' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'getTableNameForObj' )
				->will( $this->returnValue( $this->table ) );
		}

		// getTableNameForType
		if ( $omit !== 'getTableNameForType' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'getTableNameForType' )
				->will( $this->returnValue( $this->table ) );
		}

		// prepareColsAndValsForDbWrite
		if ( $omit !== 'prepareColsAndValsForDbWrite' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'prepareColsAndValsForDbWrite' )
				->will( $this->returnValue( $rowData ) );
		}

		// getId
		if ( $omit !== 'getId' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'getId' )
				->will( $this->returnValue( $objId ) );
		}

		// getUniqueIndexes
		if ( $omit !== 'getUniqueIndexes' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'getUniqueIndexes' )
				->will( $this->returnValue(
					array( 'FIELD1' => array( $pmAndM->field1 ) ) ) );
		}

		// getFieldValue
		if ( $omit !== 'getFieldValue' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'getFieldValue' )
				->will( $this->returnValue( $mockDupVal ) );
		}

		// getName (on field)
		if ( $omit !== 'getNameOnField' ) {

			$pmAndM->field1->expects( $this->any() )
				->method( 'getName' )
				->will( $this->returnValue( 'FIELD1' ) );
		}

		// getIdFieldForObj
		if ( $omit !== 'getIdFieldForObj' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'getIdFieldForObj' )
				->will( $this->returnValue( $pmAndM->idField ) );
		}

		// getIdFieldForType
		if ( $omit !== 'getIdFieldForType' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'getIdFieldForType' )
				->will( $this->returnValue( $pmAndM->idField ) );
		}

		// getDbColumn
		if ( $omit !== 'getDbColumn' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'getDbColumn' )
				->will( $this->returnCallback( function( $arg ) use ( $pmAndM ) {
					if ( $arg === $pmAndM->field1 ) {
						return 'field1';
					} elseif ( $arg === $pmAndM->idField ) {
						return 'id';
					}
				} ) );
		}

		// makeObjectFromDbRow
		if ( $omit !== 'makeObjectFromDbRow' ) {

			$pmAndM->mapper->expects( $this->any() )
				->method( 'makeObjectFromDbRow' )
				->will( $this->returnValue( $pmAndM->e ) );
		}

		// fieldHasOption
		if ( $omit !== 'fieldHasOption' ) {
			$pmAndM->mapper->expects( $this->any() )
				->method( 'fieldHasOption' )
				->will( $this->returnValue( true ) );
		}
	}

	/**
	 * Prepare mocks as required for a few tests of get()
	 */
	protected function prepareMocksForGetEntities( $pmAndM ) {

		// Set up everything _except_ the call to makeObjectFromDbRow
		$this->prepareMocks( null, $pmAndM, null, 'makeObjectFromDbRow' );

		$pmAndM->mapper->expects( $this->any() )
			->method( 'makeObjectFromDbRow' )
			->with( $this->equalTo( 'MockEntity' ) )
			->will( $this->returnArgument( 1 ) );
	}

	/**
	 * Get a DBPersistenceManager to test, and some useful mocks.
	 *
	 * @return StdClass
	 */
	protected function getPersistenceManagerAndMocks() {

		$e = $this->getMock(
			'Campaigns\PHPUnit\Persistence\Internal\Db\MockEntity' );

		$anotherE = $this->getMock(
			'Campaigns\PHPUnit\Persistence\Internal\Db\MockEntity' );

		$mapper = $this->getMock(
			'Campaigns\Persistence\Internal\Db\IDBMapper' );

		// Instead of the real class, we'll be testing a mock subclass
		// that replaces the getDb method. That way we can test that it's
		// called. The rest of the class is the same as the original.
		$pm = $this->getMockBuilder(
			'Campaigns\Persistence\Internal\Db\DBPersistenceManager' )
			->setMethods( array( 'getDb' ) )
			->setConstructorArgs( array( $mapper ) )
			->getMock();

		$field1 = $this->getMock(
			'Campaigns\PHPUnit\Persistence\Internal\Db\MockField' );

		$idField = $this->getMock(
			'Campaigns\PHPUnit\Persistence\Internal\Db\MockField' );

		return (object) array(
			'e' => $e,
			'anotherE' => $anotherE,
			'mapper' => $mapper,
			'pm' => $pm,
			'field1' => $field1,
			'idField' => $idField
		);
	}

	/**
	 * Convenience method that delegates to TestHelper
	 * @see TestHelper::captureArg()
	 */
	protected function captureArg( &$arg ) {
		return $this->testHelper->captureArg( $arg );
	}
}

class MockEntity {
	public $field1 = 'someValue';
}

interface MockField extends IField {
	public function getName();
}

/**
 * A subclass of DBPersistenceManager for spying on how operations are
 * performed by flush().
 */
class PMForTestingFlush extends DBPersistenceManager {

	var $opsPerformed = array();

	protected function save( SaveOperation $op ) {
		$this->opsPerformed[] = $op;
	}

	protected function updateOrCreate( UpdateOrCreateOperation $op ) {
		$this->opsPerformed[] = $op;
	}

	protected function delete( DeleteOperation $op ) {
		$this->opsPerformed[] = $op;
	}
}