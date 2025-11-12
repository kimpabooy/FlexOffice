<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_manageStatus
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

// Hantera eventuell POST (insertion) och hämta data
ModManageStatusHelper::handleRequest();

$statusPeriods = ModManageStatusHelper::getStatusPeriods();
$desks = ModManageStatusHelper::getDesks();
$statusTypes = ModManageStatusHelper::getStatusTypes();

$layout = (isset($params) ? $params->get('layout', 'default') : 'default');
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_manageStatus', $layout);
