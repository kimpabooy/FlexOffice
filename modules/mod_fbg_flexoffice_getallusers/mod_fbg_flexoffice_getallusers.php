<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_fbg_flexoffice_getallusers
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper_fbg_flexoffice_getallusers.php';

// Hämta data
$users = ModFbgFlexofficeGetAllUsersHelper::getUsers();

\Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_getallusers');

$layout = (isset($params) ? $params->get('layout', 'default') : 'default');
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_getallusers', $layout);
