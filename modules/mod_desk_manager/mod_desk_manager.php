<?php
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

require_once __DIR__ . '/helper.php';

// Process any POST actions (in helper)
ModDeskManagerHelper::process();

// Load layout
require ModuleHelper::getLayoutPath('mod_desk_manager');
