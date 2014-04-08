<?php

namespace Campaigns\Persistence\Internal;

use \Campaigns\TypesafeEnum;

/**
 * Options available for fields. The lowercase version of each name
 * corresponds to an option available via annotations on entity members.
 */
final class FieldOption extends TypesafeEnum {

	static $ID;
	static $REQUIRED;
	static $UNIQUE;
}

FieldOption::setUp();