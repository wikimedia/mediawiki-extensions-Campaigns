<?php

namespace Campaigns\Persistence;

use Campaigns\TypesafeEnum;

/**
 * An order. Used in requests for multiple entities.
 */
final class Order extends TypesafeEnum {

	static $ASCENDING;
	static $DESCENDING;
}

Order::setUp();