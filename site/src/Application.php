<?php

namespace Site;

use \Symfony\Component\HttpFoundation\JsonResponse;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Yaml;

class Application extends \Silex\Application {

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        // Init config
        $this->_initConfig();

        // Services
        $this->register(new \Silex\Provider\LocaleServiceProvider());

        if($this['config']['langs'])
        {
            $this->register(new \Silex\Provider\TranslationServiceProvider(), array(
                'locale_fallbacks' => array($this['config']['langs']['default']),
            ));
        }
        $this->register(new \Silex\Provider\VarDumperServiceProvider());
        $this->register(new \Silex\Provider\RoutingServiceProvider());
        $this->register(new \Silex\Provider\TwigServiceProvider(),array(
            'twig.path' => SITE_PATH.'/views'
        ));
        $this->register(new \Silex\Provider\HttpCacheServiceProvider(), array(
            'http_cache.cache_dir' => ROOT_PATH.'/cache/http/',
        ));

        // Init everything
        $this->_initAjax();
        $this->_initLangs();
        $this->_initTemplating();
        $this->_initCache();
        $this->_initMiddleware();
        $this->_initRouting();
    }

    private function _initConfig()
    {
        $parser = new Yaml\Parser();

        // Load and define environment
        $environments = $parser->parse(file_get_contents(SITE_PATH.'/config/environments.yml'));
        $domain       = $_SERVER['HTTP_HOST'];
        $environment  = 'prod'; // Default

        foreach($environments as $_environment => $_domains)
        {
            if(in_array($domain, $_domains))
            {
                $environment = $_environment;
                break;
            }
        }

        // Load and define config
        $configs = $parser->parse(file_get_contents(SITE_PATH.'/config/configs.yml'));
        $config  = $configs[$environment];

        $this['config'] = $config;
        $this['debug'] = $this['config']['debug'];
    }

    private function _initAjax()
    {
        $this['ajax'] = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    private function _initTemplating()
    {
        // Path function
        $app = $this;
        $this['twig']->addFunction(
            'path',
            new \Twig_Function_Function(
                function() use ($app)
                {
                    // Get arguments
                    $arguments = func_get_args();

                    // Full argument
                    $full = false;
                    if(count($arguments))
                        if(is_bool($arguments[count($arguments) - 1]))
                            $full = array_pop($arguments);

                    // Path
                    $path = '';
                    if(count($arguments))
                        if(is_string($arguments[count($arguments) - 1]))
                            $path = trim(array_pop($arguments),'/');

                    // Build path
                    $base_path = trim($app['request_stack']->getCurrentRequest()->getBasePath(),'/');
                    $return = $base_path . '/' . $path;

                    // Full
                    if($full)
                    {
                        $domain = 'http' . (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . ($_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '');
                        $return = $domain . $return;
                    }

                    return $return;
                }
            )
        );

        // Route function
        $app = $this;
        $this['twig']->addFunction(
            'route',
            new \Twig_Function_Function(
                function() use ($app)
                {
                    // Get arguments
                    $arguments = func_get_args();

                    // Langs
                    $has_lang = $app['config']['langs'];

                    // Full argument
                    $full = false;
                    if(count($arguments))
                        if(is_bool($arguments[count($arguments) - 1]))
                            $full = array_pop($arguments);

                    // Lang argument
                    $lang = null;
                    if($has_lang)
                    {
                        $all_langs = $app['config']['langs']['all'];

                        if(count($arguments) > 1)
                        {
                            if(is_string($arguments[count($arguments) - 1]))
                            {
                                if(in_array($arguments[count($arguments) - 1], $all_langs))
                                    $lang = trim(array_pop($arguments));
                                else
                                    array_pop($arguments);
                            }
                        }

                        if(!$lang)
                            $lang = $app['translator']->getLocale();
                    }

                    // Params
                    $params = array();
                    if(count($arguments))
                        if(is_array($arguments[count($arguments) - 1]))
                            $params = array_pop($arguments);

                    if($lang)
                        $params['_locale'] = $lang;

                    // Path
                    $route = '';
                    if(count($arguments))
                        if(is_string($arguments[count($arguments) - 1]))
                            $route = trim(array_pop($arguments),'/');

                    if($lang)
                        $route .= '_' . $lang;

                    // Build URL
                    $url = $app['url_generator']->generate($route,$params);

                    // Full
                    if($full)
                    {
                        $domain = 'http' . (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . ($_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '');
                        $url = $domain . $url;
                    }

                    return $url;
                }
            )
        );

        // Data
        $this->data      = array();
        $this->ajax_data = array();
    }

    private function _initLangs()
    {
        $has_lang = $this['config']['langs'];

        // No lang
        if(!$has_lang)
            return;

        // All langs
        $all_langs = $this['config']['langs']['all'];

        // Add translator
        $this->extend('translator', function($translator, $app) use ($all_langs)
        {
            $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());

            foreach($all_langs as $_lang)
                $translator->addResource('yaml', SITE_PATH.'/config/locales/'.$_lang.'.yml',$_lang);

            return $translator;
        });
    }

    private function _initCache()
    {
        $this->get('debug/empty-http-cache',function(Application $app)
        {
            function empty_dir($dir)
            {
                if(is_dir($dir))
                {
                    $objects = scandir($dir);
                    foreach($objects as $object)
                    {
                        if($object != '.' && $object != '..')
                        {
                            if(filetype($dir.'/'.$object) == 'dir')
                            {
                                empty_dir($dir.'/'.$object);
                                rmdir($dir.'/'.$object);
                            }
                            else
                            {
                                unlink($dir.'/'.$object);
                            }
                        }
                    }
                    reset($objects);
                }
            }
            empty_dir(__DIR__.'/../cache/http/');

            die('ok');
        });
    }

    private function _initMiddleware()
    {
        $this->before(\Site\Middlewares\Defaults::before($this));
        $this->after(\Site\Middlewares\Defaults::after($this));
        $this->finish(\Site\Middlewares\Defaults::finish($this));
    }

    private function _initRouting()
    {
        $parser = new Yaml\Parser();

        // Load and define environment
        $routes = $parser->parse(file_get_contents(SITE_PATH.'/config/routes.yml'));

        // Each route
        foreach($routes as $_route_name => $_route_params)
        {
            // Default protocol
            if(empty($_route_params['protocol']))
                $_route_params['protocol'] = 'get';

            // Langs
            if($this['config']['langs'])
                $all_langs = $this['config']['langs']['all'];
            else
                $all_langs = array('');

            // Each lang
            foreach($all_langs as $_lang)
            {
                $route_path = $_route_params['path'];

                if(!empty($_lang))
                    $route_path = '{_locale}' . $route_path;

                // Translate
                if(!empty($_lang))
                {
                    $matches = array();
                    preg_match_all("/\[([a-zA-Z0-9._-]+)]/", $route_path, $matches);

                    foreach($matches[1] as $_match)
                    {
                        $translation = $this['translator']->trans($_match,array(),'messages',$_lang);
                        $route_path = str_replace('['.$_match.']', $translation, $route_path);
                    }
                }

                $route = $this->{$_route_params['protocol']}(
                    $route_path,
                    array(
                        $_route_params['class'],
                        $_route_params['method']
                    )
                );

                // Bind
                $route->bind($_route_name . (!empty($_lang) ? '_' . $_lang : ''));

                // Middleware
                if(!empty($_route_params['middlewares']))
                {
                    foreach($_route_params['middlewares'] as $_middleware_name => $_middleware_params)
                    {
                        $callback = call_user_func(array($_middleware_params['class'],$_middleware_params['method']),$this);
                        $route->{$_middleware_name}($callback);
                    }
                }

                // Values
                if(!empty($_route_params['values']))
                {
                    foreach($_route_params['values'] as $_value_name => $_value_value)
                    {
                        $route->value($_value_name, $_value_value);
                    }
                }

                // Value lang
                if(!empty($_lang))
                    $route->value('_locale', $_lang);

                // Asserts
                if(!empty($_route_params['asserts']))
                {
                    foreach($_route_params['asserts'] as $_assert_name => $_assert_value)
                    {
                        $route->assert($_assert_name, $_assert_value);
                    }
                }

                // Assert lang
                if(!empty($_lang))
                    $route->assert('_locale', $_lang);
            }
        }

        // No lang in URL
        if($this['config']['langs'] && $this['config']['langs']['redirect'])
        {
            $this->get('/',function(\Silex\Application $app)
            {
                // Get navigator lang
                $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

                // Default lang
                if(!in_array($lang, $app['config']['langs']['all']))
                    $lang = $app['config']['langs']['default'];

                $route = $this['config']['langs']['redirect'].'_'.$lang;

                // Redirect
                return $app->redirect($app['url_generator']->generate($route,array('_locale'=>$lang)));
            });
        }

        // Errors
        $app = $this;
        $this->error(function(\Exception $e, Request $request, $code) use ($app)
        {
            if($app['debug'])
                return;

            $route    = 'error_index';
            $data     = array();
            $has_lang = $this['config']['langs'];

            if($has_lang)
            {
                // Get navigator lang
                $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

                // Default lang
                if(!in_array($lang, $app['config']['langs']['all']))
                    $lang = $app['config']['langs']['default'];

                $route          .= '_'.$lang;
                $data['_locale'] = $lang;
            }

            return $app->redirect($app['url_generator']->generate($route,$data));
        });
    }

    public function render($path)
    {
        // Body
        $body = $this['twig']->render($path,$this->data);

        // Ajax
        if($this['ajax'])
        {
            $response       = new \stdClass();
            $response->html = $body;

            foreach($this->ajax_data as $_ajax_data)
            {
                if(!empty($this->data[$_ajax_data]))
                {
                    $response->{$_ajax_data} = $this->data[$_ajax_data];
                }
            }

            return new JsonResponse(
                $response,
                200,
                array(
                    'Cache-Control' => 's-maxage='.$this['config']['http_cache']['life_time'].', public'
                )
            );
        }

        // Default
        else
        {
            return new Response(
                $body,
                200,
                array(
                    'Cache-Control' => 's-maxage='.$this['config']['http_cache']['life_time'].', public'
                )
            );
        }
    }

    public function run(Request $request = null)
    {
        // Cache
        if($this['config']['http_cache']['active'])
            $this['http_cache']->run();

        // No cache
        else
            parent::run($request);
    }
}
