<?php

/**
 * @package    Joomla.Site
 * @subpackage mod_fbg_flexoffice_personal_desk_availability
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper_fbg_flexoffice_personal_desk_availability.php';

// Hantera eventuell POST
ModFbgFlexofficePersonalDeskAvailabilityHelper::handleRequest();

// Hämta data för vyn
$user = \Joomla\CMS\Factory::getApplication()->getIdentity();
$isSuperUser = $user->authorise('core.admin');
$desks = ModFbgFlexofficePersonalDeskAvailabilityHelper::getDesks();
$periods = ModFbgFlexofficePersonalDeskAvailabilityHelper::getRecentAvails($isSuperUser);

$layout = isset($params) ? $params->get('layout', 'default') : 'default';
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_personal_desk_availability', $layout);
