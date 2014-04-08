<?php

namespace Campaigns\PHPUnit;

use MediaWikiTestCase;
use Campaigns\Persistence\Condition;
use Campaigns\Persistence\IField;
use Campaigns\Persistence\Operator;

class TestHelper {

	protected $testCase;

	public function __construct( MediaWikiTestCase $testCase ) {
		$this->testCase = $testCase;
	}

	/**
	 * Convenience method for performing assertions with a Condition
	 *
	 * @param Condition $c Should be a condition; this is tested
	 * @param IField $field
	 * @param Operator $operator
	 * @param mixed $val
	 */
	public function assertCondition( $c, IField $field, Operator $operator,
		$val) {

		// Test the type and contents of the condition
		$this->testCase->assertInstanceOf( 'Campaigns\Persistence\Condition', $c );
		$this->testCase->assertEquals( $field, $c->field );
		$this->testCase->assertEquals( $operator, $c->operator );
		$this->testCase->assertSame( $val, $c->value );
	}

	/**
	 * Capture an argument passed to a mock in the mock's with() method.
	 *
	 * @param $arg Reference to a variable for capturing the argument
	 */
	public function captureArg( &$arg ) {

		return $this->testCase->callback( function( $argToMock ) use ( &$arg ) {
			$arg = $argToMock;
			return true;
		} );
	}
}