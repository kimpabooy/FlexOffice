<?php
/**
 * @package    Joomla.Site
 * @subpackage mod_getAllBookings
 */

defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

// Hämta data
$bookings = ModGetAllBookingsHelper::getBookings();

$layout = (isset($params) ? $params->get('layout', 'default') : 'default');
require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_getAllBookings', $layout);
