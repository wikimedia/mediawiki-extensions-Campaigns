<?php

namespace Campaigns\Persistence\Internal\Db;

use DatabaseBase;
use MWException;
use ApiBase;
use Campaigns\TypesafeEnum;
use Campaigns\Persistence\IPersistenceManager;
use Campaigns\Persistence\Condition;
use Campaigns\Persistence\Operator;
use Campaigns\Persistence\Order;
use Campaigns\Persistence\IField;
use Campaigns\Persistence\Internal\FieldOption;

/**
 * Database-based implementation of IPersistenceManager.
 *
 * @see IPersistenceManager
 *
 * See that class for general setup instructions. For this database-based
 * implementation, set key-values in global $wgCampaignsDBPersistence as
 * follows:
 * - Set the keys in $wgCampaignsDBPersistence to the fully qualified names of
 *   types (interfaces or classes) that represent your entities.
 * - Set the values to associative arrays with the following keys:
 *     realization:   The fully qualified name of the entity's realization class
 *     table:         The database table that holds the entities
 *     column_prefix: The prefix used for the table's column names; the rest
 *                    of the column names must correspond to the entity field
 *                    names, in lower_underscore_format, with an additional
 *                    underscore to separate the prefix from the field name
 *     field_class:   A class that implements IField, extends
 *                    TypesafeEnum and specifies the entities' fields
 *
 * Please note that this implementation still has several limitations:
 * - There is no object identity map. The consumer is responsible for keeping
 *   track of possible duplicate objects that refer to the same persisted
 *   entity.
 * - The objects used for entities must be direct instances of the
 *   realization class registered. Subclassing entity realization classes is
 *   currently not supported.
 * - Only generated ID fields are available.
 * - Fields or groups of fields declared as unique must have corresponding
 *   uniqueness constraints in the database.
 * - Before using a new entity object with other entity objects, you must first
 *   queueSave() on it and call flush().
 */
class DBPersistenceManager implements IPersistenceManager {

	protected $mapper;
	protected $queue = array();

	// TODO improve this
	const MAX_FETCH_LIMIT = ApiBase::LIMIT_BIG2;

	public function __construct( IDBMapper $mapper ) {
		$this->mapper = $mapper;

		// Supposedly we shouldn't do something like this here. But creating a
		// factory just to avoid it seems excessive.
		$this->mapper->ensureConfigured();
	}

	/**
	 * @see IPersistenceManager::queueSave()
	 */
	public function queueSave( $obj, $duplicatesCallback=null ) {
		$this->queue[] = new SaveOperation( $obj, $duplicatesCallback );
	}

	/**
	 * @see IPersistenceManager::queueUpdateOrCreate()
	 */
	public function queueUpdateOrCreate( $obj, array $conditionFields ) {
		$this->queue[] = new UpdateOrCreateOperation( $obj, $conditionFields );
	}

	/**
	 * @see IPersistenceManager::queueDelete()
	 */
	public function queueDelete( $type, $conditions  ) {
		$this->queue[] = new DeleteOperation( $type, $conditions );
	}

	/**
	 * @see IPersistenceManager::flush()
	 */
	public function flush() {

		while ( count( $this->queue ) > 0 ) {

			// Get the next operation to process
			$nextOp = array_shift( $this->queue );

			// Peek ahead to upcoming operations. If the next has the same
			// properties and operation type as this one, then we only have to
			// do that one. The main reason for doing this is so a new object
			// can be queued to save automatically, and it can also be modified
			// right after being created and then queued to save manually, and
			// we'll still only perform one save.

			while ( count( $this->queue ) > 0 && $this->queue[0] == $nextOp ) {
				$nextOp = array_shift( $this->queue );
			}

			// Perform the operation
			switch ( $nextOp->operationType ) {

				case OperationType::$SAVE:
					$this->save( $nextOp );
					break;

				case OperationType::$UPDATE_OR_CREATE:
					$this->updateOrCreate( $nextOp );
					break;

				case OperationType::$DELETE:
					$this->delete( $nextOp );
					break;
			}
		}
	}

	/**
	 * @see IPersistenceManager::existsWithConditions()
	 */
	public function existsWithConditions( $type, $conditions,
			$useMaster=false) {

		$count = $this->count( $type, $conditions, $useMaster );
		return $count > 0;
	}

	/**
	 * @see IPersistenceManager::getOne()
	 */
	public function getOne( $type, $conditions, $useMaster=false ) {

		$db = $this->getDb( $useMaster );
		return $this->getOneInternal( $type, $conditions, $db );
	}

	/**
	 * @see IPersistenceManager::getOneById()
	 */
	public function getOneById( $type, $id, $useMaster=false ) {

		$condition = new Condition(
			$this->mapper->getIdFieldForType( $type ),
			Operator::$EQUAL,
			$id
		);

		return $this->getOne( $type, $condition, $useMaster );
	}

	/**
	 * @see IPersistenceManager::count()
	 */
	public function count( $type, $conditions=null, $useMaster=false) {

		$db = $this->getDb( $useMaster );
		$tableName = $this->mapper->getTableNameForType( $type );

		if ( is_null( $conditions ) ) {
			$condsForQuery = '';
		} else {
			$condsForQuery = $this->prepareConditions( $conditions, $db );
		}

		$result = $db->selectRow(
			$tableName,
			array( 'row_count' => 'COUNT(1)' ),
			$condsForQuery,
			__METHOD__
		);

		// This shouldn't happen
		if ( !$result ) {
			throw new MWException( 'Couldn\'t get row count for table ' .
				$tableName . '.' );
		}

		return intval( $result->row_count );
	}

	/**
	 * @see IPersistenceManager::get()
	 */
	public function get( $type, IField $orderByField, Order $order,
		$conditions=null, $fetchLimit=null, &$continueKey=null ) {

		// $orderByField must be unique or id for $continueKey to work
		// TODO this will incorrectly let through fields that are part of
		// composite unique indexes
		if ( !$this->mapper->fieldHasOption(
				$orderByField, FieldOption::$UNIQUE ) &&

			!$this->mapper->fieldHasOption( $orderByField, FieldOption::$ID ) ) {

			throw new MWException( '$orderByField must be unique or an id.' );
		}

		$dbr = $this->getDb( false );
		$tableName = $this->mapper->getTableNameForType( $type );
		$orderByCol = $this->mapper->getDbColumn( $orderByField );

		// $conditions should either be a single Condition or an array of them
		if ( is_null( $conditions ) ) {
			$conditions = array();

		} elseif ( $conditions instanceof Condition) {
			$conditions = array( $conditions );

		} elseif ( !is_array( $conditions )) {

			throw new MWException( 'Illegal argument: $conditions must be a ' .
				'Condition or an array of Conditions.' );
		}

		// Add the condition for the continue key, if one has been sent
		if ( !is_null( $continueKey ) ) {

			$contCondOperator = $order === Order::$ASCENDING ?
				Operator::$GT_OR_EQUAL : Operator::$LT_OR_EQUAL;

			$conditions[] = new Condition(
				$orderByField,
				$contCondOperator,
				$continueKey
			);
		}

		// Process conditions
		$condsForQuery = $this->prepareConditions( $conditions, $dbr );

		// Set up fetch limit (limit in SQL parlance)
		$maxFetchLimit = $this->getMaxFetchLimit( $type );

		if ( !is_null( $fetchLimit ) ) {

			if ( $fetchLimit > $maxFetchLimit ) {
				throw new MWException( $fetchLimit . ' entities of the type ' .
					$type . ' requested, but the  maximum is ' .
					$maxFetchLimit );
			}

			$fetchLimit = intval( $fetchLimit );

		} else {
			$fetchLimit = $maxFetchLimit;
		}

		// Set up options: limit and order by
		$options = array(
			'ORDER BY' => $orderByCol . ' ' .$this->getOrderSqlString( $order ),

			// Actually request one extra to see if there are more records
			'LIMIT' => $fetchLimit + 1
		);

		// Run the query
		$dbResult = $dbr->select(
			$tableName,
			'*',
			$condsForQuery,
			__METHOD__,
			$options
		);

		// Null $continueKey if we have only $max or less campaigns to return.
		// (Note that the opposite case is taken care of in the loop below.)
		if ( $dbResult->numRows() <= $fetchLimit ) {
			$continueKey = null;
		}

		// Build up the result
		$count = 0;
		$result = array();

		foreach ( $dbResult as $row ) {

			// If we're over the limit, don't add the row, and set $continueKey
			if ( $count >= $fetchLimit ) {
				$continueKey = $row->$orderByCol;
				break;
			}

			// Get the object
			$result[] = $this->mapper->makeObjectFromDbRow( $type, $row );

			$count++;
		}

		return $result;
	}

	/**
	 * @see IPersistenceManager::getMaxFetchLimit()
	 */
	public function getMaxFetchLimit( $type ) {

		// We could eventually have different limits for different types but
		// it's all the same now.
		return self::MAX_FETCH_LIMIT;
	}

	/**
	 * @see IPersistenceManager::getAnyStringForLikeOperator()
	 */
	public function getAnyStringForLikeOperator() {
		return $this->getDb( false )->anyString();
	}

	/**
	 * Perform a save operation.
	 * @see IPersistenceManager::queueSave()
	 *
	 * @param SaveOperation $op The operation to perform
	 */
	protected function save( SaveOperation $op ) {

		$obj = $op->obj;

		// First check that the object has all the required fields. This will
		// throw an exception if there's a problem.
		$this->mapper->verifyRequiredFields( $obj );

		$dbw = $this->getDb( true );

		// Get the table name and prepare the columns
		$tableName = $this->mapper->getTableNameForObj( $obj );
		$colsAndVals = $this->mapper->prepareColsAndValsForDbWrite( $obj, $dbw );

		// An object is considered to be new if its ID field isn't set.
		// See also updateOrCreate() and DBMapper::setIdOfNewObj().
		$id = $this->mapper->getId( $obj );
		$insert = is_null( $id );

		// Insert a new row or update an existing row.
		// Using IGNORE and then checking the number of affected rows is a
		// recommended way of preventing duplicates on fields with unique
		// indexes. This technique allows us to avoid locking reads.

		if ( $insert ) {

			// Insert the row
			$dbw->insert(
				$tableName,
				$colsAndVals,
				__METHOD__,
				array( 'IGNORE' )
			);

		} else {

			// Create a condition for selecting the right row using the ID field
			$idField = $this->mapper->getIdFieldForObj( $obj );

			$condition = new Condition(
				$idField,
				Operator::$EQUAL,
				$id
			);

			$condForQuery = $this->prepareConditions( $condition, $dbw );

			// Update the row
			$dbw->update(
				$tableName,
				$colsAndVals,
				$condForQuery,
				__METHOD__,
				array( 'IGNORE' )
			);
		}

		// One row modified, as expected?
		if ( $dbw->affectedRows() === 1 ) {

			// If this was an insert, set the id field on the new entity
			if ( $insert ) {
				$this->mapper->setIdOfNewObj( $obj, $dbw->insertId() );
			}

			// All done
			return;
		}

		// The rest of this method is for when a row wasn't changed (likely
		// due to a duplicate index value).

		// Cycle through the unique indexes and see if there was a failure due
		// to duplicates on them.
		$uniqueIndexes = $this->mapper->getUniqueIndexes( $obj );

		foreach ( $uniqueIndexes as $uniqueIndex => $uIdxFields ) {

			// Make query conditions
			$conditions = array();
			foreach ( $uIdxFields as $uIdxField ) {

				$val = $this->mapper->getFieldValue( $obj, $uIdxField );

				$conditions[] = new Condition(
					$uIdxField,
					Operator::$EQUAL,
					$val
				);
			}

			// Get the object that had the same values on the unique index
			// fields
			$objWithSameValues = $this->getOneInternal(
				$this->mapper->getTypeForObj( $obj ),
				$conditions, $dbw, $tableName );

			// If we got an object, perform more checks to see what to do.
			// Otherwise just continue with the loop.
			if ( !is_null( $objWithSameValues ) ) {

				// If we're doing an update rather than insert, we have to
				// do some extra checks.
				if ( !$insert ) {

					// One possibility is that the two objects are
					// identical. This can happen in MySQL if you try to
					// update a row with exactly the same values that it
					// already had; in that case MySQL will not count it
					// as a row that was modified. This doesn't happen with
					// SQLite, though.
					// TODO If entities can ever have objects as field
					// values, this check should be modified.
					if ( $obj == $objWithSameValues ) {

						// No need to continue checking indexes here, since
						// they'll all take us to the same result.
						return;
					}

					// If the two objects don't have identical values, but
					// have the same ID, then the issue must have been a
					// different unique index, or there must have been some
					// other problem.
					if ( $id === $this->mapper->getId( $objWithSameValues ) ) {
						continue;
					}
				}

				// If we're here, it means the duplicate was on this index.
				// If we got a callback, call it. Otherwise, throw an exception.
				if ( !is_null( $op->duplicatesCallback ) ) {

					$duplicatesCallback = $op->duplicatesCallback;
					$duplicatesCallback( $obj, $uniqueIndex );

					// Nothing more to do here
					return;

				} else {

					throw new MWException(
						'Attempted to save a ' . get_class( $obj )
						. ' with duplicate value(s) for the unique index ' .
						$uniqueIndex . '.' );
				}
			}
		}

		// Hmmm, if we got here with no exceptions, then something else
		// probably went wrong. However, it's also possible that there
		// was indeed a collision on a unique field, but that the row
		// with the duplicate value was changed right away and wasn't
		// detected above.
		throw new MWException( 'Couldn\'t save ' .
			get_class( $obj ) . ' with id ' . $id . '. Reason: unknown. ' .
			'Possibly due to an attempt to save an entity with a ' .
			'duplicate value for a unique field that was changed ' .
			'immediately after transaction.' );
	}

	/**
	 * Perform an update or create operation.
	 * @see IPersistenceManager::queueUpdateOrCreate()
	 *
	 * @param UpdateOrCreateOperation $op
	 */
	protected function updateOrCreate( UpdateOrCreateOperation $op ) {

		// TODO similar to the first bit of save(), but I'm not sure
		// consolidating would be worth the required extra complexity.

		$obj = $op->obj;

		// This operation should only be used with a new object, i.e., one that
		// doesn't have an id field.
		// See also save() and DBMapper::setIdOfNewObj().
		$id = $this->mapper->getId( $obj );

		if ( !is_null( $id ) ) {
			throw new MWException( 'Tried to do an updateOrCreate on a ' .
				get_class( $obj ) .' that has already been saved. Id: ' . $id .
				'.' );
		}

		// First check that the object has all the required fields. This will
		// throw an exception if there's a problem.
		$this->mapper->verifyRequiredFields( $obj );

		$dbw = $this->getDb( true );

		// Get the table name, prepare columns and values for insert
		$tableName = $this->mapper->getTableNameForObj( $obj );
		$insertColsAndVals =
			$this->mapper->prepareColsAndValsForDbWrite( $obj, $dbw );

		// Prepare the columns and values for set and the columns for conditions

		// This copies the array 8p
		$setColsAndVals = $insertColsAndVals;

		$conditionCols = array();

		foreach ( $op->conditionFields as $conditionField ) {
			$dbCol = $this->mapper->getDbColumn( $conditionField );
			$conditionCols[] = $dbCol;
			unset( $setColsAndVals[$dbCol] );
		}

		// Remove id column from columns to set
		unset( $setColsAndVals[$this->mapper->getIdColForObj( $obj )] );

		// Run the query
		$dbw->upsert(
			$tableName,
			$insertColsAndVals,
			$conditionCols,
			$setColsAndVals,
			__METHOD__
		);
	}

	/**
	 * Perform a delete operation.
	 * @see IPersistenceManager::queueDelete()
	 *
	 * @param DeleteOperation $op
	 */
	protected function delete( DeleteOperation $op ) {

		$dbw = $this->getDb( true );

		$condForQuery = $this->prepareConditions( $op->conditions, $dbw );

		// Perform the delete
		$dbw->delete(
			$this->mapper->getTableNameForType( $op->type ),
			$condForQuery,
			__METHOD__
		);
	}

	/**
	 * Get a database abstraction object (a DatabaseBase).
	 *
	 * @param boolean $writeDb Set to true for DB_MASTER, false for DB_SLAVE
	 * @return DatabaseBase
	 */
	protected function getDb( $writeDb ) {
		if ( $writeDb ) {
			return wfGetDB( DB_MASTER );
		}

		return wfGetDB( DB_SLAVE );
	}

	/**
	 * Turn one or more Conditions into an array for specifying conditions in a
	 * database query.
	 *
	 * @param Condition|array $conditions A single Condition or an array of them
	 * @param DatabaseBase $db
	 * @return array
	 */
	protected function prepareConditions( $conditions, DatabaseBase $db ) {

		$preparedConds = array();

		// If this is an array, prepare each element
		if ( is_array( $conditions ) ) {
			foreach ( $conditions as $condition ) {

				$preparedConds[] = $this->prepareSingleCondition(
					$condition, $db );
			}

		// If this is a Condition, prepare it
		} elseif ( $conditions instanceof Condition ) {

			$preparedConds[] =  $this->prepareSingleCondition(
				$conditions, $db );

		// Something's wrong
		} else {
			throw new MWException( 'Illegal argument: $conditions must be ' .
				'a Condition or an array of Conditions.' );
		}

		// Return the array of prepared conditions
		return $preparedConds;
	}

	/**
	 * Turn a Condition into a string for specifiying a condition in a
	 * database query.
	 *
	 * @param Condition $condition
	 * @param DatabaseBase $db
	 * @return string
	 */
	protected function prepareSingleCondition( Condition $condition,
		DatabaseBase $db ) {

		$dbColumn = $this->mapper->getDbColumn( $condition->field );
		$val = $condition->value;

		// TODO try producing structured conditions
		switch ( $condition->operator ) {

			case Operator::$EQUAL:
				return $dbColumn . '='
					. $db->addQuotes( $val );

			case Operator::$GT_OR_EQUAL:
				return $dbColumn . '>='
					. $db->addQuotes( $val );

			case Operator::$LT_OR_EQUAL:
				return $dbColumn . '<='
					. $db->addQuotes( $val );

			case Operator::$IS_NULL:
				return $dbColumn . ' IS NULL';

			case Operator::$LIKE:
				return $dbColumn .
					$db->buildLike( $val );

			default:
				throw new MWException( 'Unknown operator ' .
					$condition->operator . '.' );
		}
	}

	/**
	 * Return a single entity of this $type identified by $conditions.
	 * $tableName may be provided if it's handy, to avoid having to look it up
	 * here.
	 *
	 * @param string $type
	 * @param Condition|array $conditions A single Condition or an array of them
	 * @param DatabaseBase $db
	 * @param string $tableName
	 * @return mixed
	 */
	protected function getOneInternal( $type, $conditions, DatabaseBase $db,
			$tableName=null ) {

		if ( is_null( $tableName ) ) {
			$tableName = $this->mapper->getTableNameForType( $type );
		}

		$condsForQuery = $this->prepareConditions( $conditions, $db );

		$result = $db->select(
			$tableName,
			'*',
			$condsForQuery,
			__METHOD__
		);

		if ( $result->numRows() === 0 ) {
			return null;
		}

		return $this->mapper->makeObjectFromDbRow( $type,
			$result->fetchObject() );
	}

	/**
	 * Get a string for identifying an order (ascending or descending) in a
	 *   database query.
	 *
	 * @param Order $ord
	 * @return string
	 */
	protected function getOrderSqlString( Order $ord ) {
		switch ( $ord ) {
			case Order::$ASCENDING:
				return 'ASC';

			case Order::$DESCENDING:
				return 'DESC';

			default:
				throw new MWException( 'Unhandled order: ' . $ord );
		}
	}
}

/**
 * A type of database operation (see above).
 */
final class OperationType extends TypesafeEnum {

		static $SAVE;
		static $UPDATE_OR_CREATE;
		static $DELETE;
}

OperationType::setUp();

/**
 * Abstract base class for database operations.
 */
abstract class Operation {

	var $operationType;

	public function __construct( $operationType ) {
		$this->operationType = $operationType;
	}
}

/**
 * @see IPersistenceManager::queueSave()
 */
class SaveOperation extends Operation {

	var $obj;
	var $duplicatesCallback;

	public function __construct( $obj, $duplicatesCallback=null ) {

		$this->obj = $obj;
		$this->duplicatesCallback = $duplicatesCallback;
		parent::__construct( OperationType::$SAVE );
	}
}

/**
 * @see IPersistenceManager::queueSave()
 */
class UpdateOrCreateOperation extends Operation {

	var $obj;
	var $conditionFields;

	public function __construct( $obj, array $conditionFields ) {

		$this->obj = $obj;
		$this->conditionFields = $conditionFields;
		parent::__construct( OperationType::$UPDATE_OR_CREATE );
	}
}

/**
 * @see IPersistenceManager::queueDelete()
 */
class DeleteOperation extends Operation {

	var $type;
	var $conditions;

	public function __construct( $type, $conditions ) {

		$this->type = $type;
		$this->conditions = $conditions;
		parent::__construct( OperationType::$DELETE );
	}
}
