<?php

namespace Campaigns\PHPUnit\Persistence\Internal\Db;

use MediaWikiTestCase;
use ReflectionClass;
use Campaigns\TypesafeEnum;
use Campaigns\Persistence\IField;
use Campaigns\Persistence\Internal\FieldOption;
use Campaigns\Persistence\Internal\FieldDatatype;
use Campaigns\Persistence\Internal\Db\DBMapper;

class DBMapperTest extends MediaWikiTestCase {

	protected $testNS = 'Campaigns\PHPUnit\Persistence\Internal\Db';
	protected $testTable = 'test_table';
	protected $testColPrefix = 'prefix';

	public function testGetTableNameForObj() {
		$m = $this->getConfiguredMapper();
		$e = new TestEntity();
		$this->assertEquals( $this->testTable, $m->getTableNameForObj( $e ) );
	}

	public function testGetTableNameForTypeWithNamespace() {
		$m = $this->getConfiguredMapper();
		$this->assertEquals( $this->testTable,
			$m->getTableNameForType( $this->testNS . '\ITestEntity' ) );
	}

	public function testGetTableNameForTypeWithoutNamespace() {
		$m = $this->getConfiguredMapper();
		$this->assertEquals( $this->testTable,
			$m->getTableNameForType( 'ITestEntity' ) );
	}

	public function testGetIdColForObj() {
		$m = $this->getConfiguredMapper();
		$e = new TestEntity();
		$this->assertEquals( $this->testColPrefix . '_id',
			$m->getIdColForObj( $e ) );
	}

	public function testIdFieldForTypeWithNamespace() {
		$m = $this->getConfiguredMapper();
		$this->assertEquals( TestEntityField::$ID,
			$m->getIdFieldForType( $this->testNS . '\ITestEntity' ) );
	}

	public function testIdFieldForTypeWithoutNamespace() {
		$m = $this->getConfiguredMapper();
		$this->assertEquals( TestEntityField::$ID,
			$m->getIdFieldForType( 'ITestEntity' ) );
	}

	public function testGetTypeForObj() {
		$m = $this->getConfiguredMapper();
		$e = new TestEntity();
		$this->assertEquals( $this->testNS . '\ITestEntity',
			$m->getTypeForObj( $e ) );
	}

	public function testIsEntityReturnsTrueWhenExpected() {
		$m = $this->getConfiguredMapper();
		$e = new TestEntity();
		$this->assertTrue( $m->isEntity( $e ) );
	}

	public function testIsEntityReturnsFalseWhenExpected() {
		$m = $this->getConfiguredMapper();
		$ne = new TestNonEntity();
		$this->assertFalse( $m->isEntity( $ne ) );
	}

	public function
		testExceptionNotThownAsExpectedWhenVerifyRequiredFieldsCalled() {

		$m = $this->getConfiguredMapper();

		// Instantiate an entity and set the required fields
		$e = new TestEntity();
		$e->name = 'name';
		$e->time = wfTimestampNow();

		$m->verifyRequiredFields( $e );

		// If we've gotten here, the test was successful
		$this->assertTrue( true );
	}

	/**
	 * @expectedException Campaigns\Persistence\RequiredFieldNotSetException
	 * @expectedExceptionMessage Missing required field NAME
	 */
	public function
		testExceptionThownAsExpectedWhenVerifyRequiredFieldsCalled() {

		$m = $this->getConfiguredMapper();

		// Instantiate an entity but leave one required field unset
		$e = new TestEntity();
		$e->somethingElseId = 2;
		$e->time = wfTimestampNow();

		// This should throw the exception
		$m->verifyRequiredFields( $e );
	}

	public function testPrepareColsAndValsForDbWrite() {

		$m = $this->getConfiguredMapper();

		// Set up a mock DB to munge the timestamp in the test entity.
		// The logic we're testing with this is in the protected function
		// DBMapper::mungeValForDb().
		$now = wfTimestampNow();
		$nowMunged = $now . '_munged';

		$db = $this->getMockBuilder( 'DatabaseMysql' )
			->setMethods( array( 'timestamp' ) )
			->disableOriginalConstructor()
			->getMock();

		$db->expects( $this->once() )
			->method( 'timestamp' )
			->with( $this->equalTo( $now ) )
			->will( $this->returnValue( $nowMunged ) );

		// Instantiate an entity and set some fields
		$e = new TestEntity();
		$e->id = 5;
		$e->name = 'name';
		$e->time = $now;
		$e->condition = true;

		$expectedColsAndVals = array(
			$this->testColPrefix . '_id' => 5,
			$this->testColPrefix . '_name' => 'name',
			$this->testColPrefix . '_time' => $nowMunged,
			$this->testColPrefix . '_something_else_id' => null,
			$this->testColPrefix . '_condition' => true
		);

		$receivedColsAndVals =
			$m->prepareColsAndValsForDbWrite( $e, $db );

		$this->assertSame( $expectedColsAndVals, $receivedColsAndVals );
	}

	public function testGetDbColumn() {

		$m = $this->getConfiguredMapper();

		$this->assertEquals( $this->testColPrefix . '_id',
			$m->getDbColumn( TestEntityField::$ID ) );

		$this->assertEquals( $this->testColPrefix . '_name',
			$m->getDbColumn( TestEntityField::$NAME ) );

		$this->assertEquals( $this->testColPrefix . '_time',
			$m->getDbColumn( TestEntityField::$TIME ) );

		$this->assertEquals( $this->testColPrefix . '_something_else_id',
			$m->getDbColumn( TestEntityField::$SOMETHING_ELSE_ID ) );

		$this->assertEquals( $this->testColPrefix . '_condition',
			$m->getDbColumn( TestEntityField::$CONDITION ) );
	}

	public function testFieldHasOptionReturnsTrueWhenExpected() {

		$m = $this->getConfiguredMapper();

		// Go through all the cases that should return true
		$this->assertTrue( $m->fieldHasOption(
			TestEntityField::$ID, FieldOption::$ID ) );

		$this->assertTrue( $m->fieldHasOption(
			TestEntityField::$NAME, FieldOption::$UNIQUE ) );

		$this->assertTrue( $m->fieldHasOption(
				TestEntityField::$NAME, FieldOption::$REQUIRED ) );

		$this->assertTrue( $m->fieldHasOption(
				TestEntityField::$TIME, FieldOption::$UNIQUE ) );

		$this->assertTrue( $m->fieldHasOption(
				TestEntityField::$TIME, FieldOption::$REQUIRED ) );

		$this->assertTrue( $m->fieldHasOption(
				TestEntityField::$SOMETHING_ELSE_ID, FieldOption::$UNIQUE ) );
	}

	public function testFieldHasOptionReturnsFalseWhenExpected() {

		$m = $this->getConfiguredMapper();

		// Go through all the cases that should return false
		$this->assertFalse( $m->fieldHasOption(
			TestEntityField::$ID, FieldOption::$UNIQUE ) );

		$this->assertFalse( $m->fieldHasOption(
			TestEntityField::$ID, FieldOption::$REQUIRED ) );

		$this->assertFalse( $m->fieldHasOption(
			TestEntityField::$NAME, FieldOption::$ID ) );

		$this->assertFalse( $m->fieldHasOption(
			TestEntityField::$TIME, FieldOption::$ID ) );

		$this->assertFalse( $m->fieldHasOption(
			TestEntityField::$SOMETHING_ELSE_ID, FieldOption::$ID ) );

		$this->assertFalse( $m->fieldHasOption(
			TestEntityField::$SOMETHING_ELSE_ID, FieldOption::$REQUIRED ) );

		$this->assertFalse( $m->fieldHasOption(
			TestEntityField::$CONDITION, FieldOption::$ID ) );

		$this->assertFalse( $m->fieldHasOption(
			TestEntityField::$CONDITION, FieldOption::$UNIQUE ) );

		$this->assertFalse( $m->fieldHasOption(
			TestEntityField::$CONDITION, FieldOption::$REQUIRED ) );
	}

	public function testGetFieldsWithOption() {

		$m = $this->getConfiguredMapper();
		$e = new TestEntity();

		$this->assertSame(
			array( TestEntityField::$ID ),
			$m->getFieldsWithOption( $e, FieldOption::$ID ) );

		$this->assertSame(
			array( TestEntityField::$NAME, TestEntityField::$TIME,  ),
			$m->getFieldsWithOption( $e, FieldOption::$REQUIRED ) );

		$this->assertSame(
			array(
				TestEntityField::$NAME,
				TestEntityField::$TIME,
				TestEntityField::$SOMETHING_ELSE_ID
			),
			$m->getFieldsWithOption( $e, FieldOption::$UNIQUE ) );
	}

	public function testGetUniqueIndexes() {

		$m = $this->getConfiguredMapper();
		$e = new TestEntity();

		$expectedUniqueIndexes = array(

			'NAME' => array( TestEntityField::$NAME ),

			'time_and_something_else_id' => array(
				TestEntityField::$TIME, TestEntityField::$SOMETHING_ELSE_ID )
		);

		$this->assertSame( $expectedUniqueIndexes, $m->getUniqueIndexes( $e ) );
	}

	public function testFieldDatatypeConfiguration() {

		$m = $this->getConfiguredMapper();

		// The mapper has no public methods to test this directly. If it
		// weren't working, other tests here would almost certainly fail. But
		// let's use reflection to check it directly, anyway.

		$reflClass = new ReflectionClass(
			'Campaigns\Persistence\Internal\Db\DBMapper' );

		$prop = $reflClass->getProperty( 'typeInfosByShortTypeName' );
		$prop->setAccessible( true );
		$typeInfosByShortName = $prop->getValue( $m );
		$typeInfo = $typeInfosByShortName['ITestEntity'];
		$fieldInfosByName = $typeInfo->fieldInfosByName;

		// Get the field infos one by one and test the datatype

		$fi = $fieldInfosByName['ID'];
		$this->assertEquals( FieldDatatype::$INT, $fi->datatype );

		$fi = $fieldInfosByName['NAME'];
		$this->assertEquals( FieldDatatype::$STRING, $fi->datatype );

		$fi = $fieldInfosByName['TIME'];
		$this->assertEquals( FieldDatatype::$MW_TS, $fi->datatype );

		$fi = $fieldInfosByName['SOMETHING_ELSE_ID'];
		$this->assertEquals( FieldDatatype::$INT, $fi->datatype );

		$fi = $fieldInfosByName['CONDITION'];
		$this->assertEquals( FieldDatatype::$BOOLEAN, $fi->datatype );
	}

	public function testGetFieldValue() {

		$m = $this->getConfiguredMapper();

		// Instantiate an entity and set fields
		$e = new TestEntity();
		$e->id = 5;
		$e->name = 'name';
		$e->time = wfTimestampNow();
		$e->somethingElseId = 2;

		$this->assertEquals( 5, $m->getFieldValue( $e, TestEntityField::$ID ) );
	}

	public function testGetId() {

		$m = $this->getConfiguredMapper();
		$e = new TestEntity();
		$e->id = 5;
		$this->assertEquals( 5, $m->getId( $e ) );
	}

	public function testSetIdOfNewObj() {

		$m = $this->getConfiguredMapper();
		$e = new TestEntity();
		$m->setIdOfNewObj( $e, 5 );
		$this->assertEquals( 5, $e->id );
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage Attempted to set the ID of a Campaigns\PHPUnit\Persistence\Internal\Db\TestEntity that already has an id. Current ID: 3 Attempted to set as: 5
	 */
	public function testExceptionThrownWhenSetIdOfNewObjCalledOnObjectWithId() {

		$m = $this->getConfiguredMapper();
		$e = new TestEntity();
		$e->id = 3;
		$m->setIdOfNewObj( $e, 5 );
	}

	public function testMakeObjectFromDbRow() {

		$m = $this->getConfiguredMapper();

		// Set up a stdClass object like those returned by ResultWrapper

		// This will also test the protected function
		// DBMapper::mungeValFromDb(), which will turn strings to integers for
		// the int datatype, and will turn integers to booleans for the boolean
		// datatype. This is tested via the values set and the assertions
		// performed for the last two fields.

		$now = wfTimestampNow();

		$simulatedDbRow = (object) array(
			$this->testColPrefix . '_id' => 2,
			$this->testColPrefix . '_name' => 'name',
			$this->testColPrefix . '_time' => $now,
			$this->testColPrefix . '_something_else_id' => '5',
			$this->testColPrefix . '_condition' => 1,
		);

		$e = $m->makeObjectFromDbRow( 'ITestEntity', $simulatedDbRow );

		$this->assertSame( 2, $e->id );
		$this->assertSame( 'name', $e->name );
		$this->assertSame( $now, $e->time );
		$this->assertSame( 5, $e->somethingElseId );
		$this->assertSame( true, $e->condition );
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage Campaigns\PHPUnit\Persistence\Internal\Db\TestEntity is not a subclass of Campaigns\PHPUnit\Persistence\Internal\Db\ITestNonEntity
	 */
	public function testExceptionThrownWhenRealizationIsNotAnInstanceOfType() {

		// Set up a different config in wgCampaignsDBPersistence to test this
		$this->setMWGlobals( 'wgCampaignsDBPersistence', array (

			$this->testNS . '\ITestNonEntity' => array(

				'realization'   => $this->testNS . '\TestEntity',
				'table'         => $this->testTable,
				'column_prefix' => $this->testColPrefix,
				'field_class'   => $this->testNS . '\TestEntityField'
			)
		) );

		$mapper = new DBMapper();
		$mapper->ensureConfigured();
	}

	public function testExceptionNotThrownWhenRealizationClassAndTypeAreTheSame() {

		// Set up a different config in wgCampaignsDBPersistence to test this
		$this->setMWGlobals( 'wgCampaignsDBPersistence', array (

			$this->testNS . '\TestEntity' => array(

				'realization'   => $this->testNS . '\TestEntity',
				'table'         => $this->testTable,
				'column_prefix' => $this->testColPrefix,
				'field_class'   => $this->testNS . '\TestEntityField'
			)
		) );

		$mapper = new DBMapper();
		$mapper->ensureConfigured();

		// If we've gotten here, the test was successful
		$this->assertTrue( true );
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage Can't register two types with the same name: ITestEntity
	 */
	public function testExceptionThrownWhenTwoTypesOfSameNameRegistered() {

		$this->setUpGlobals();

		// Add another config in wgCampaignsDBPersistence to test this
		$GLOBALS['wgCampaignsDBPersistence']
			[$this->testNS . '\AnotherTestNS\ITestEntity']
			= array(

				'realization'   => $this->testNS . '\AnotherTestNS\TestEntity',
				'table'         => 'another_' . $this->testTable,
				'column_prefix' => $this->testColPrefix,
				'field_class'   => $this->testNS . '\AnotherTestNS\TestEntityField'
		);

		$mapper = new DBMapper();
		$mapper->ensureConfigured();
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage Exactly one ID field must be declared for Campaigns\PHPUnit\Persistence\Internal\Db\IIdlessTestEntity
	 */
	public function testExceptionThrownWhenEntityHasNoIdField() {

		// Set up a different config in wgCampaignsDBPersistence to test this
		$this->setMWGlobals( 'wgCampaignsDBPersistence', array (

			$this->testNS . '\IIdlessTestEntity' => array(

				'realization'   => $this->testNS . '\IdlessTestEntity',
				'table'         => $this->testTable,
				'column_prefix' => $this->testColPrefix,
				'field_class'   => $this->testNS . '\IdlessTestEntityField'
			)
		) );

		$mapper = new DBMapper();
		$mapper->ensureConfigured();
	}

	protected function getConfiguredMapper() {
		$this->setUpGlobals();
		$mapper = new DBMapper();
		$mapper->ensureConfigured();
		return $mapper;
	}

	protected function setUpGlobals() {

		$this->setMWGlobals( 'wgCampaignsDBPersistence', array (

			$this->testNS . '\ITestEntity' => array(

				'realization'   => $this->testNS . '\TestEntity',
				'table'         => $this->testTable,
				'column_prefix' => $this->testColPrefix,
				'field_class'   => $this->testNS . '\TestEntityField'
			)
		) );
	}
}

// The following interface, realization class and field class are used in most
// test methods here.

interface ITestEntity { }

class TestEntity implements ITestEntity {

	/**
	 * @var int
	 * @id
	 */
	var $id;

	/**
	 * @var string
	 * @unique
	 * @required
	 */
	var $name;

	/**
	 * @var MW_TS
	 * @required
	 * @unique time_and_something_else_id
	 */
	var $time;

	/**
	 * @var int
	 * @unique time_and_something_else_id
	 */
	var $somethingElseId;

	/**
	 * @var boolean
	 */
	var $condition;
}

class TestEntityField extends TypesafeEnum implements IField {

	static $ID;
	static $NAME;
	static $TIME;
	static $SOMETHING_ELSE_ID;
	static $CONDITION;
}

TestEntityField::setUp();

// For testing isEntity
class TestNonEntity { }

// For testing that an exception is thrown when a realization class isn't a
// subtype of or the same as its type.
interface ITestNonEntity { }

// The following interface and classes are for testing that an exception is
// thrown if an entity has no ID field.

interface IIdlessTestEntity { }

class IdlessTestEntity implements IIdlessTestEntity {

	/**
	 * @var string
	 */
	var $someField;
}

class IdlessTestEntityField extends TypesafeEnum implements IField {

	static $SOME_FIELD;
}

IdlessTestEntityField::setUp();

// The following namespace and its contents are for testing that an error is
// thrown when two types of the same name (though in different namespaces) are
// registered.

namespace Campaigns\PHPUnit\Persistence\Internal\Db\AnotherTestNS;

use Campaigns\TypesafeEnum;
use Campaigns\Persistence\IField;

interface ITestEntity { }

class TestEntity implements ITestEntity {

	// Note: we have to set this to avoid throwing an error about no ID field
	/**
	 * @var int
	 * @id
	 */
	var $id;
}

class TestEntityField extends TypesafeEnum implements IField {
	static $ID;
}

TestEntityField::setUp();