<?php
namespace Campaigns\Persistence;

use Campaigns\ConnectionType;
use Campaigns\Persistence\Order;
use Campaigns\Persistence\IField;

/**
 * This interface provides facilities for a persistence layer based on the
 * data mapper pattern.
 *
 * To set it up:
 * - Define entity classes with properties for the entities' fields.
 * - Add PHPDoc annotations on the entities' properties to specify datatype
 *   (using var) or any of the following options: id, required or unique. (See
 *   Campaigns\Domain\Internal\Campaign and
 *   Campaigns\Domain\Internal\Participation for examples.)
 * - Define enums for fields using TypesafeEnum and IField (see
 *   Campaigns\Domain\Internal\CampaignField and
 *   Campaigns\Domain\Internal\ParticipationField). The enum names must
 *   be in UPPERCASE_UNDERSCORE format and must correspond to the names of the
 *   entities' properties in lowerCamelCase format.
 * - Additional setup specific to the underlying persistence store will be
 *   required. (For now, only a database-based implementation is available. See
 *   Persistence\Internal\Db\DBPersistenceManager for details.)
 *
 * You can then queue operations on your entities. Call flush() to perform
 * the queued operations. Once entities have been persisted, you can use the
 * methods here to query and retrieve them. See
 * Campaigns\Domain\Internal\CampaignRepository and
 * Campaigns\Domain\Internal\ParticipationRepository for examples.
 */
interface IPersistenceManager {

	/**
	 * Queue an operation to save changes to $obj or insert it in the
	 * persistence store. The operation will be performed when flush() is
	 * called.
	 *
	 * If $obj is new, it will be given an ID when flush() is called.
	 *
	 * Note that if a new object is to be used in more operations (other than
	 * saving updates to its own fields), it must be saved and flushed before
	 * additional operations are queued.
	 *
	 * Uniqueness constraints may be defined on fields or groups of fields using
	 * annotations on entity fields. If, when flush() is called, the underlying
	 * persistence store does not allow the save due to duplicate values on
	 * indexes, $duplicatesCallback will be called with two arguments: $obj
	 * (the same object sent here) and $uniqueIndex (the string name of the
	 * unique index with the duplicate value).
	 *
	 * (TODO: If there are duplicate values on several unique indexes at once,
	 * still only one unique index name will be sent.)
	 *
	 * If, when flush() is called, the save fails due to duplicate values and no
	 * $duplicatesCallback was provided, an exception will be thrown.
	 *
	 * @param mixed $obj An entity; must be of a class registered as a
	 *   an entity realization class
	 * @param callback $duplicatesCallback
	 */
	public function queueSave( $obj, $duplicatesCallback=null );

	/**
	 * Queue an update-or-create operation. The operation will be performed
	 * when flush() is called.
	 *
	 * This operation entails searching the persistence store for an entity
	 * similar to $obj, and if one is found, updating it with the values in
	 * $obj. If no such entity is found, $obj will be inserted into the
	 * persistence store.
	 *
	 * In the database-based implementation of this interface, this operation
	 * corresponds to DatabaseBase::upsert() (a wrapper for INSERT ON DUPLICATE
	 * KEY UPDATE).
	 *
	 * The fields in $conditionFields identify which of $obj's fields will
	 * be used to search the store for a similar entity. Together they should
	 * uniquely identify exactly zero or one entities. The persistence store
	 * should have a uniqueness constraint on those fields.
	 *
	 * @param mixed $obj An entity; must be of a class registered as a
	 *   an entity realization class
	 * @param array $conditionFields An array of IFields
	 */
	public function queueUpdateOrCreate( $obj, array $conditionFields );

	/**
	 * Queue a delete operation. The operation will be performed when flush() is
	 * called.
	 *
	 * @param string $type The type of entity to delete
	 * @param array $conditions An array of Conditions that identify the
	 *   entities to delete.
	 */
	public function queueDelete( $type, $conditions );

	/**
	 * Perform all queued operations, in the order in which they were queued.
	 */
	public function flush();

	/**
	 * Is there at least one entity of this $type that fulfills these
	 * $conditions?
	 *
	 * @param string $type
	 * @param array $conditions An array of Conditions
	 * @return boolean
	 */
	public function existsWithConditions( $type, $conditions );

	/**
	 * Get a single entity of this $type, identified by $conditions.
	 *
	 * @param string $type
	 *
	 * @param Condition|array $conditions A single Condition or an array of them
	 *
	 * @param ConnectionType $connectionType Set to MASTER for data that is
	 *   guaranteed to be the latest. Default is SLAVE, which may provide
	 *   slightly laggy data. (In the DB implementation, these map to
	 *   DB_MASTER and DB_SLAVE.)
	 *
	 * @return mixed $obj
	 */
	public function getOne( $type, $conditions,
			ConnectionType $connectionType=null );

	/**
	 * Get a single entity of this $type with this $id.
	 *
	 * @param string $type
	 *
	 * @param mixed $id
	 *
	 * @param ConnectionType $connectionType Set to MASTER for data that is
	 *   guaranteed to be the latest. Default is SLAVE, which may provide
	 *   slightly laggy data. (In the DB implementation, these map to
	 *   DB_MASTER and DB_SLAVE.)
	 *
	 * @return mixed $obj
	 */
	public function getOneById( $type, $id,
		ConnectionType $connectionType=null );

	/**
	 * Count the entities of this $type, identified by $conditions. If
	 * $conditions is null, count all the entities of this $type.
	 *
	 * @param string $type
	 *
	 * @param array $conditions An array of Conditions
	 *
	 * @return int
	 */
	public function count( $type, $conditions=null );

	/**
	 * Get an array of entities of this $type, identified by $conditions,
	 * ordered by $orderByField in this $order.
	 *
	 * At most $fetchLimit entities will be returned. If $fetchLimit is greater
	 * than the limit indicated for this $type by getMaxFetchLimit(), an
	 * exception will be thrown. If $fetchLimit is null, then at most the number
	 * indicated by getMaxFetchLimit() for this $type will be returned.
	 *
	 * If not all the entities of this $type that meet the $conditions are
	 * returned, $continueKey will be set to a string key. To fetch the next
	 * block of entities, call this method again with that key, and the other
	 * parameters unchanged. When the last block of entities has been returned,
	 * $continueKey will be set to null. To start with the first block
	 * of entities, call this method with $continueKey set to null.
	 *
	 * @param string $type The type of entity to get
	 *
	 * @param Condition|array $conditions A single Condition or an array of them
	 *
	 * @param int $fetchLimit Maximum number of entities to return in a single
	 *   call; must be less than getMaxFetchLimit() for this $type, or null
	 *
	 * @param IField $orderByField Order the list using the values in this field
	 *
	 * @param Order $order
	 * @param string $continueKey
	 */
	public function get( $type, IField $orderByField, Order $order,
		$conditions=null, $fetchLimit=null, &$continueKey=null );

	/**
	 * The maximum number of entities that may be retrieved at once using
	 * get() with this $type. If that method is called with a higher
	 * $fetchLimit, an exception will be thrown.
	 *
	 * @param string $type
	 * @return int
	 */
	public function getMaxFetchLimit( $type );

	/**
	 * Get a string that may be used as a wildcard in conditions with
	 * Operator::$LIKE.
	 *
	 * @return string
	 */
	public function getAnyStringForLikeOperator();
}