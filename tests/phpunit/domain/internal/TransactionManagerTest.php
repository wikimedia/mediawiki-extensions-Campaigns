<?php

namespace Campaigns\PHPUnit\Domain\Internal;

use MediaWikiTestCase;
use Campaigns\Domain\Internal\TransactionManager;

/**
 * @group Campaigns
 */
class TransactionManagerTest extends MediaWikiTestCase {

	public function testFlush() {

		$pm = $this->getMock( 'Campaigns\Persistence\IPersistenceManager' );
		$pm->expects( $this->once() )->method( 'flush' );
		$tm = new TransactionManager( $pm );
		$tm->flush();
	}
}