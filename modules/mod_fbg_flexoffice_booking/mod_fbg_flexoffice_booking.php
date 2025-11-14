<?php

/**
 * @package     Joomla.Site
 * @subpackage  mod_fbg_flexoffice_booking
 */
defined('_JEXEC') or die;

require_once __DIR__ . '/helper_fbg_flexoffice_booking.php';

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

$app = Factory::getApplication();
$session = Factory::getSession() ?: Factory::getApplication()->getSession(); // use existing session or get from application
$input = $app->input;

$user = Factory::getApplication()->getIdentity();
$isSuperUser = $user->authorise('core.admin');

// Handle booking cancellation
if ($input->getMethod() === 'POST' && $input->get('task') === 'cancel') {
    \Joomla\CMS\Session\Session::checkToken() or die('Invalid Token');

    if (!$user->id) {
        $session->set('mod_booking_message', ['text' => 'Du måste vara inloggad för att avboka.', 'type' => 'error']);
        $ref = $app->input->server->getString('HTTP_REFERER', '');
        header('Location: ' . ($ref ? $ref : '/'));
        exit;
    }

    $bookingId = $input->getInt('booking_id');
    if (!$bookingId) {
        $session->set('mod_booking_message', ['text' => 'Ogiltigt boknings-ID.', 'type' => 'error']);
        $ref = $app->input->server->getString('HTTP_REFERER', '');
        header('Location: ' . ($ref ? $ref : '/'));
        exit;
    }

    $helper = new ModFbgFlexofficeBookingHelper;
    if ($helper->cancelBooking($bookingId, $user->id, $isSuperUser)) {
        $session->set('mod_booking_message', ['text' => 'Bokningen har avbokats.', 'type' => 'message']);
    } else {
        $session->set('mod_booking_message', ['text' => 'Kunde inte avboka bokningen. Du kanske inte har behörighet eller så är den ogiltig.', 'type' => 'error']);
    }

    $ref = $app->input->server->getString('HTTP_REFERER', '');
    header('Location: ' . ($ref ? $ref : '/'));
    exit;
}

// Handle booking POST
if ($input->getMethod() === 'POST' && $input->get('task') === 'book') {
    \Joomla\CMS\Session\Session::checkToken() or die('Invalid Token');

    if (!$user->id) {
        $session->set('mod_booking_message', ['text' => 'Du måste vara inloggad för att boka.', 'type' => 'error']);
        // Redirect back to referer when possible to avoid malformed REQUEST_URI issues
        $ref = $app->input->server->getString('HTTP_REFERER', '');
        header('Location: ' . ($ref ? $ref : '/'));
        exit;
    }

    $periodId = $input->getInt('period_id');
    if (!$periodId) {
        $session->set('mod_booking_message', ['text' => 'Ogiltig period.', 'type' => 'error']);
        $ref = $app->input->server->getString('HTTP_REFERER', '');
        header('Location: ' . ($ref ? $ref : '/'));
        exit;
    }

    // Load period from DB to get desk_id, start_time, end_time
    if (method_exists(Factory::class, 'getContainer')) {
        // Use the DatabaseInterface::class constant so the container lookup matches registration
        $db = Factory::getContainer()->get(DatabaseInterface::class);
    } else {
        $db = \Joomla\CMS\Factory::getContainer()->get(DatabaseInterface::class);
    }

    $query = $db->getQuery(true)
        ->select('*')
        ->from($db->quoteName('#__fbgflexoffice_desk_availability_period'))
        ->where($db->quoteName('id') . ' = ' . (int) $periodId);
    $db->setQuery($query);

    try {
        $period = $db->loadObject();
    } catch (\RuntimeException $e) {
        $period = null;
    }

    if (empty($period) || empty($period->desk_id) || empty($period->start_time)) {
        $session->set('mod_booking_message', ['text' => 'Ogiltig period.', 'type' => 'error']);
        $ref = $app->input->server->getString('HTTP_REFERER', '');
        header('Location: ' . ($ref ? $ref : '/'));
        exit;
    }

    $helper = new ModFbgFlexofficeBookingHelper;
    $result = $helper->saveBooking([
        'desk_id' => (int) $period->desk_id,
        'user_id' => (int) $user->id,
        'start_time' => $period->start_time,
        'end_time' => isset($period->end_time) ? $period->end_time : null,
    ]);

    if ($result) {
        $session->set('mod_booking_message', ['text' => 'Bokningen sparades.', 'type' => 'message']);
    } else {
        $session->set('mod_booking_message', ['text' => 'Kunde inte spara bokningen (kanske redan bokat).', 'type' => 'error']);
    }

    // Redirect to avoid form resubmission. Use referer when available to prevent routing/SEF issues.
    $ref = $app->input->server->getString('HTTP_REFERER', '');
    header('Location: ' . ($ref ? $ref : '/'));
    exit;
}

// Fetch data for the view
$user = \Joomla\CMS\Factory::getApplication()->getIdentity();
$isSuperUser = $user->authorise('core.admin');
// Hämta tillgängliga perioder och bokningar via nya helper
$available = ModFbgFlexofficeBookingHelper::getAvailable();
$bookings = ModFbgFlexofficeBookingHelper::getBookings($isSuperUser);
$personalBookings = ModFbgFlexofficeBookingHelper::getPersonalBookings();


// Visa eventuellt sparat systemmeddelande från sessionen
$bookingMessage = $session->get('mod_booking_message', null);
if ($bookingMessage && !empty($bookingMessage['text'])) {
    $app->enqueueMessage($bookingMessage['text'], $bookingMessage['type']);
    $session->set('mod_booking_message', null);
}

$layout = $params->get('layout', 'default');

// If this is an AJAX request for a different week, render only the calendar fragment
$isAjax = strtolower($app->input->server->getString('HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest' && $input->getString('start', '') !== '';
if ($isAjax) {
    // render the calendar fragment (calendar.php layout) and exit
    // Note: getLayoutPath returns a path; include to execute it
    $layoutPath = \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_booking', 'calendar');
    if ($layoutPath && file_exists($layoutPath)) {
        include $layoutPath;
    }
    // End execution for AJAX
    return;
}

require \Joomla\CMS\Helper\ModuleHelper::getLayoutPath('mod_fbg_flexoffice_booking', $layout);
