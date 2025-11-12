<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

require_once __DIR__ . '/src/PersonalDeskService.php';

class ModPersonalDeskAvailabilityHelper
{
    /**
     * Delegate POST handling to the service layer.
     */
    public static function handleRequest()
    {
        return PersonalDeskService::handleRequest();
    }

    public static function getDesks()
    {
        return PersonalDeskService::getDesks();
    }

    public static function getRecentAvails()
    {
        return PersonalDeskService::getRecentAvails();
    }
}
