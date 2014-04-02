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


// Hooks

$wgHooks['UserCreateForm'][] = 'Campaigns\Hooks::onUserCreateForm';
$wgHooks['AddNewAccount'][] = 'Campaigns\Hooks::onAddNewAccount';
$wgHooks['UserLoginForm'][] = 'Campaigns\Hooks::onUserLoginForm';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'Campaigns\Hooks::onLoadExtensionSchemaUpdate';


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
