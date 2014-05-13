<?php

namespace Campaigns;

use BadFunctionCallException;
use ReflectionClass;
use ReflectionProperty;

/**
 * To create some typesafe enums, just extend this class, declare some public
 * static properties without setting thier values, then call
 * YourClass::setUp(). Also, make your subclass final.
 * @see ITypesafeEnum
 */
abstract class TypesafeEnum implements ITypesafeEnum {

	/**
	 * @var array
	 */
	private static $metaByClassName = array();

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $fullyQualifiedName;

	/**
	 * @see ITypesafeEnum::setUp()
	 */
	public static function setUp() {

		// Get the the class this was called on and its static properties
		$calledClassName = get_called_class();
		$calledClass = new ReflectionClass ( $calledClassName );
		$props = $calledClass->getProperties ( ReflectionProperty::IS_STATIC );

		// Set each property to an instance of the called class and create an
		// associative array of names and values.
		$meta = array();

		foreach ( $props as $prop ) {

			if ( !$prop->isPublic() ) {
				continue;
			}

			if ( !is_null( $prop->getValue() ) ) {
				throw new BadFunctionCallException(
					'Enum setup called more than once or enum values already' .
					' set for ' . $calledClassName . '.' );

			}

			$name = $prop->getName();
			$fullyQualifiedName = $calledClassName . '::$' . $name;
			$val = new $calledClassName( $name, $fullyQualifiedName );
			$prop->setValue( $val );
			$meta[$name] = $val;
		}

		self::$metaByClassName[$calledClassName] = $meta;
	}

	/**
	 * @see ITypesafeEnum::getValues()
	 */
	public static function getValues() {
		return array_values( self::$metaByClassName[get_called_class()] );
	}

	/**
	 * @see ITypesafeEnum::isDeclared()
	 */
	public static function isDeclared( $valOrName ) {

		$meta = self::$metaByClassName[get_called_class()];

		if ( is_string ( $valOrName ) ) {
			return in_array( $valOrName, array_keys( $meta ) );

		} else {
			return in_array( $valOrName, array_values( $meta ) );
		}
	}

	/**
	 * @see ITypesafeEnum::getValueByName()
	 */
	public static function getValueByName( $name ) {

		$meta = self::$metaByClassName[get_called_class()];

		if ( isset( $meta[$name] ) ) {
			return $meta[$name];
		} else {
			return null;
		}
	}

	protected function __construct( $name, $fullyQualifiedName ) {
		$this->name = $name;
		$this->fullyQualifiedName = $fullyQualifiedName;
	}

	public function __toString() {
		return $this->name;
	}

	/**
	 * @see ITypesafeEnum::getName()
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @see ITypesafeEnum::getFullyQualifiedName()
	 */
	public function getFullyQualifiedName() {
		return $this->fullyQualifiedName;
	}
}