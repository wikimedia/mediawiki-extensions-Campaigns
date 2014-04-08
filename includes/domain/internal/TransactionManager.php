<?php

namespace Campaigns\Domain\Internal;

use Campaigns\Domain\ITransactionManager;
use Campaigns\Persistence\IPersistenceManager;

/**
 * Implementation of ITransactionManager
 * @see ITransactionManager
 * @see IPersistenceManager
 */
class TransactionManager implements ITransactionManager {

	protected $pm;

	public function __construct( IPersistenceManager $pm ) {
		$this->pm = $pm;
	}

	/**
	 * @see ITransactionManager::flush()
	 */
	public function flush() {
		$this->pm->flush();
	}
}