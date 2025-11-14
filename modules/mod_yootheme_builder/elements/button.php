<?php

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

class JFormFieldButton extends FormField
{
    protected $type = 'Button';

    public function getInput()
    {
        if (!($templ = $this->getTemplate())) {
            return '<p id="alert-customizer" class="alert alert-error">Please create a YOOtheme <a href="index.php?option=com_templates">template style</a>.</p>';
        }

        $uri = Uri::getInstance();
        if ($uri->getVar('tmpl') === 'component') {
            return '';
        }

        $buttonText = Text::_('Open Builder');
        $warningText = Text::_('Please save the module first.');
        $href = sprintf(
            'index.php?option=com_ajax&p=customizer&templateStyle=%s&format=html&section=joomla-modules&return=%s',
            $templ->id,
            urlencode($uri),
        );

        return "<a class=\"tm-button\" href=\"{$href}\">{$buttonText}</a>
                <script>
                    document.body.addEventListener('click', function (e) {
                        if (e.target.matches('.tm-button') && !(new URL(document.location)).searchParams.has('id')) {
                            e.preventDefault();
                            window.alert('{$warningText}');
                        }
                    });
                </script>
                <style>
                    .tm-button {
                        display: block;
                        box-sizing: border-box;
                        width: 280px;
                        max-width: 100%;
                        padding: 20px 30px;
                        border-radius: 2px;
                        background: linear-gradient(140deg, #FE67D4, #4956E3);
                        box-shadow: inset 0 0 1px 0 rgba(0,0,0,0.5);
                        line-height: 10px;
                        vertical-align: middle;
                        color: #fff !important;
                        font-size: 11px;
                        font-weight: bold;
                        font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;
                        text-align: center;
                        text-decoration: none !important;
                        text-transform: uppercase;
                        letter-spacing: 2px;
                        -webkit-font-smoothing: antialiased;
                    }\
                </style>";
    }

    protected function getTemplate()
    {
        $templs = Factory::getDbo()
            ->setQuery(
                'SELECT id, params from #__template_styles WHERE client_id = 0 ORDER BY home DESC',
            )
            ->loadObjectList();

        foreach ($templs as $templ) {
            $params = new Registry($templ->params);

            if ($params->get('yootheme')) {
                return $templ;
            }
        }
    }
}
