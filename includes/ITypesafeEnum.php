<?php

namespace Campaigns;

/**
 * Interface that defines public methods available on a TypesafeEnum.
 * @see TypesafeEnum
 */
interface ITypesafeEnum {

	/**
	 * Set up the values of the static properties for the enums.
	 * Should only be called once.
	 *
	 * @throws BadFunctionCallException Thrown if this method is called more than
	 *   once or if the static properties already have values
	 */
	public static function setUp();

	/**
	 * Get an array of declared enum values.
	 *
	 * @return ITypesafeEnum[]
	 */
	public static function getValues();

	/**
	 * Check if an enum is declared on the called class. If a string is sent,
	 * the value is looked up by name.
	 *
	 * @param TypesafeEnum|string $valOrName
	 * @return boolean
	 */
	public static function isDeclared( $valOrName );

	/**
	 * Returns the enum with this name, as declared by the called class, or null
	 * if the enum doesn't exist.
	 *
	 * @param string $name
	 * @return ITypesafeEnum
	 */
	public static function getValueByName( $name );

	/**
	 * Get the enum's name.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Get the enum's fully qualified name, in this format:
	 * Namespace\Classname::$ENUM_NAME
	 *
	 * @return string
	 */
	public function getFullyQualifiedName();
}