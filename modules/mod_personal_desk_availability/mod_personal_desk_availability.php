<?php

/**
 * @package    Joomla.Site
 * @subpackage mod_personal_desk_availability
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

// Hantera eventuell POST
ModPersonalDeskAvailabilityHelper::handleRequest();

// Hämta data för vyn
$user = \Joomla\CMS\Factory::getUser();
$isSuperUser = $user->authorise('core.admin');
$desks = ModPersonalDeskAvailabilityHelper::getDesks();
$periods = ModPersonalDeskAvailabilityHelper::getRecentAvails($isSuperUser);

$layout = isset($params) ? $params->get('layout', 'default') : 'default';
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_personal_desk_availability', $layout);
