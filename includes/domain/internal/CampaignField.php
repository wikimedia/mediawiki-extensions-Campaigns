<?php

namespace Campaigns\Domain\Internal;

use Campaigns\Persistence\IField;
use Campaigns\TypesafeEnum;

/**
 * Enums representing Campaign fields.
 * @see Campaigns\Domain\ICampaign
 * @see Campaigns\Domain\Internal\Campaign
 */
final class CampaignField extends TypesafeEnum implements IField {

	static $ID;
	static $TIME_CREATED;
	static $TIME_ENDED;
	static $URL_KEY;
	static $NAME;
	static $HOME_PAGE_TITLE_TEXT;
}

CampaignField::setUp();