<?php

use Joomla\CMS\Helper\ModuleHelper;
use function YOOtheme\app;
use YOOtheme\View;
use YOOtheme\View\HtmlElement;

defined('_JEXEC') or die();

// Make module re-renderable
if (!isset($module->_builder)) {
    $module->_builder = $module->content;
}

$module->content = app(View::class)->builder($module->_builder, [
    'prefix' => "module-{$module->id}",
]);

if ($module->content && in_array($module->position, ['top', 'bottom'])) {
    $module->content = HtmlElement::tag(
        $params->get('module_tag', 'div'),
        ['id' => "module-{$module->id}", 'class' => 'builder'],
        $module->content,
    );
}

require ModuleHelper::getLayoutPath('mod_yootheme_builder', $params->get('layout', 'default'));
