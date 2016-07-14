<?php

namespace Site\Middlewares;

use \Site\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

class Defaults
{
    static function before(Application $app)
    {
        return function(Request $request) use ($app)
        {
            $app->data['config']      = $app['config'];
            $app->data['layout_path'] = $app['ajax'] ? 'layouts/ajax.twig' : 'layouts/default.twig';

            $app->ajax_data[] = 'title';
            $app->ajax_data[] = 'route_name';
            $app->ajax_data[] = 'locale_route_name';

            if($app['config']['langs'])
            {
                $lang = $app['translator']->getLocale();

                $app->data['locale']            = $lang;
                $app->data['locale_route_name'] = $request->get('_route');
                $app->data['route_name']        = str_replace('_' . $lang, '', $request->get('_route'));
            }
            else
            {
                $app->data['route_name'] = $request->get('_route');
            }
        };
    }

    static function after(Application $app)
    {
        return function(Request $request, Response $response) use ($app)
        {

        };
    }

    static function finish(Application $app)
    {
        return function(Request $request, Response $response) use ($app)
        {

        };
    }
}
