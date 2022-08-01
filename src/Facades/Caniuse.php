<?php

namespace Sbrow\Caniuse\Facades;

use Illuminate\Support\Facades\Facade;

class Caniuse extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'caniuse';
    }
}
