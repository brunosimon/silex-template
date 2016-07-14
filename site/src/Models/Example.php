<?php

namespace Site\Models;

class Example
{
    function __construct($app)
    {
        $this->app = $app;
    }

    function getSomething()
    {
        return array(
            'toto',
            'tata',
            'tutu'
        );
    }
}
