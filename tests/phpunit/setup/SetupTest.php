<?php

namespace Campaigns\PHPUnit\Setup;

use \Campaigns\Setup\Setup;
use \ReflectionClass;

/**
 * @group Campaigns
 */
class SetupTest extends \MediaWikiTestCase {

	protected function setUp() {

		parent::setUp();

		$this->clearOutGlobalWgCampaignsDI();
	}

	/**
	 * Returns a Setup instance with some registrations used in several tests
	 *
	 * @return Setup
	 */
	protected function getSetupWithRegisrations() {

		$setup = new Setup();

		$setup->register(
			'Campaigns\PHPUnit\Setup\IClassWithNoConstructor',
			'Campaigns\PHPUnit\Setup\ClassWithNoConstructor',
			'singleton'
		);

		$setup->register(
			'Campaigns\PHPUnit\Setup\ClassWhereTypeAndRealizationAreTheSame',
			'Campaigns\PHPUnit\Setup\ClassWhereTypeAndRealizationAreTheSame',
			'singleton'
		);

		// Default scope is singleton
		$setup->register(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam',
			'Campaigns\PHPUnit\Setup\ClassWithAConstructorParam'
		);

		return $setup;
	}

	public function
		testGetProvidesObjectOfCorrectClassForClassWithNoConstructor() {

		$setup = $this->getSetupWithRegisrations();

		$obj = $setup->get( 'Campaigns\PHPUnit\Setup\IClassWithNoConstructor' );

		$implClassName = 'Campaigns\PHPUnit\Setup\ClassWithNoConstructor';
		$this->assertInstanceOf( $implClassName, $obj );
	}

	public function
		testGetProvidesObjectOfCorrectClassForClassWithAConstructorParam() {

		$setup = $this->getSetupWithRegisrations();

		$obj = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam' );

		$implClassName = 'Campaigns\PHPUnit\Setup\ClassWithAConstructorParam';
		$this->assertInstanceOf( $implClassName, $obj );
	}

	public function
		testGetProvidesObjectOfCorrectClassWhenTypeAndRealizationAreTheSame() {

		$setup = $this->getSetupWithRegisrations();

		$obj = $setup->get(
			'Campaigns\PHPUnit\Setup\ClassWhereTypeAndRealizationAreTheSame' );

		$implClassName =
			'Campaigns\PHPUnit\Setup\ClassWhereTypeAndRealizationAreTheSame';

		$this->assertInstanceOf( $implClassName, $obj );
	}

	public function
		testObjectOfClassWithAConstructorParamReceivesObjectOfCorrectClassInConstructor() {

		$setup = $this->getSetupWithRegisrations();

		$obj1 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam' );

		$obj2 = $obj1->getValueSentInConstructor();

		$obj2ImplClassName = 'Campaigns\PHPUnit\Setup\ClassWithNoConstructor';
		$this->assertInstanceOf( $obj2ImplClassName, $obj2 );
	}

	public function testInSingletonScopeGetAlwaysProvidesTheSameInstance() {

		$setup = $this->getSetupWithRegisrations();

		// IClassWithNoConstructor is explicitly in the singleton scope
		$obj1 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithNoConstructor' );

		$obj2 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithNoConstructor' );

		$this->assertSame( $obj1, $obj2 );
	}

	public function testSingletonIsDefaultScope() {

		$setup = $this->getSetupWithRegisrations();

		// IClassWithAConstructorParam is in the default scope.
		// Above we verified that for types in the singleton scope, the same
		// instance is always provided. Here we perform the same test to see
		// if IClassWithAConstructorParam is in that scope.
		$obj1 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam' );

		$obj2 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam' );

		$this->assertSame( $obj1, $obj2 );
	}

	/**
	 * @expectedException \MWException
	 * @expectedExceptionMessage No concrete class registered for
	 */
	public function testExceptionThrownWhenObjectOfUnregisteredTypeRequested() {

		$setup = $this->getSetupWithRegisrations();
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
	 * @expectedException \MWException
	 * @expectedExceptionMessage is not a subclass of
	 */
	public function
		testExceptionThrownWhenImplementationClassThatIsntASubclassOfOrTheSameAsTypeIsRegistered() {

		$setup = new Setup();

		$setup->register(
			'Campaigns\PHPUnit\Setup\ISomeOtherType',
			'Campaigns\PHPUnit\Setup\ClassWithNoConstructor',
			'singleton'
		);
	}

	/**
	 * @expectedException \MWException
	 * @expectedExceptionMessage already registered
	 */
	public function testExceptionThrownWhenSameTypeIsRegisteredTwice() {

		$setup = $this->getSetupWithRegisrations();

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
	 * @expectedException \MWException
	 * @expectedExceptionMessage with no type hint
	 */
	public function
		testExceptionThrownWhenClassWithConstructorParamWithNoTypeHintRegistered() {

		$setup = $this->getSetupWithRegisrations();

		$setup->register(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParamWithNoTypeHint',
			'Campaigns\PHPUnit\Setup\ClassWithAConstructorParamWithNoTypeHint',
			'singleton'
		);
	}

	public function testTypesCanBeRegisteredFromWgcampaignsdi() {

		// Set a registration in global $wgCampaignsDI
		$this->setRegistrationInGlobalWgCampaignsDI();

		$setup = new Setup();

		// Register from global
		$setup->registerTypesFromWGCampaignsDI();

		// Request the first type registered
		$obj1 = $setup->get( 'Campaigns\PHPUnit\Setup\IClassWithNoConstructor' );

		// Test that we get an instance of the correct class
		$implClassName = 'Campaigns\PHPUnit\Setup\ClassWithNoConstructor';
		$this->assertInstanceOf( $implClassName, $obj1 );

		// Request the second type registered
		$obj2 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam' );

		// Test that we get an instance of the correct class
		$implClassName = 'Campaigns\PHPUnit\Setup\ClassWithAConstructorParam';
		$this->assertInstanceOf( $implClassName, $obj2 );

		// Clean up
		$this->clearOutGlobalWgCampaignsDI();
	}

	public function testStaticGetInstanceReturnsTheSameInstance() {

		$instance1 = Setup::getInstance();
		$instance2 = Setup::getInstance();
		$this->assertSame( $instance1, $instance2 );
	}

	public function testStaticGetInstanceRegistersTypesFromWgcampaignsdi() {

		// Clear out static instance property
		TestSetup::clearInstance();

		// Set a registration in global $wgCampaignsDI
		$this->setRegistrationInGlobalWgCampaignsDI();

		// Get our instance via static getInstance()
		$setup = Setup::getInstance();

		// Request the first type registered
		$obj1 = $setup->get( 'Campaigns\PHPUnit\Setup\IClassWithNoConstructor' );

		// Test that we get an instance of the correct class
		$implClassName = 'Campaigns\PHPUnit\Setup\ClassWithNoConstructor';
		$this->assertInstanceOf( $implClassName, $obj1 );

		// Request the second type registered
		$obj2 = $setup->get(
			'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam' );

		// Test that we get an instance of the correct class
		$implClassName = 'Campaigns\PHPUnit\Setup\ClassWithAConstructorParam';
		$this->assertInstanceOf( $implClassName, $obj2 );

		// Clean up
		$this->clearOutGlobalWgCampaignsDI();
	}

	/**
	 * Sets up the global variable $wgCampaignsDI with a type registration
	 */
	private function setRegistrationInGlobalWgCampaignsDI() {
		$this->setMwGlobals( 'wgCampaignsDI',
			array(
				'Campaigns\PHPUnit\Setup\IClassWithNoConstructor' => array(

					'realization' =>
						'Campaigns\PHPUnit\Setup\ClassWithNoConstructor',

					'scope' => 'singleton'
				),

				// Testing that we can omit the scope here, default is singleton
				'Campaigns\PHPUnit\Setup\IClassWithAConstructorParam' => array(

					'realization' =>
						'Campaigns\PHPUnit\Setup\ClassWithAConstructorParam'
				)
			)
		);
	}

	/**
	 * Clear out any registrations in global $wgCampaignsDI
	 */
	private function clearOutGlobalWgCampaignsDI() {
		$this->setMwGlobals( 'wgCampaignsDI', array() );
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

class ClassWhereTypeAndRealizationAreTheSame { }

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

/**
 * Subclass with method for clearing out protected static property
 */
class TestSetup extends Setup {

	public static function clearInstance() {
		parent::$instance = null;
	}
}