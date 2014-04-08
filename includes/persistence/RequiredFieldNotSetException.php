<?php

namespace Campaigns\Persistence;

use MWException;

/**
 * Thrown when you try to save an object with a required field that's not set.
 */
class RequiredFieldNotSetException extends MWException { }