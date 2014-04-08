<?php

namespace Campaigns\Persistence;

use Campaigns\TypesafeEnum;

/**
 * An operator for Conditions, which are used to select entities.
 */
final class Operator extends TypesafeEnum {

	static $EQUAL;
	static $GT_OR_EQUAL;
	static $LT_OR_EQUAL;
	static $IS_NULL;
	static $LIKE;
}

Operator::setUp();