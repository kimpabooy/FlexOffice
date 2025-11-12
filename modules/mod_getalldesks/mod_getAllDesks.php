<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_getAllDesks
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

// Hämta data
$desks = ModGetAllDesksHelper::getDesks();

$layout = (isset($params) ? $params->get('layout', 'default') : 'default');
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_getAllDesks', $layout);
