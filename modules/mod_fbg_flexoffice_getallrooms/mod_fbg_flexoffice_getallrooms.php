<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_fbg_flexoffice_getallrooms
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper_fbg_flexoffice_getallrooms.php';

// Hämta data
$rooms = ModFbgFlexofficeGetAllRoomsHelper::getRooms();

\Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_getallrooms', 'default');

$layout = (isset($params) ? $params->get('layout', 'default') : 'default');
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_getallrooms', $layout);
