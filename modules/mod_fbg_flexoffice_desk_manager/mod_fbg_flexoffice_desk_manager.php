<?php
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

require_once __DIR__ . '/helper_fbg_flexoffice_desk_manager.php';

// Process any POST actions (in helper)
ModFbgFlexofficeDeskManagerHelper::process();

// Load layout
require ModuleHelper::getLayoutPath('mod_fbg_flexoffice_desk_manager');
