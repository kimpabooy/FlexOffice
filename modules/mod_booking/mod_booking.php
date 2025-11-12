<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_booking
 */
defined('_JEXEC') or die;

require_once __DIR__ . '/helper.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

$app = Factory::getApplication();
$input = $app->input;

// Handle booking POST
if ($input->getMethod() === 'POST' && $input->get('task') === 'book') {
    \Joomla\CMS\Session\Session::checkToken() or die('Invalid Token');

    $user = Factory::getUser();
    if (!$user->id) {
    $app->enqueueMessage('Du måste vara inloggad för att boka.', 'error');
    // Redirect back to referer when possible to avoid malformed REQUEST_URI issues
    $ref = $app->input->server->getString('HTTP_REFERER', '');
    $app->redirect($ref ? $ref : '/');
    }

    $periodId = $input->getInt('period_id');
    if (!$periodId) {
    $app->enqueueMessage('Ogiltig period.', 'error');
    $ref = $app->input->server->getString('HTTP_REFERER', '');
    $app->redirect($ref ? $ref : '/');
    }

    // Load period from DB to get desk_id, start_time, end_time
    if (method_exists(Factory::class, 'getContainer')) {
        // Use the DatabaseInterface::class constant so the container lookup matches registration
        $db = Factory::getContainer()->get(DatabaseInterface::class);
    } else {
        $db = Factory::getDbo();
    }

    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName('#__desk_availability_period'))
        ->where($db->quoteName('id') . ' = ' . (int) $periodId);
    $db->setQuery($query);

    try {
        $period = $db->loadObject();
    } catch (\RuntimeException $e) {
        $period = null;
    }

    if (empty($period) || empty($period->desk_id) || empty($period->start_time)) {
    $app->enqueueMessage('Ogiltig period.', 'error');
    $ref = $app->input->server->getString('HTTP_REFERER', '');
    $app->redirect($ref ? $ref : '/');
    }

    $helper = new ModBookingHelper;
    $result = $helper->saveBooking([
        'desk_id' => (int) $period->desk_id,
        'user_id' => (int) $user->id,
        'start_time' => $period->start_time,
        'end_time' => isset($period->end_time) ? $period->end_time : null,
    ]);

    if ($result) {
        $app->enqueueMessage('Bokningen sparades.');
    } else {
        $app->enqueueMessage('Kunde inte spara bokningen (kanske redan bokat).', 'error');
    }

    // Redirect to avoid form resubmission. Use referer when available to prevent routing/SEF issues.
    $ref = $app->input->server->getString('HTTP_REFERER', '');
    $app->redirect($ref ? $ref : '/');
}

$helper = new ModBookingHelper;
$available = $helper->getAvailable();
$bookings = $helper->getBookings();

// If this is an AJAX request for a different week, render only the calendar fragment
$isAjax = strtolower($app->input->server->getString('HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest' && $input->getString('start', '') !== '';
if ($isAjax) {
    // render the calendar fragment (calendar.php layout) and exit
    // Note: getLayoutPath returns a path; include to execute it
    $layoutPath = \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_booking', 'calendar');
    if ($layoutPath && file_exists($layoutPath)) {
        include $layoutPath;
    }
    // End execution for AJAX
    return;
}

require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_booking', $params->get('layout', 'default'));
