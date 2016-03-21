<?php
namespace Habanero\Framework\Service;

use Habanero\Framework\Config\Config;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Validator\Validation;

class TwigService implements ServiceInterface
{
    public function getService(Config $config)
    {
        $translator = new Translator('en');
        $translator->addLoader('xlf', new XliffFileLoader());
        $translator->addResource(
            'xlf',
            $config->getVendorPath().'/symfony/form/Resources/translations/validators.en.xlf',
            'en',
            'validators'
        );
        $translator->addResource(
            'xlf',
            $config->getVendorPath().'/symfony/validator/Resources/translations/validators.en.xlf',
            'en',
            'validators'
        );

        $params = [
            'debug' => $config['app']['debug'],
            'cache' => !$config['app']['dev'] ? $config->getViewCachePath() : false
        ];

        $viewRender = new \Twig_Environment(new \Twig_Loader_Filesystem([
            $config->getAppPath(),
            $config->getVendorPath().'/symfony/twig-bridge/Resources/views/Form'
        ]), $params);

        $formEngine = new TwigRendererEngine([
            'form_div_layout.html.twig'
        ]);
        $formEngine->setEnvironment($viewRender);

        if ($config['app']['debug']) {
            $viewRender->addExtension(new \Twig_Extension_Debug());
        }
        $viewRender->addExtension(new TranslationExtension($translator));
        $viewRender->addExtension(
            new FormExtension(new TwigRenderer($formEngine))
        );

        $function = new \Twig_Function('asset', function ($name = '') use ($config) {
            return $config->getBaseUrl().'/'.$name;
        });
        $viewRender->addFunction($function);

        return $viewRender;
    }
}