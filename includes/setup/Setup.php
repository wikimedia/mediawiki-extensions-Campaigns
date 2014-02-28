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
 * If the class to instantiate has parameters in its constructor, they should be
 * type-hinted, and concrete classes to instantiate should be registered for
 * those types.
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

		if ( is_null( static::$instance ) ){
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Clear the global Setup instance (useful for setting up tests)
	 */
	public static function clearInstance() {
		static::$instance = null;
	}

	/**
	 * Register a type, an implementation class, and a scope
	 * (for now, must be 'singleton').
	 *
	 * @param string $typeName
	 * @param string $implClassName
	 * @param string $scope For now, must be 'singleton'
	 * @throws MWException
	 */
	public function register( $typeName, $implClassName, $scope ) {

		// Check this type hasn't already been registered
		if ( isset( $this->registrations[$typeName] ) ) {
			throw new MWException( 'Attempted to register type ' . $typeName .
				', but it\'s already registered.' );
		}

		// Check that the registration isn't for an unsupported scope
		if ( $scope !== 'singleton' ) {
			throw new MWException( 'Attempted to register type ' . $typeName .
				' for a scope other than singleton.' );
		}

		$reflClass = new ReflectionClass( $implClassName );

		// Check that the implementation class is a subclass of the type
		if ( !$reflClass->isSubClassOf( $typeName ) ) {
			throw new MWException( $implClassName . ' is not a subclass of ' .
				$typeName );
		}

		// If the implementation class has a constructor, check that all the
		// constructor params are type-hinted
		if ( $reflClass->hasMethod( '__construct' ) ) {
			$constructor = $reflClass->getMethod( '__construct' );
			$reflParams = $constructor->getParameters();

			// Cycle through constructor params
			foreach ( $reflParams as $reflParam ) {

				// Raise an exception if there's no type hint
				if ( is_null( $reflParam->getClass() ) ) {

					throw new MWException( 'Attempted to register class ' .
						$implClassName . ' with no type hint for the ' .
						'constructor parameter ' . $reflParam->getName() );
				}
			}
		}

		// All OK, let's register
		$this->registrations[$typeName] =
			new Registration( $typeName, $implClassName, $scope );
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

		$reflClass = new ReflectionClass( $registration->implClassName );

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
	public $implClassName;
	public $scope;

	public function __construct( $typeName, $implClassName, $scope ) {
		$this->typeName = $typeName;
		$this->implClassName = $implClassName;
		$this->scope = $scope;
	}
}