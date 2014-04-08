<?php

namespace Campaigns\Persistence\Internal;

use Campaigns\TypesafeEnum;

/**
 * Datatypes available for fields. The lowercase version of each name
 * corresponds to a type available in entity member annotations.
 */
final class FieldDatatype extends TypesafeEnum {

	static $INT;
	static $STRING;
	static $BOOLEAN;
	static $MW_TS; // Mediawiki timestamp
}

FieldDatatype::setUp();
