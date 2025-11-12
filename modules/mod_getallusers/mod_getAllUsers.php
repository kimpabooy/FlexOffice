<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_getAllUsers
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

// Hämta data
$users = ModGetAllUsersHelper::getUsers();

\Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_getAllUsers');

$layout = (isset($params) ? $params->get('layout', 'default') : 'default');
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_getAllUsers', $layout);
