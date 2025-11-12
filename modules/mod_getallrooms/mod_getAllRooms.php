<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_getAllRooms
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

// Hämta data
$rooms = ModGetAllRoomsHelper::getRooms();

\Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_getAllRooms', 'default');

$layout = (isset($params) ? $params->get('layout', 'default') : 'default');
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_getAllRooms', $layout);
