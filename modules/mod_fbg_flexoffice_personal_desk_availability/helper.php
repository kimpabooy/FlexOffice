<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

require_once __DIR__ . '/src/PersonalDeskService.php';

class ModFbgFlexofficePersonalDeskAvailabilityHelper
{
    /**
     * AJAX handler for com_ajax (method=save)
     */
    public static function saveAjax()
    {
        return PersonalDeskService::handleRequest(true);
    }
    
    /**
     * Delegate POST handling to the service layer.
     */
    public static function handleRequest()
    {
        return PersonalDeskService::handleRequest();
    }
    
    /**
     * Get list of desks for the current user.
     */
    public static function getDesks()
    {
        return PersonalDeskService::getDesks();
    }

    /**
     * Get recent bookings.
     */
    public static function getRecentAvails($isSuperUser = false)
    {
        return PersonalDeskService::getRecentAvails($isSuperUser);
    }
}
