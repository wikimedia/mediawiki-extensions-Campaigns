<?php

namespace Campaigns\Domain\Internal;

use Campaigns\Persistence\IField;
use Campaigns\TypesafeEnum;

/**
 * Enums representing Participation fields.
 * @see Campaigns\Domain\IParticipation
 * @see Campaigns\Domain\Internal\Participation
 */
final class ParticipationField extends TypesafeEnum implements IField {

	static $ID;
	static $USER_ID;
	static $CAMPAIGN_ID;
	static $TIME_JOINED;
	static $ORGANIZER;
}

ParticipationField::setUp();