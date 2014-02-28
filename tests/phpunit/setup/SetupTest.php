<?php

namespace Campaigns\PHPUnit\Setup;

use \Campaigns\Setup\Setup;

class SetupTest extends \MediaWikiTestCase {

	/**
	 * Provides a Setup instance for test
	 *
	 * @return Setup
	 */
	public function setupProvider() {

		$setup = new Setup();

		$setup->register(
			'Campaigns\PHPUnit\Setup\IClassWithNoConstructor',
			'Campaigns\PHPUnit\Setup\ClassWithNoConstructor',
			'singleton'
		);

		$setup->register(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam',
			'Campaigns\PHPUnit\Setup\ClassWithAConstructorParam',
			'singleton'
		);

		return array( array( $setup ) );
	}

	/**
	 * @dataProvider setupProvider
	 */
	public function
		testGetProvidesObjectOfCorrectClassForClassWithNoConstructor( Setup $setup ) {

		$obj = $setup->get( 'Campaigns\PHPUnit\Setup\IClassWithNoConstructor' );

		$implClassName = 'Campaigns\PHPUnit\Setup\ClassWithNoConstructor';
		$this->assertInstanceOf( $implClassName, $obj );
	}

	/**
	 * @dataProvider setupProvider
	 */
	public function
		testGetProvidesObjectOfCorrectClassForClassWithAConstructorParam(
		$setup ) {

		$obj = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam' );

		$implClassName = 'Campaigns\PHPUnit\Setup\ClassWithAConstructorParam';
		$this->assertInstanceOf( $implClassName, $obj );
	}

	/**
	 * @dataProvider setupProvider
	 */
	public function
		testObjectOfClassWithAConstructorParamReceivesObjectOfCorrectClassInConstructor(
		$setup ) {

		$obj1 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam' );

		$obj2 = $obj1->getValueSentInConstructor();

		$obj2ImplClassName = 'Campaigns\PHPUnit\Setup\ClassWithNoConstructor';
		$this->assertInstanceOf( $obj2ImplClassName, $obj2 );
	}

	/**
	 * @dataProvider setupProvider
	 */
	public function testInSingletonScopeGetAlwaysProvidesTheSameInstance(
		$setup ) {

		$obj1 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithNoConstructor' );

		$obj2 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithNoConstructor' );

		$this->assertTrue( $obj1 === $obj2 );
	}

	/**
	 * @dataProvider setupProvider
	 * @expectedException \MWException
	 * @expectedExceptionMessage No concrete class registered for
	 */
	public function testExceptionThrownWhenObjectOfUnregisteredTypeRequested(
		$setup ) {

		$setup->get( 'IUnregisteredType' );
	}

	/**
	 * @expectedException \MWException
	 * @expectedExceptionMessage No concrete class registered for
	 */
	public function
		testExceptionThrownWhenObjectOfClassWithConstructorParamOfUnregisteredTypeRequested() {

		$setup = new Setup();

		$setup->register(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParamOfUnregisteredType',
			'Campaigns\PHPUnit\Setup\ClassWithAConstructorParamOfUnregisteredType',
			'singleton'
		);

		$setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParamOfUnregisteredType' );
	}

	/**
	 * @dataProvider setupProvider
	 * @expectedException \MWException
	 * @expectedExceptionMessage is not a subclass of
	 */
	public function
		testExceptionThrownWhenImplementationClassThatIsntASubclassOfTypeIsRegistered(
		$setup ) {

		$setup->register(
			'Campaigns\PHPUnit\Setup\ISomeOtherType',
			'Campaigns\PHPUnit\Setup\ClassWithNoConstructor',
			'singleton'
		);
	}

	/**
	 * @dataProvider setupProvider
	 * @expectedException \MWException
	 * @expectedExceptionMessage already registered
	 */
	public function testExceptionThrownWhenSameTypeIsRegisteredTwice( $setup ) {
		$setup->register(
			'Campaigns\PHPUnit\Setup\IClassWithNoConstructor',
			'Campaigns\PHPUnit\Setup\ClassWithNoConstructor',
			'singleton'
		);
	}

	/**
	 * @expectedException \MWException
	 * @expectedExceptionMessage for a scope
	 */
	public function
		testExceptionThrownWhenRegistrationAttemptedWithUnsupportedScope() {

		$setup = new Setup();

		$setup->register(
			'Campaigns\PHPUnit\Setup\IClassWithNoConstructor',
			'Campaigns\PHPUnit\Setup\ClassWithNoConstructor',
			'unsupportedscope'
		);
	}

	/**
	 * @dataProvider setupProvider
	 * @expectedException \MWException
	 * @expectedExceptionMessage with no type hint
	 */
	public function
		testExceptionThrownWhenClassWithConstructorParamWithNoTypeHintRegistered(
		$setup ) {

		$setup->register(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParamWithNoTypeHint',
			'Campaigns\PHPUnit\Setup\ClassWithAConstructorParamWithNoTypeHint',
			'singleton'
		);
	}

	public function testStaticClearInstanceClearsGlobalInstance() {
		$instance1 = Setup::getInstance();
		Setup::clearInstance();
		$instance2 = Setup::getInstance();

		$this->assertFalse( $instance1 === $instance2 );
	}
}

interface IClassWithNoConstructor { }

class ClassWithNoConstructor implements IClassWithNoConstructor{ }

class AnotherClassWithNoConstructor implements IClassWithNoConstructor{ }

interface IClassWithAConstructorParam {

	public function getValueSentInConstructor();
}

class ClassWithAConstructorParam implements IClassWithAConstructorParam {

	var $value;

	public function __construct( IClassWithNoConstructor $value ) {
		$this->value = $value;
	}

	public function getValueSentInConstructor() {
		return $this->value;
	}
}

interface IUnregisteredType { }

interface IClassWithAConstructorParamOfUnregisteredType { }

class ClassWithAConstructorParamOfUnregisteredType
	implements IClassWithAConstructorParamOfUnregisteredType {

	public function __construct( IUnregisteredType $value ) { }
}

interface ISomeOtherType { }

interface IClassWithAConstructorParamWithNoTypeHint { }

class ClassWithAConstructorParamWithNoTypeHint
	implements IClassWithAConstructorParamWithNoTypeHint {

	public function __construct( $value ) { }
}