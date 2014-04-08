<?php

namespace Campaigns\Persistence\Internal\Db;

use DatabaseBase;
use Campaigns\Persistence\IField;
use Campaigns\Persistence\Internal\FieldOption;

/**
 * Handles mapping among object properties, field constants, field options,
 * object types, interfaces and database fields, tables and query bits.
 */
interface IDBMapper {

	/**
	 * Get the name of a database table whose rows store instances of
	 * the same class as $obj.
	 *
	 * @param mixed $obj An object of a class registered with the the mapper as
	 *   a realization class
	 * @return string
	 */
	public function getTableNameForObj( $obj );

	/**
	 * Get the name of a database table whose rows store instances of
	 * the type $typeName.
	 *
	 * @param string $typeName A type registered with the mapper; may be sent
	 *   with or without namespace
	 * @return string
	 */
	public function getTableNameForType( $typeName );

	/**
	 * Get the ID field for entities whose realization class is the same class
	 *   as $obj.
	 *
	 * @param mixed $obj An object of a class registered with the the mapper as
	 *   a realization class
	 * @return IField
	 */
	public function getIdFieldForObj( $obj );

	/**
	 * Get the ID column for entities whose realization class is the same class
	 *   as $obj.
	 *
	 * @param mixed $obj An object of a class registered with the the mapper as
	 *   a realization class
	 * @return string
	 */
	public function getIdColForObj( $obj );

	/**
	 * Get the ID field for the type $typeName.
	 *
	 * @param string $typeName A type registered with the mapper; may be sent
	 *   with or without namespace
	 * @return IField
	 */
	public function getIdFieldForType( $type );

	/**
	 * Get the name of the type (including namespace) associated with the
	 *   the class of this object as a realization class.
	 *
	 * @param mixed $obj An object of a class registered with the the mapper as
	 *   a realization class
	 * @return string
	 */
	public function getTypeForObj( $obj );

	/**
	 * Is this object an entity? (Checked by looking at the object's class
	 * and seeing if that class is registered as a realization class.)
	 *
	 * @param mixed $obj An object of a class registered with the the mapper as
	 *   a realization class
	 */
	public function isEntity( $obj );

	/**
	 * Verify that all required fields are present on $obj.
	 *
	 * @param $obj An object of a class registered with the mapper as a
	 *   realization class
	 *
	 * @throws Campaigns\Persistence\RequiredFieldNotSetException
	 */
	public function verifyRequiredFields( $obj );

	/**
	 * Create an associative array of DB column names and values for use in
	 * insert or update calls to DatabaseBase.
	 *
	 * @param mixed $obj An object of a class registered with the mapper as a
	 *   realization class
	 * @param DatabaseBase $db A MW database abstraction object
	 *
	 * @return array
	 */
	public function prepareColsAndValsForDbWrite( $obj, DatabaseBase $db );

	/**
	 * Get the database column that maps to this field.
	 *
	 * @param IField $field
	 * @return string
	 */
	public function getDbColumn( IField $field );

	/**
	 * Does this field have this option?
	 *
	 * @param IField $field
	 * @param FieldOption $opt
	 */
	public function fieldHasOption( IField $field, FieldOption $opt );

	/**
	 * Get an array of IFields for $obj that have this $opt
	 *
	 * @param mixed $obj An object of a class registered with the mapper as a
	 *   realization class
	 * @return array An array of IFields
	 */
	public function getFieldsWithOption( $obj, FieldOption $opt );

	public function getUniqueIndexes( $obj );

	/**
	 * Get the value of a field in an object, as specified by an IField.
	 *
	 * @param mixed $obj An object of a class registered with the mapper as a
	 *   realization class
	 * @param IField $field The field whose value we want
	 * @return The value of the field requested
	 */
	public function getFieldValue( $obj, IField $field );

	/**
	 * Get the value of the ID field for this object.
	 *
	 * @param mixed $obj An object of a class registered with the mapper as a
	 *   realization class
	 */
	public function getId( $obj );

	/**
	 * Set the ID of a new object
	 *
	 * @param mixed $obj An object of a class registered with the mapper as a
	 *   realization class
	 * @param mixed $id
	 */
	public function setIdOfNewObj( $obj, $id );

	/**
	 * Instantiate an object of the type $type (and of the realization class
	 * associated with that type) using the contents of a database row.
	 *
	 * @param string $type The type to instantiate
	 * @param stdClass $row An object representing a database row as returned
	 *   by ResultWrapper.
	 * @return mixed
	 */
	public function makeObjectFromDbRow( $type, $row );

	/**
	 * Ensure that the mapping configuration has been loaded from global
	 * $wgCampaignsDBPersistence.
	 */
	public function ensureConfigured();
}