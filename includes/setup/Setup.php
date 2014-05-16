<?php

namespace Campaigns\Setup;

use \MWException;
use \ReflectionClass;

/**
 * Helper for managing general setup.
 *
 * This class provides minimal, lightweight dependency injection.
 *
 * You can register a type and the concrete class you wish to instantiate for
 * that type. When you call get() with the name of the type, you'll get an
 * instance of the concrete class you registered.
 *
 * If the class depends on other dependency-injected classes, specify these as
 * type-hinted constructor parameters. Setup::get() will instantiate the
 * concrete objects and pass them to your constructor.
 *
 * Only the 'singleton' scope is available. For types in this scope, only one
 * instance will be created.
 *
 * More features may be added later on if needed.
 *
 * TODO: If an external DI library is approved for use on production WMF sites,
 * it should be used here instead.
 */
class Setup {

	protected static $instance = null;

	private $registrations = array();
	private $singletons = array();

	/**
	 * Get a global Setup instance
	 *
	 * @return Setup
	 */
	public static function getInstance() {

		// If the instance is null...
		if ( is_null( static::$instance ) ) {

			// Create a new one...
			static::$instance = new static();

			// ...and set it up using values from $wgCampaignsDI.
			// (This allows putting setup in Camapaigns.php.)
			static::$instance->registerTypesFromWGCampaignsDI();
		}

		return static::$instance;
	}

	/**
	 * Performs registrations indicated in the global variable
	 * $wgCampaignsDI
	 */
	public function registerTypesFromWGCampaignsDI() {

		foreach ( $GLOBALS['wgCampaignsDI'] as $key => $val ) {

			// Scope is optional but this isn't the place to set its
			// default
			$scope = isset( $val['scope'] ) ? $val['scope'] : null;

			$this->register(
				$key,
				$val['realization'],
				$scope
			);
		}
	}

	/**
	 * Register a type, a realization class, and a scope
	 * (for now, must be 'singleton').
	 *
	 * @param string $typeName
	 * @param string $realizationClassName
	 * @param string $scope For now, must be 'singleton'
	 * @throws MWException
	 */
	public function register( $typeName, $realizationClassName, $scope=null ) {

		// Check this type hasn't already been registered
		if ( isset( $this->registrations[$typeName] ) ) {
			throw new MWException( 'Attempted to register type ' . $typeName .
				', but it\'s already registered.' );
		}

		// Default scope is singleton
		if ( is_null( $scope ) ) {
			$scope = 'singleton';
		}

		// Check that the registration isn't for an unsupported scope
		if ( $scope !== 'singleton' ) {
			throw new MWException( 'Attempted to register type ' . $typeName .
				' for a scope other than singleton.' );
		}

		$reflClass = new ReflectionClass( $realizationClassName );

		// Check that the realization class is a subclass of or the same as type
		if ( !$reflClass->isSubClassOf( $typeName ) &&
				$typeName !== $realizationClassName ) {

			throw new MWException( $realizationClassName .
				' is not a subclass of or the same class as ' . $typeName );
		}

		// If the realization class has a constructor, check that all the
		// constructor params are type-hinted
		if ( $reflClass->hasMethod( '__construct' ) ) {
			$constructor = $reflClass->getMethod( '__construct' );
			$reflParams = $constructor->getParameters();

			// Cycle through constructor params
			foreach ( $reflParams as $reflParam ) {

				// Raise an exception if there's no type hint
				if ( is_null( $reflParam->getClass() ) ) {

					throw new MWException( 'Attempted to register class ' .
						$realizationClassName . ' with no type hint for the ' .
						'constructor parameter ' . $reflParam->getName() );
				}
			}
		}

		// All OK, let's register
		$this->registrations[$typeName] =
			new Registration( $typeName, $realizationClassName, $scope );
	}

	/**
	 * Get an object of the class registered for the type $typeName.
	 *
	 * @param string $typeName
	 * @return mixed An object of the class registered for $typeName
	 */
	public function get( $typeName ) {

		// Check that we have a registration for this type
		if ( !isset( $this->registrations[$typeName] ) ) {
			throw new MWException( 'No concrete class registered for ' .
				$typeName . '.' );
		}

		$registration = $this->registrations[$typeName];

		// Only 'singleton' scope is supported so far; this is where we could
		// implement other scopes, though
		switch ( $registration->scope ) {
			case 'singleton':

				// If an instance has already been created, return that
				if ( isset( $this->singletons[$typeName] ) ) {
					return $this->singletons[$typeName];
				}

				// No previously created instance? Create one, store it, then
				// return it
				$obj = $this->instantiate( $registration );
				$this->singletons[$typeName] = $obj;
				return $obj;

			default:
				throw new MWException( 'Unavailable scope set for ' .
					$typeName . '.' );
		}
	}

	/**
	 * Instantiate a new object as per $registration.
	 *
	 * @param Registration $registration
	 * @return mixed An object of the class defined in $registration
	 */
	private function instantiate( Registration $registration ) {

		$reflClass = new ReflectionClass( $registration->realizationClassName );

		// If there's no constructor, we can just instantiate and leave
		if ( !$reflClass->hasMethod( '__construct' ) ) {
			return $reflClass->newInstance();
		}

		// Get the parameters declared on the constructor
		$constructor = $reflClass->getMethod( '__construct' );
		$reflParams = $constructor->getParameters();

		// Create an array of the parameters to pass in to the constructor by
		// checking the type hints for each one, and getting the corresponding
		// instances
		$params = array();

		foreach ( $reflParams as $reflParam ) {
			$params[] = $this->get( $reflParam->getClass()->getName() );
		}

		// Instantiate
		return $reflClass->newInstanceArgs( $params );
	}
}

/**
 * Wrapper for info about a registration; not used elsewhere.
 */
class Registration {
	public $typeName;
	public $realizationClassName;
	public $scope;

	public function __construct( $typeName, $realizationClassName, $scope ) {
		$this->typeName = $typeName;
		$this->realizationClassName = $realizationClassName;
		$this->scope = $scope;
	}
}