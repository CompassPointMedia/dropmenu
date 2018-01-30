<?php

namespace Compasspointmedia\Julietmenu;

/**
 * This file is part of Julietmenu,
 * a simple Dynamic Dropdown Menu Generator
 *
 * @license MIT
 * @package Compasspointmedia/Julietmenu
 */

use Illuminate\Support\Facades\Facade;

class JulietmenuFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'julietmenu';
    }
}
