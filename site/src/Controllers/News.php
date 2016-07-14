<?php

namespace Site\Controllers;

use \Site\Application;

class News
{
    static function index(Application $app)
    {
        $app->data['title'] = 'News';

        return $app->render('pages/news/index.twig');
    }

    static function single($news_slug, Application $app)
    {
        $app->data['title'] = 'Single news';

        return $app->render('pages/news/single.twig');
    }
}
