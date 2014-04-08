<?php

namespace Campaigns\Persistence\Internal\Db;

use ReflectionClass;
use ReflectionProperty;
use MWException;
use DatabaseBase;
use Campaigns\Persistence\Internal\FieldDatatype;
use Campaigns\Persistence\Internal\FieldOption;
use Campaigns\Persistence\IField;
use Campaigns\Persistence\RequiredFieldNotSetException;

/**
 * Handles mapping among object properties, field constants, field options,
 * object types, interfaces, database fields, tables and query bits.
 */
class DBMapper implements IDBMapper {

	protected $typeInfosByTypeName = array();
	protected $typeInfosByShortTypeName = array();
	protected $typeInfosByRealizationClsName = array();
	protected $fieldInfosByQualifiedFieldName = array();
	protected $configured = false;

	/**
	 * @see IDBMapper::getTableNameForObj()
	 */
	public function getTableNameForObj( $obj ) {
		return $this->getTypeInfoForObj( $obj )->tableName;
	}

	/**
	 * @see IDBMapper::getTableNameForType()
	 */
	public function getTableNameForType( $type ) {
		return $this->getTypeInfoForType( $type )->tableName;
	}

	/**
	 * @see IDBMapper::getIdFieldForObj()
	 */
	public function getIdFieldForObj( $obj ) {
		return $this->getTypeInfoForObj( $obj )->getIdField();
	}

	/**
	 * @see IDBMapper::getIdColForObj()
	 */
	public function getIdColForObj( $obj ) {
		return $this->getTypeInfoForObj( $obj )->idFieldInfo->column;
	}

	/**
	 * @see IDBMapper::getIdFieldForType()
	 */
	public function getIdFieldForType( $type ) {
		return $this->getTypeInfoForType( $type )->getIdField();
	}

	/**
	 * @see IDBMapper::getTypeForObj()
	 */
	public function getTypeForObj( $obj ) {
		return $this->getTypeInfoForObj( $obj )->typeName;
	}

	/**
	 * @see IDBMapper::isEntity()
	 */
	public function isEntity( $obj ) {
		return isset( $this->typeInfosByRealizationClsName[get_class( $obj )] );
	}

	/**
	 * @see IDBMapper::verifyRequiredFields()
	 */
	public function verifyRequiredFields( $obj ) {

		// Get the required fields for this object and loop through them
		$typeInfo = $this->getTypeInfoForObj( $obj );

		foreach ( $typeInfo->getFieldInfosByOption( FieldOption::$REQUIRED )
			as $fieldInfo ) {

			// If a required field is null, throw the appropriate exception
			if ( is_null( $this->getFieldValueWFI( $obj, $fieldInfo ) ) ) {

				throw new RequiredFieldNotSetException( 'Missing required field ' .
					$fieldInfo->field . ' on ' . $typeInfo->realizationClsName .
					'.' );
			}
		}
	}

	/**
	 * @see IDBMapper::prepareColsAndValsForDbWrite()
	 */
	public function prepareColsAndValsForDbWrite( $obj, DatabaseBase $db ) {

		$typeInfo = $this->getTypeInfoForObj( $obj );
		$map = array();

		// Loop through all the FieldInfos for this object
		foreach ( $typeInfo->fieldInfosByName as $fieldInfo ) {

			// Get the field's value from the object and tenderly munge it
			$val = $this->getFieldValueWFI( $obj, $fieldInfo );
			$val = $this->mungeValForDb( $val, $fieldInfo->datatype, $db );

			// Set the key/val pair
			$map[$fieldInfo->column] = $val;
		}

		return $map;
	}

	/**
	 * @see IDBMapper::getDbColumn()
	 */
	public function getDbColumn( IField $field ) {

		$qualifiedName = $field->getFullyQualifiedName();
		$fieldInfo = $this->fieldInfosByQualifiedFieldName[$qualifiedName];
		return $fieldInfo->column;
	}

	/**
	 * @see IDBMapper::fieldHasOption()
	 */
	public function fieldHasOption( IField $field, FieldOption $opt ) {
		$qualifiedName = $field->getFullyQualifiedName();
		$fieldInfo = $this->fieldInfosByQualifiedFieldName[$qualifiedName];
		return in_array( $opt, $fieldInfo->options );
	}

	/**
	 * @see IDBMapper::getFieldsWithOption()
	 */
	public function getFieldsWithOption( $obj, FieldOption $opt ) {

		// Get the corresponding fieldInfos
		$typeInfo = $this->getTypeInfoForObj( $obj );
		$fieldInfos = $typeInfo->getFieldInfosByOption( $opt );

		// Return an array with just the fields
		return array_map( function( $fieldInfo ) {
			return $fieldInfo->field;
		}, $fieldInfos );
	}

	/**
	 * @see IDBMapper::getUniqueIndexes()
	 */
	public function getUniqueIndexes( $obj ) {
		$typeInfo = $this->getTypeInfoForObj( $obj );
		return $typeInfo->uniqueIndexNamesAndFields;
	}

	/**
	 * @see IDBMapper::getFieldValue()
	 */
	public function getFieldValue( $obj, IField $field ) {

		$fieldInfo = $this->fieldInfosByQualifiedFieldName[
			$field->getFullyQualifiedName()];

		return $this->getFieldValueWFI( $obj, $fieldInfo );
	}

	/**
	 * @see IDBMapper::getId()
	 */
	public function getId( $obj ) {
		$typeInfo = $this->getTypeInfoForObj( $obj );
		return $this->getFieldValue( $obj, $typeInfo->getIdField() );
	}

	/**
	 * @see IDBMapper::setIdOfNewObj()
	 */
	public function setIdOfNewObj( $obj, $id ) {

		$typeInfo = $this->getTypeInfoForObj( $obj );
		$idFieldInfo = $typeInfo->idFieldInfo;

		// An object is considered to be new if its ID field isn't set.
		// See DBPersistenceManager::save() and
		// DBPersistenceManager::updateOrCreate().
		$currentId = $this->getFieldValueWFI( $obj, $idFieldInfo );

		if ( !is_null( $currentId ) ) {

			throw new MWException( 'Attempted to set the ID of a ' .
				get_class( $obj ) . ' that already has an id. Current ID: ' .
				$currentId . ' Attempted to set as: ' . $id );
		}

		$this->setFieldValueWFI( $obj, $idFieldInfo, $id );
	}

	/**
	 * @see IDBMapper::makeObjectFromDbRow()
	 */
	public function makeObjectFromDbRow( $type, $row ) {

		$typeInfo = $this->getTypeInfoForType( $type );

		// PHP 5.3 doesn't provide a reflection method for bypassing the
		// constructor, so we have to use the following hack.
		// See http://stackoverflow.com/a/2556089

		// TODO When PHP 5.4 is available on production, use this instead:
		// $obj = $typeInfo->realizationCls->newInstanceWithoutConstructor();

		$clsName = $typeInfo->realizationClsName;

		$obj = unserialize(
			sprintf(
				'O:%d:"%s":0:{}',
				strlen( $clsName ), $clsName
			)
		);

		foreach ( $typeInfo->fieldInfosByName as $fieldInfo ) {

			$column = $fieldInfo->column;
			$val = $row->$column;
			$val = $this->mungeValFromDb( $val, $fieldInfo->datatype );
			$this->setFieldValueWFI( $obj, $fieldInfo, $val );
		}

		return $obj;
	}

	/**
	 * Get the value of an object's field, specified by a DBFieldInfo.
	 *
	 * @param $obj The object with the field value to get
	 * @param DBFieldInfo $fieldInfo Info about the field whose value we want
	 * @return The value of the field requested
	 */
	protected function getFieldValueWFI( $obj, DBFieldInfo $fieldInfo ) {
		return $fieldInfo->property->getValue( $obj );
	}

	/**
	 * Set the value of an object's field, specified by a DBFieldInfo.
	 *
	 * @param $obj The object with the field value to get
	 * @param DBFieldInfo $fieldInfo Info about the field whose value we want
	 * @param $val The value to set
	 */
	protected function setFieldValueWFI( $obj, DBFieldInfo $fieldInfo, $val ) {
		$prop = $fieldInfo->property->setValue( $obj, $val );
	}

	/**
	 * Take a value received from a database row and munge it for use in
	 * entities, according to the $datatype.
	 *
	 * @param mixed $val
	 * @param FieldDatatype $datatype
	 * @return mixed
	 */
	protected function mungeValFromDb( $val, FieldDatatype $datatype ) {

		switch ( $datatype ) {
			case FieldDatatype::$INT:

				if ( !is_int( $val ) ) {
					return intval( $val );
				}

				return $val;

			case FieldDatatype::$STRING:
				return $val;

			case FieldDatatype::$BOOLEAN:

				if ( !is_bool( $val ) ) {
					return $val > 0;
				}

				return $val;

			case FieldDatatype::$MW_TS:
				return $val;

			default:
				throw new MWException( 'Datatype not handled: ' . $datatype .
				'.' );
		}
	}

	/**
	 * Take a value as contained in an entity and munge it for insertion in
	 * the database, as indicated by $datatype.
	 *
	 * @param mixed $val
	 * @param FieldDatatype $datatype
	 * @param DatabaseBase $db The database abstraction object to be used
	 * @return mixed
	 */
	protected function mungeValForDb( $val, FieldDatatype $datatype,
		 DatabaseBase $db ) {

		// If this field is a timestamp, munge it appropriately
		if ( !is_null( $val ) && $datatype === FieldDatatype::$MW_TS ) {
			return $db->timestamp( $val );
		} else {
			return $val;
		}
	}

	/**
	 * Get the correct DBTypeInfo for this $obj.
	 *
	 * @param mixed $obj
	 * @return DBTypeInfo
	 */
	protected function getTypeInfoForObj( $obj ) {
		return $this->typeInfosByRealizationClsName[get_class( $obj )];
	}

	/**
	 * Get the correct DBTypeInfo for this type. Either the full type name with
	 * namespace or the short name (just the class or interface name) may be
	 * used.
	 *
	 * @param string $typeName
	 * @return DBTypeInfo
	 */
	protected function getTypeInfoForType( $typeName ) {

		if ( isset( $this->typeInfosByShortTypeName[$typeName] ) ) {
			return $this->typeInfosByShortTypeName[$typeName];
		} else {
			return $this->typeInfosByTypeName[$typeName];
		}
	}

	/**
	 * @see IDBMapper::ensureConfigured()
	 */
	public function ensureConfigured() {

		if ( !$this->configured ) {

			$this->loadConfig();
			$this->configured = true;
		}
	}

	/**
	 * Load configuration using info set in $wgCampaignsDBPersistence.
	 */
	protected function loadConfig() {

		// TODO Throw exceptions for more than one registration with the same
		// table, realization class or field class

		// Cycle through config info. Key/val pairs are type/config.
		foreach ( $GLOBALS['wgCampaignsDBPersistence'] as $typeName => $cfg ) {

			// get the fields
			$fieldClsName = $cfg['field_class'];
			$fields = $fieldClsName::getValues();

			// get the realization class
			$realizationClsName = $cfg['realization'];
			$realizationCls = new ReflectionClass( $realizationClsName );

			// Check that the realization class is a subclass of the type
			if ( !$realizationCls->isSubClassOf( $typeName ) &&
				$typeName !== $realizationClsName ) {

				throw new MWException( $realizationClsName .
					' is not a subclass of ' . $typeName );
			}

			// Create the DBTypeInfo
			$tableName = $cfg['table'];
			$typeParts = explode( '\\', $typeName );
			$shortTypeName = $typeParts[count( $typeParts ) - 1];

			$typeInfo = new DBTypeInfo( $typeName, $shortTypeName,
				$realizationClsName, $realizationCls, $tableName );

			// Cycle through the fields, get a DBFieldInfo for each one, and
			// index by fully qualified field name.
			$fieldInfos = array();

			foreach ( $fields as $field ) {

				// Get the field info and add it to they array
				$fieldInfo = $this->makeFieldInfo( $field,
					$cfg['column_prefix'], $realizationCls );

				$fieldInfos[] = $fieldInfo;

				// Add it to an index by qualified field name
				$qualifiedFieldName = $field->getFullyQualifiedName();

				$this->fieldInfosByQualifiedFieldName[$qualifiedFieldName] =
					$fieldInfo;
			}

			// Set the field infos on the DBTypeInfo
			$typeInfo->setFieldInfos( $fieldInfos );

			// Add entries to our DBTypeInfo indexes

			$this->typeInfosByTypeName[$typeName] = $typeInfo;

			// Since we allow referencing types by short type name (just the
			// class/interface name without the namespace) we can't handle more
			// than one type with the same name.
			if ( isset( $this->typeInfosByShortTypeName[$shortTypeName] ) ) {

				throw new MWException( 'Can\'t register two types with the ' .
				'same name: ' . $shortTypeName );
			}

			$this->typeInfosByShortTypeName[$shortTypeName] = $typeInfo;

			$this->typeInfosByRealizationClsName[$realizationClsName] =
				$typeInfo;
		}
	}

	/**
	 * Create a DBFieldInfo for this $field.
	 *
	 * @param IField $field The field that we need a DBFieldInfo for
	 * @param string $colPrefix The database column prefix
	 * @param ReflectionClass $realizationCls The realization class that this
	 *   field is used with
	 *
	 * @return DBFieldInfo
	 */
	protected function makeFieldInfo( IField $field, $colPrefix,
		ReflectionClass $realizationCls ) {

		// Get the field name, property, column name and reflection prop
		$name = $field->getName();
		$propName = $this->toLowerCamelCase( $name );
		$column = $colPrefix . '_' . strtolower( $name );
		$prop = $realizationCls->getProperty( $propName );

		// We're going to keep this reference to the reflection prop and use it
		// later. So let's make its accessible.
		$prop->setAccessible( true );

		// Datatype and field options are set in annotations in the property's
		// doc comment
		$propDoc = $prop->getDocComment();
		$infoFromPropDoc = $this->processPropertyAnnotations( $propDoc, $name );

		// Put all the above in a DBFieldInfo and return it
		return new DBFieldInfo( $field, $propName, $prop, $column,
			$infoFromPropDoc['datatype'], $infoFromPropDoc['options'],
			$infoFromPropDoc['unique_index']);
	}

	/**
	 * Process annotations in a property's doc comment to get the field
	 * datatype and options.
	 *
	 * @param string $propDoc The doc comment for a field property
	 * @param string $fieldName The name of the field for this propDoc
	 * @return array An associative array with a 'datatype' key pointing to a
	 *   FieldDatatype, an 'options' key pointing to an array of FieldOptions
	 *   and 'unique_index' key pointing to an array of unique indexes the field
	 *   belongs to.
	 */
	protected function processPropertyAnnotations( $propDoc, $fieldName ) {

		$datatype = FieldDatatype::$STRING; // default datatype if none is set
		$options = array();
		$uniqueIndexNames = array();

		// Parse the annotations in the doc comment
		// This is a bit primitive but good enough for now
		if ( preg_match_all( '/@.*?$/m', $propDoc, $annotations ) > 0) {

			foreach ( $annotations[0] as $annotation ) {

				$annotationParts = explode( ' ', $annotation );
				$annotationPartsCount = count( $annotationParts );

				// The datatype follows a @var annotation
				if ( $annotationParts[0] === '@var' ) {

					if ( $annotationPartsCount > 1 ) {

						$dataTypeString = strtoupper( $annotationParts[1] );

						$annotatedDatatype =
							FieldDatatype::getValueByName( $dataTypeString );

						// Null means this isn't a known datatype
						if ( !is_null( $annotatedDatatype ) ) {
							$datatype = $annotatedDatatype;
						}
					}

				// Any other annotation is an option if it's the name of
				// a FieldOption enum
				} else {

					$optString = strtoupper(
						str_replace( '@', '', $annotationParts[0] ) );

					$annotatedOption =
						FieldOption::getValueByName( $optString );

					// Null means this isn't a known option
					if ( !is_null( $annotatedOption ) ) {

						if ( $annotatedOption === FieldOption::$UNIQUE ) {

							// @unique can appear several times
							if ( !in_array( $annotatedOption, $options ) ) {
								$options[] = $annotatedOption;
							}

							// If there's no string following @unique, then
							// this field is just unique on its own, and the
							// unique index is just the name of the field.
							// Otherwise, use the string after the annotation
							// for the unique index.
							$uniqueIndexNames[] = $annotationPartsCount > 1 ?
								$annotationParts[1] : $fieldName;

						} else {
							$options[] = $annotatedOption;
						}
					}
				}
			}
		}

		// Return the options and datatype in an associative array
		return array (
			'datatype' => $datatype,
			'options' => $options,
			'unique_index' => $uniqueIndexNames
		);
	}

	/**
	 * Converts from UNDERSCORE_CASE to lowerCamelCase
	 *
	 * @param string $str
	 * @return string
	 */
	protected function toLowerCamelCase( $str ) {

		$parts = explode( '_', $str);

		$parts[0] = strtolower( $parts[0] );

		for ( $i = 1; $i < count( $parts ); $i++) {
			$parts[$i] = ucwords( strtolower( $parts[$i] ) );
		}

		return implode( '', $parts );
	}
}

/**
 * Container for a type's mapping config. Performs some simple indexing
 * of DBFieldInfos.
 */
class DBTypeInfo {

	var $typeName;
	var $shortTypeName;
	var $realizationClsName;
	var $realizationCls;
	var $tableName;
	var $fieldInfosByName;
	var $fieldInfosByColumn;
	var $fieldInfosByOption;
	var $idFieldInfo;
	var $uniqueIndexNamesAndFields;

	/**
	 * @param string $typeName The full name of the type registered
	 * @param string $shortTypeName Just the type name (without the namesapce)
	 * @param string $realizationClsName The full name of the realization class
	 * @param ReflectionClass $realizationCls A ReflectionClass for the
	 *   realization class
	 * @param string $tableName The database table name for entities of this
	 *   type
	 */
	public function __construct( $typeName, $shortTypeName,
		$realizationClsName, ReflectionClass $realizationCls, $tableName ) {

		$this->typeName = $typeName;
		$this->shortTypeName = $shortTypeName;
		$this->realizationClsName = $realizationClsName;
		$this->realizationCls = $realizationCls;
		$this->tableName = $tableName;
	}

	/**
	 * Save and process DBFieldInfos for this type's fields
	 *
	 * @param array $fieldInfos An array of DBFieldInfos
	 * @throws MWException Thrown if there isn't exactly one field with the
	 *   ID option
	 */
	public function setFieldInfos( array $fieldInfos ) {

		// Set up the arrays for indexing
		$this->fieldInfosByName = array();
		$this->fieldInfosByColumn = array();
		$this->fieldInfosByOption = array();
		$this->uniqueIndexNamesAndFields = array();

		// Set up the outer-level keys and values of $this->fieldInfosByOption
		foreach ( FieldOption::getValues() as $opt ) {
			$this->fieldInfosByOption[$opt->getName()] = array();
		}

		// Loop through the fields and place values in the indexes
		foreach ( $fieldInfos as $fi ) {

			$this->fieldInfosByName[$fi->field->getName()] = $fi;
			$this->fieldInfosByColumn[$fi->column] = $fi;

			foreach ( $fi->options as $opt ) {
				$this->fieldInfosByOption[$opt->getName()][] = $fi;
			}

			foreach ( $fi->uniqueIndexNames as $uIndex ) {

				if ( !isset( $this->uniqueIndexNamesAndFields[$uIndex] ) ) {
					$this->uniqueIndexNamesAndFields[$uIndex] = array();
				}

				$this->uniqueIndexNamesAndFields[$uIndex][] = $fi->field;
			}
		}

		// Every type needs exactly one id field. Throw an exception if this
		// type doesn't respect that rule.
		if ( count( $this->getFieldInfosByOption( FieldOption::$ID ) )
			!== 1 ) {

			throw new MWException( 'Exactly one ID field must be declared ' .
				'for ' . $this->typeName . '.' );
		}

		// Remember the single ID DBFieldInfo for fast retrieval
		$idFieldInfos = $this->fieldInfosByOption[FieldOption::$ID->getName()];
		$this->idFieldInfo = $idFieldInfos[0];
	}

	/**
	 * @param IField $field
	 * @return DBFieldInfo
	 */
	public function getFieldInfoByField( IField $field ) {
		return $this->fieldInfosByName[$field->getName()];
	}

	/**
	 * @param string $column
	 * @return DBFieldInfo
	 */
	public function getFieldInfoByColumn( $column ) {
		return $this->fieldInfosByColumn[$column];
	}

	/**
	 * @param FieldOption $opt
	 * @return array of DBFieldInfos
	 */
	public function getFieldInfosByOption( FieldOption $opt ) {
		return $this->fieldInfosByOption[$opt->getName()];
	}

	/**
	 * @return IField
	 */
	public function getIdField() {
		return $this->idFieldInfo->field;
	}
}

/**
 * Container for info about a field mapping.
 */
class DBFieldInfo {

	var $field;
	var $propName;
	var $property;
	var $column;
	var $datatype;
	var $options;
	var $uniqueIndexNames;

	/**
	 * @param IField $field The field this is about
	 * @param string $propName The name of the property in the realization class
	 * @param ReflectionProperty $property The property in the realization class
	 * @param string $column The database table column
	 * @param FieldDatatype $datatype The field's datatype
	 * @param array $options The FieldOptions set on this field
	 * @param array $uniqueIndexNames An array with the names of the unique
	 *   indexes this field is a part of
	 */
	public function __construct( IField $field, $propName,
		ReflectionProperty $property, $column, FieldDatatype $datatype,
		$options, $uniqueIndexNames ) {

		$this->field = $field;
		$this->propName = $propName;
		$this->property = $property;
		$this->column = $column;
		$this->datatype = $datatype;
		$this->options = $options;
		$this->uniqueIndexNames = $uniqueIndexNames;
	}
}