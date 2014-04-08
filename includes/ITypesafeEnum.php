<?php

namespace Campaigns;

/**
 * Interface that defines public methods available on a TypesafeEnum.
 */
interface ITypesafeEnum {
	public static function setUp();

	public static function getValues();

	public static function isDeclared( $valOrName );

	public static function getValueByName( $name );

	public function getName();

	public function getFullyQualifiedName();
}