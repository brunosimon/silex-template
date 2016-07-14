<?php

namespace Site\Controllers;

use \Site\Application;
use \Symfony\Component\HttpFoundation\Request;

class Error
{
    static function index(Application $app)
    {
        $app->data['title'] = 'Error';

        return $app->render('pages/error/index.twig');
    }
}
