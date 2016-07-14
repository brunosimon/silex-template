<?php

namespace Site\Controllers;

use \Site\Application;

class Index
{
    static function index(Application $app)
    {
        $app->data['title'] = 'Home';

        return $app->render('pages/index/index.twig');
    }

    static function about(Application $app)
    {
        $example_model = new \Site\Models\Example($app);
        $something     = $example_model->getSomething();

        $app->data['title']     = 'About';
        $app->data['something'] = $something;

        return $app->render('pages/index/about.twig');
    }
}
