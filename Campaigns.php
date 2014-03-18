<?php
/**
 * Campaigns extension
 *
 * @ingroup Extensions
 *
 * @author S Page <spage@wikimedia.org>
 *
 * @license GPL v2 or later
 */

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Campaigns',
	'version' => '0.2.0',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Campaigns',
	'author' => 'S Page',
	'descriptionmsg' => 'campaigns-desc',
);

$dir = __DIR__;


// Messages

$wgMessagesDirs['Campaigns'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Campaigns'] = $dir . '/Campaigns.i18n.php';


// Autoload

$wgAutoloadClasses['Campaigns\Hooks'] = $dir . '/Campaigns.hooks.php';

$wgAutoloadClasses['Campaigns\Setup\Setup'] = $dir . '/includes/setup/Setup.php';
$wgAutoloadClasses['Campaigns\TypesafeEnum']  = $dir . '/includes/TypesafeEnum.php';
$wgAutoloadClasses['Campaigns\ITypesafeEnum']  = $dir . '/includes/ITypesafeEnum.php';
$wgAutoloadClasses['Campaigns\ConnectionType']  = $dir . '/includes/ConnectionType.php';

$wgAutoloadClasses['Campaigns\Domain\ICampaign']  = $dir . '/includes/domain/ICampaign.php';
$wgAutoloadClasses['Campaigns\Domain\IParticipation']  = $dir . '/includes/domain/IParticipation.php';
$wgAutoloadClasses['Campaigns\Domain\ICampaignRepository']  = $dir . '/includes/domain/ICampaignRepository.php';
$wgAutoloadClasses['Campaigns\Domain\IParticipationRepository']  = $dir . '/includes/domain/IParticipationRepository.php';
$wgAutoloadClasses['Campaigns\Domain\ITransactionManager']  = $dir . '/includes/domain/ITransactionManager.php';
$wgAutoloadClasses['Campaigns\Domain\CampaignValueNotUniqueException']  = $dir . '/includes/domain/CampaignValueNotUniqueException.php';
$wgAutoloadClasses['Campaigns\Domain\CampaignUrlKeyNotUniqueException']  = $dir . '/includes/domain/CampaignUrlKeyNotUniqueException.php';
$wgAutoloadClasses['Campaigns\Domain\CampaignNameNotUniqueException']  = $dir . '/includes/domain/CampaignNameNotUniqueException.php';

$wgAutoloadClasses['Campaigns\Domain\Internal\Campaign']  = $dir . '/includes/domain/internal/Campaign.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\Participation']  = $dir . '/includes/domain/internal/Participation.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\CampaignRepository']  = $dir . '/includes/domain/internal/CampaignRepository.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\ParticipationRepository']  = $dir . '/includes/domain/internal/ParticipationRepository.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\ICampaignFactory']  = $dir . '/includes/domain/internal/ICampaignFactory.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\CampaignFactory']  = $dir . '/includes/domain/internal/CampaignFactory.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\IParticipationFactory']  = $dir . '/includes/domain/internal/IParticipationFactory.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\ParticipationFactory']  = $dir . '/includes/domain/internal/ParticipationFactory.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\CampaignField']  = $dir . '/includes/domain/internal/CampaignField.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\ParticipationField']  = $dir . '/includes/domain/internal/ParticipationField.php';
$wgAutoloadClasses['Campaigns\Domain\Internal\TransactionManager']  = $dir . '/includes/domain/internal/TransactionManager.php';

$wgAutoloadClasses['Campaigns\Persistence\IPersistenceManager']  = $dir . '/includes/persistence/IPersistenceManager.php';
$wgAutoloadClasses['Campaigns\Persistence\Condition']  = $dir . '/includes/persistence/Condition.php';
$wgAutoloadClasses['Campaigns\Persistence\Operator']  = $dir . '/includes/persistence/Operator.php';
$wgAutoloadClasses['Campaigns\Persistence\Order']  = $dir . '/includes/persistence/Order.php';
$wgAutoloadClasses['Campaigns\Persistence\IField']  = $dir . '/includes/persistence/IField.php';
$wgAutoloadClasses['Campaigns\Persistence\RequiredFieldNotSetException']  = $dir . '/includes/persistence/RequiredFieldNotSetException.php';

$wgAutoloadClasses['Campaigns\Persistence\Internal\FieldDatatype']  = $dir . '/includes/persistence/internal/FieldDatatype.php';
$wgAutoloadClasses['Campaigns\Persistence\Internal\FieldOption']  = $dir . '/includes/persistence/internal/FieldOption.php';

$wgAutoloadClasses['Campaigns\Persistence\Internal\Db\DBPersistenceManager']  = $dir . '/includes/persistence/internal/db/DBPersistenceManager.php';
$wgAutoloadClasses['Campaigns\Persistence\Internal\Db\DBMapper']  = $dir . '/includes/persistence/internal/db/DBMapper.php';
$wgAutoloadClasses['Campaigns\Persistence\Internal\Db\IDBMapper']  = $dir . '/includes/persistence/internal/db/IDBMapper.php';

$wgAutoloadClasses['Campaigns\Services\ICampaignFromUrlKeyProvider']  = $dir . '/includes/services/ICampaignFromUrlKeyProvider.php';
$wgAutoloadClasses['Campaigns\Services\CampaignFromUrlKeyProvider']  = $dir . '/includes/services/CampaignFromUrlKeyProvider.php';
$wgAutoloadClasses['Campaigns\Services\IParticipantSetter']  = $dir . '/includes/services/IParticipantSetter.php';
$wgAutoloadClasses['Campaigns\Services\ParticipantSetter']  = $dir . '/includes/services/ParticipantSetter.php';

$wgAutoloadClasses['Campaigns\PHPUnit\TestHelper']  = $dir . '/tests/phpunit/TestHelper.php';


// Hooks

$wgHooks['UserCreateForm'][] = 'Campaigns\Hooks::onUserCreateForm';
$wgHooks['AddNewAccount'][] = 'Campaigns\Hooks::onAddNewAccount';
$wgHooks['UserLoginForm'][] = 'Campaigns\Hooks::onUserLoginForm';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'Campaigns\Hooks::onLoadExtensionSchemaUpdate';
$wgHooks['UnitTestsList'][] = 'Campaigns\Hooks::onUnitTestsList';


// Modules

$wgResourceModules['ext.campaigns'] = array(
	'scripts'       => 'ext.campaigns.js',
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'Campaigns/modules',
	'dependencies'  => array(
		'jquery.cookie',
	),
	'targets' => array( 'mobile', 'desktop' ),
);


// Dependency injection setup

$wgCampaignsDI = array();

$wgCampaignsDI['Campaigns\Domain\ICampaignRepository'] = array(
	'realization' => 'Campaigns\Domain\Internal\CampaignRepository',
);

$wgCampaignsDI['Campaigns\Domain\IParticipationRepository'] = array(
	'realization' => 'Campaigns\Domain\Internal\ParticipationRepository',
);

$wgCampaignsDI['Campaigns\Domain\ITransactionManager'] = array(
	'realization' => 'Campaigns\Domain\Internal\TransactionManager',
);

$wgCampaignsDI['Campaigns\Domain\Internal\ICampaignFactory'] = array(
	'realization' => 'Campaigns\Domain\Internal\CampaignFactory',
);

$wgCampaignsDI['Campaigns\Domain\Internal\IParticipationFactory'] = array(
	'realization' => 'Campaigns\Domain\Internal\ParticipationFactory',
);

$wgCampaignsDI['Campaigns\Persistence\IPersistenceManager'] = array(
	'realization' => 'Campaigns\Persistence\Internal\Db\DBPersistenceManager',
);

$wgCampaignsDI['Campaigns\Persistence\Internal\Db\IDBMapper'] = array(
	'realization' => 'Campaigns\Persistence\Internal\Db\DBMapper',
);

$wgCampaignsDI['Campaigns\Services\ICampaignFromUrlKeyProvider'] = array(
	'realization' => 'Campaigns\Services\CampaignFromUrlKeyProvider',
	'scope'       => 'singleton'
);

$wgCampaignsDI['Campaigns\Services\IParticipantSetter'] = array(
	'realization' => 'Campaigns\Services\ParticipantSetter',
	'scope'       => 'singleton'
);


// Database persistence layer setup

$wgCampaignsDBPersistence = array();

$wgCampaignsDBPersistence['Campaigns\Domain\ICampaign'] = array(
	'realization'   => 'Campaigns\Domain\Internal\Campaign',
	'table'         => 'campaigns_campaign',
	'column_prefix' => 'campaign',
	'field_class'   => 'Campaigns\Domain\Internal\CampaignField'
);

$wgCampaignsDBPersistence['Campaigns\Domain\IParticipation'] = array(
	'realization'   => 'Campaigns\Domain\Internal\Participation',
	'table'         => 'campaigns_participation',
	'column_prefix' => 'participation',
	'field_class'   => 'Campaigns\Domain\Internal\ParticipationField'
);
