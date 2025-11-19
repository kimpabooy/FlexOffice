<?php
// Kopia av helper_fbg_flexoffice_personal_desk_availability.php för com_ajax
// Se till att klassnamnet och metoderna är identiska

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

    public static function getDesks()
    {
        return PersonalDeskService::getDesks();
    }

    public static function getRecentAvails($isSuperUser = false)
    {
        return PersonalDeskService::getRecentAvails($isSuperUser);
    }
}
