<?php

namespace Site\Middlewares;

use \Site\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

class Customs
{
    static function indexIndexBefore(Application $app)
    {
        return function(Request $request)
        {

        };
    }
}
