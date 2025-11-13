<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_fbg_flexoffice_getalldesks
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper_fbg_flexoffice_getalldesks.php';

// Hämta data
$desks = ModFbgFlexofficeGetAllDesksHelper::getDesks();

$layout = (isset($params) ? $params->get('layout', 'default') : 'default');
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_getalldesks', $layout);
