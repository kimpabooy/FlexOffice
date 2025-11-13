<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_fbg_flexoffice_getallbookings
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper_fbg_flexoffice_getallbookings.php';

// Hämta data
$bookings = ModFbgFlexofficeGetAllBookingsHelper::getBookings();

$layout = (isset($params) ? $params->get('layout', 'default') : 'default');
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_getallbookings', $layout);
