<?php

namespace Campaigns;

/**
 * A connection type for a persistence store. When using DB persistence,
 * this maps to DB_MASTER and DB_SLAVE.
 */
final class ConnectionType extends TypesafeEnum {

	static $MASTER; /* For fully up-to-date data */
	static $SLAVE;  /* For data that may be laggy */
}

ConnectionType::setUp();