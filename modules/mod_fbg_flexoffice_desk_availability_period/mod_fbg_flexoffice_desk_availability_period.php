<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_fbg_flexoffice_desk_availability_period
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper_fbg_flexoffice_desk_availability_period.php';

$limit = isset($params) ? (int) $params->get('limit', 20) : 20;

// Hämta perioder och deskId via nya helper
$periods = ModFbgFlexofficeDeskAvailabilityPeriodHelper::getAvailabilityPeriods($limit);
$deskId = ModFbgFlexofficeDeskAvailabilityPeriodHelper::getDeskId();

$layout = isset($params) ? $params->get('layout', 'default') : 'default';
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_desk_availability_period', $layout);
