<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_desk_availability_period
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

$limit = isset($params) ? (int) $params->get('limit', 20) : 20;

$periods = ModDeskAvailabilityHelper::getAvailabilityPeriods($limit);
$deskId = ModDeskAvailabilityHelper::getDeskId();

$layout = isset($params) ? $params->get('layout', 'default') : 'default';
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_desk_availability_period', $layout);
