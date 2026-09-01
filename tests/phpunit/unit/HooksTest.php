<?php

namespace MediaWiki\Extension\Campaigns\Tests\Unit;

use MediaWiki\Extension\Campaigns\Hooks;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\Campaigns\Hooks
 */
class HooksTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::onAuthChangeFormFields
	 * @dataProvider provideLinkQueries
	 */
	public function testOnAuthChangeFormFields( string $linkQuery, string $expected ) {
		$hooks = new Hooks();
		$formDescriptor = [
			'createOrLogin' => [
				'linkQuery' => $linkQuery,
			],
		];

		$hooks->onAuthChangeFormFields( [], [], $formDescriptor, 'login' );

		$this->assertSame( $expected, $formDescriptor['createOrLogin']['linkQuery'] );
	}

	public static function provideLinkQueries() {
		return [
			'empty query appends campaign param' => [ '', 'campaign=loginCTA' ],
			'query without campaign appends param' => [ 'foo=bar', 'foo=bar&campaign=loginCTA' ],
			'query with campaign at start is unchanged' => [ 'campaign=existing', 'campaign=existing' ],
			'query with campaign later is unchanged' => [ 'foo=bar&campaign=existing', 'foo=bar&campaign=existing' ],
			'query with x-campaign' => [ 'x-campaign=existing', 'x-campaign=existing&campaign=loginCTA' ],
		];
	}
}
