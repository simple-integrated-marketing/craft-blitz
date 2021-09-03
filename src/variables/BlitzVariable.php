<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\variables;

use Craft;
use craft\helpers\Template;
use craft\web\View;
use putyourlightson\blitz\Blitz;
use putyourlightson\blitz\models\CacheOptionsModel;
use Twig\Markup;
use yii\web\NotFoundHttpException;

class BlitzVariable
{
    /**
     * @var int
     */
    private $_injected = 0;

    // Public Methods
    // =========================================================================

    /**
     * Returns script to get the output of a URI.
     *
     * @param string $uri
     * @param array $params
     * @param bool $unique
     * @param int $priority
     * @return Markup
     */
    public function getUri(string $uri, array $params = [], bool $unique = false, int $priority = 99): Markup
    {
        $params['no-cache'] = 1;

        return $this->_getScript($uri, $params, $unique, $priority);
    }

    /**
     * Returns script to get the output of a template.
     *
     * @param string $template
     * @param array $params
     * @param bool $unique
     * @param int $priority
     * @return Markup
     */
    public function getTemplate(string $template, array $params = [], bool $unique = false, int $priority = 99): Markup
    {
        // Ensure template exists
        if (!Craft::$app->getView()->resolveTemplate($template)) {
            throw new NotFoundHttpException('Template not found: '.$template);
        }

        $uri = '/'.Craft::$app->getConfig()->getGeneral()->actionTrigger.'/blitz/templates/get';

        // Hash the template
        $template = Craft::$app->getSecurity()->hashData($template);

        // Add template and passed in params to the params
        $params = [
            'template' => $template,
            'params' => $params,
            'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
        ];

        return $this->_getScript($uri, $params, $unique, $priority);
    }

    /**
     * Returns a script to get a CSRF input field.
     *
     * @return Markup
     */
    public function csrfInput(): Markup
    {
        $uri = '/'.Craft::$app->getConfig()->getGeneral()->actionTrigger.'/blitz/csrf/input';

        return $this->_getScript($uri, []);
    }

    /**
     * Returns a script to get the CSRF param.
     *
     * @return Markup
     */
    public function csrfParam(): Markup
    {
        $uri = '/'.Craft::$app->getConfig()->getGeneral()->actionTrigger.'/blitz/csrf/param';

        return $this->_getScript($uri, []);
    }

    /**
     * Returns a script to get a CSRF token.
     *
     * @return Markup
     */
    public function csrfToken(): Markup
    {
        $uri = '/'.Craft::$app->getConfig()->getGeneral()->actionTrigger.'/blitz/csrf/token';

        return $this->_getScript($uri, []);
    }

    /**
     * Returns options for the current page cache, first setting any parameters provided.
     *
     * @param array $params
     *
     * @return CacheOptionsModel
     */
    public function options(array $params = []): CacheOptionsModel
    {
        $options = Blitz::$plugin->generateCache->options;

        if (isset($params['cacheDuration'])) {
            $options->cacheDuration($params['cacheDuration']);
        }

        $options->setAttributes($params, false);

        if ($options->validate()) {
            Blitz::$plugin->generateCache->options = $options;
        }

        return Blitz::$plugin->generateCache->options;
    }

    /**
     * Returns whether the `@web` alias is used in any site's base URL.
     *
     * @return bool
     */
    public static function getWebAliasExists(): bool
    {
        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($sites as $site) {
            if (strpos($site->baseUrl, '@web') !== false) {
                return true;
            }
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a script to inject the output of a URI into a div.
     *
     * @param string $uri
     * @param array $params
     * @param bool $unique
     * @param int $priority
     * @return Markup
     */
    private function _getScript(string $uri, array $params = [], bool $unique = false, int $priority = 99): Markup
    {
        $view = Craft::$app->getView();
        $js = '';

        if ($this->_injected === 0) {
            $blitzInjectScript = Craft::getAlias('@putyourlightson/blitz/resources/js/blitzInjectScript.js');

            if (file_exists($blitzInjectScript)) {
                $js = file_get_contents($blitzInjectScript);
                $js = str_replace('{injectScriptEvent}', Blitz::$plugin->settings->injectScriptEvent, $js);
            }
        }

        $view->registerJs($js, View::POS_END);

        $this->_injected++;
        $id = $this->_injected;

        $data = [
            'id' => $id,
            'uri' => $uri,
            'params' => http_build_query($params),
            'unique' => $unique,
            'priority' => $priority,
        ];

        foreach ($data as $key => &$value) {
            $value = 'data-blitz-'.$key.'="'.$value.'"';
        }

        $output = '<span class="blitz-inject" id="blitz-inject-'.$id.'" '.implode(' ', $data).'></span>';

        return Template::raw($output);
    }
}
