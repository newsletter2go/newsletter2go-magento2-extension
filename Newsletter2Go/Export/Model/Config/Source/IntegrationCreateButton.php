<?php

namespace Newsletter2Go\Export\Model\Config\Source;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Newsletter2Go\Export\Model\PluginVersion;

class IntegrationCreateButton extends Field
{
    const N2G_CONNECT_URL = 'https://ui.newsletter2go.com/integrations/connect/MAG2/?version=<version>&token=<token>&url=<shop-url>&callback=<callback>&language=<language>';

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setType('button');
        $element->setData('value', 'Connect to Newsletter2Go');

        if (!$this->_scopeConfig->getValue('newsletter_go/general/token')) {
            $element->setData('disabled', 'true');
        }

        $pluginVersion = new PluginVersion();
        $shopUrl = $this->_storeManager->getStore()->getBaseUrl();
        $languageCode = explode('_', $this->_scopeConfig->getValue('general/locale/code', 'stores'));

        $replacements = [
            '<version>' => $pluginVersion->getShortVersion(),
            // <token> is replaced in javascript side
            '<shop-url>' => urlencode($shopUrl),
            '<callback>' => urlencode($shopUrl . 'n2gocallback/Callback/Index'),
            '<language>' => $languageCode[0],
        ];
        $url = str_replace(array_keys($replacements), array_values($replacements), self::N2G_CONNECT_URL);

        $element->setData('onclick', 'n2goConnect(' . json_encode($url) . ');');

        return $element->getElementHtml();
    }
}
