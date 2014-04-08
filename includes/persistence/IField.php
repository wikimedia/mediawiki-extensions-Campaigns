<?php

namespace Campaigns\Persistence;

use Campaigns\ITypesafeEnum;

/**
 * An entity field. A field enum type that implements this interface should be
 * declared for all entities.
 */
interface IField extends ITypesafeEnum { }