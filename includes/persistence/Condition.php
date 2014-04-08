<?php

namespace Campaigns\Persistence;

/**
 * A condition for identifying entities via the IPersistenceManager.
 */
class Condition {

	var $field;
	var $operator;
	var $value;

	/**
	 * @param IField $field
	 * @param Operator $operator
	 * @param string $value
	 */
	public function __construct(
		IField $field, Operator $operator, $value=null ) {

		$this->field = $field;
		$this->operator = $operator;
		$this->value = $value;
	}
}