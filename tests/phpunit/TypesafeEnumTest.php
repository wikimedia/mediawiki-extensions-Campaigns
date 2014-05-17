<?php

namespace Campaigns\PHPUnit;

use Campaigns\TypesafeEnum;

class TypesafeEnumTest extends \MediaWikiTestCase {

	public static function setUpBeforeClass() {
		TestEnum::setUp();
		AnotherTestEnum::setUp();
	}

	public function testEnumIsCorrectClass() {

		$this->assertInstanceOf(
			'Campaigns\PHPUnit\TestEnum', TestEnum::$ENUM_ONE );
	}

	public function testDifferentEnumsAreNotEqual() {

		$this->assertNotEquals( TestEnum::$ENUM_ONE, TestEnum::$ENUM_TWO );
		$this->assertNotEquals( TestEnum::$ENUM_TWO, TestEnum::$ENUM_THREE );
		$this->assertNotEquals( TestEnum::$ENUM_THREE, TestEnum::$ENUM_ONE );
	}

	public function testEnumsOfDifferentClassesAreNotEqual() {

		$this->assertNotEquals( TestEnum::$ENUM_ONE,
			AnotherTestEnum::$ENUM_ONE );

		$this->assertNotEquals( TestEnum::$ENUM_TWO,
			AnotherTestEnum::$ENUM_TWO );

		$this->assertNotEquals( TestEnum::$ENUM_THREE,
			AnotherTestEnum::$ENUM_THREE );
	}

	public function testSwitchOnEnum() {

		$enums = array(
			TestEnum::$ENUM_ONE,
			TestEnum::$ENUM_TWO,
			TestEnum::$ENUM_THREE
		);

		foreach ( $enums as $e ) {

			switch ( $e ) {

				case TestEnum::$ENUM_ONE:
					$this->assertSame( TestEnum::$ENUM_ONE, $e );
					break;

				case TestEnum::$ENUM_TWO:
					$this->assertSame( TestEnum::$ENUM_TWO, $e );
					break;

				case TestEnum::$ENUM_THREE:
					$this->assertSame( TestEnum::$ENUM_THREE, $e );
					break;

				default:
					$this->fail( 'Enum ' . $e . ' didn\'t follow the right path'
						. ' in switch.');
			}
		}
	}

	public function testInArrayWithEnums() {

		$enums = array(
			TestEnum::$ENUM_ONE,
			TestEnum::$ENUM_TWO
		);

		$this->assertTrue( in_array( TestEnum::$ENUM_ONE, $enums ) );
		$this->assertTrue( in_array( TestEnum::$ENUM_TWO, $enums ) );
		$this->assertFalse( in_array( TestEnum::$ENUM_THREE, $enums ) );
	}

	public function testArraySearchWithEnums() {

		$enums = array(
			TestEnum::$ENUM_ONE,
			TestEnum::$ENUM_TWO
		);

		$this->assertEquals( 0, array_search( TestEnum::$ENUM_ONE, $enums ) );
		$this->assertEquals( 1, array_search( TestEnum::$ENUM_TWO, $enums ) );
		$this->assertFalse( array_search( TestEnum::$ENUM_THREE, $enums ) );
	}

	public function testToStringReturnsEnumName() {

		$this->assertEquals( 'ENUM_ONE', TestEnum::$ENUM_ONE->__toString() );
		$this->assertEquals( 'ENUM_TWO', TestEnum::$ENUM_TWO->__toString() );
		$this->assertEquals( 'ENUM_THREE', TestEnum::$ENUM_THREE->__toString() );
	}

	public function testGetNameReturnsEnumName() {

		$this->assertEquals( 'ENUM_ONE', TestEnum::$ENUM_ONE->getName() );
		$this->assertEquals( 'ENUM_TWO', TestEnum::$ENUM_TWO->getName() );
		$this->assertEquals( 'ENUM_THREE', TestEnum::$ENUM_THREE->getName() );
	}

	public function testGetFullyQualifiedNameReturnsFullyQualifiedName() {

		$this->assertEquals( 'Campaigns\PHPUnit\TestEnum::$ENUM_ONE',
			TestEnum::$ENUM_ONE->getFullyQualifiedName() );

		$this->assertEquals( 'Campaigns\PHPUnit\TestEnum::$ENUM_TWO',
			TestEnum::$ENUM_TWO->getFullyQualifiedName() );

		$this->assertEquals( 'Campaigns\PHPUnit\TestEnum::$ENUM_THREE',
			TestEnum::$ENUM_THREE->getFullyQualifiedName() );
	}

	public function testGetValuesReturnsArrayOfValues() {

		$expectedValues = array(
			TestEnum::$ENUM_ONE,
			TestEnum::$ENUM_TWO,
			TestEnum::$ENUM_THREE
		);

		$providedValues = TestEnum::getValues();

		$this->assertTrue( array_diff( $expectedValues, $providedValues ) ===
			array_diff( $providedValues, $expectedValues ) );
	}

	public function testIsDeclaredReturnsTrueWhenExpectedForNonString() {

		$this->assertTrue( TestEnum::isDeclared( TestEnum::$ENUM_ONE ) );
		$this->assertTrue( TestEnum::isDeclared( TestEnum::$ENUM_TWO ) );
		$this->assertTrue( TestEnum::isDeclared( TestEnum::$ENUM_THREE ) );
	}

	public function testIsDeclaredReturnsFalseWhenExpectedForNonString() {

		$this->assertFalse( TestEnum::isDeclared( AnotherTestEnum::$ENUM_ONE ) );
	}

	public function testIsDeclaredReturnsTrueWhenExpectedForString() {

		$this->assertTrue( TestEnum::isDeclared( 'ENUM_ONE' ) );
		$this->assertTrue( TestEnum::isDeclared( 'ENUM_TWO' ) );
		$this->assertTrue( TestEnum::isDeclared( 'ENUM_THREE' ) );
	}

	public function testIsDeclaredReturnsFalseWhenExpectedForString() {

		$this->assertFalse( TestEnum::isDeclared( 'UNKNOWN_ENUM' ) );

		// Be sure this doesn't create a false positive, since it's declared
		// as a static property, but is protected.
		$this->assertFalse( TestEnum::isDeclared( 'values' ) );
	}

	public function testGetValueByNameReturnsCorrectValue() {

		$this->assertSame( TestEnum::$ENUM_ONE,
			TestEnum::getValueByName( 'ENUM_ONE' ) );

		$this->assertSame( TestEnum::$ENUM_TWO,
			TestEnum::getValueByName( 'ENUM_TWO' ) );

		$this->assertSame( TestEnum::$ENUM_THREE,
			TestEnum::getValueByName( 'ENUM_THREE' ) );
	}

	public function testGetValueByNameReturnsNullWhenExpected() {

		$this->assertNull( TestEnum::getValueByName( 'UNKNOWN_ENUM' ) );

		// Be sure this doesn't create a false positive, since it's declared
		// as a static property, but is protected.
		$this->assertNull( TestEnum::getValueByName( 'values' ) );
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Enum setup called more than once or enum values already set
	 */
	public function testExceptionThrownWhenSetupCalledMoreThanOnce() {
		TestEnum::setUp();
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Enum setup called more than once or enum values already set
	 */
	public function testExceptionThrownWhenSetupCalledAndValuesAreAlreadySet() {
		YetAnotherTestEnum::setUp();
	}
}

/**
 * Test enum. Since we're testing static "methods", there is no need to
 * instantiate and no provider.
 */
final class TestEnum extends TypesafeEnum {

	static $ENUM_ONE;
	static $ENUM_TWO;
	static $ENUM_THREE;
}

/**
 * Another test enum
 */
final class AnotherTestEnum extends TypesafeEnum {

	static $ENUM_ONE;
	static $ENUM_TWO;
	static $ENUM_THREE;
}

/**
 * Yet another test enum, to test that an exception is thrown if enum values
 * are set manually.
 */
final class YetAnotherTestEnum extends TypesafeEnum {

	static $ENUM_ONE = 0;    // **Wrong** Don't do this
	static $ENUM_TWO = 1;    // **Wrong** Don't do this
	static $ENUM_THREE = 2;  // **Wrong** Don't do this
}