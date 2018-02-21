<?php

namespace Compasspointmedia\Julietmenu;

use Compasspointmedia\Julietmenu\Model\MenuManager as MenuManager;

/**
 * Displays the menus provided by the name and using the
 * settings that are provided.
 *
 * Developed by: Samuel Fullman <sam-git@samuelfullman.com>
 * Originally template by: Shashwat Mishra <secrethash96@gmail.com>
 * License: MIT (Given that Credits should be given)
 */
class Julietmenu {

    public function __construct() {
        $this->menu = new MenuManager();
    }

    public function __call($name, $arguments) {
        if(method_exists($this->menu, $name)){
            //call directly
            return $this->menu->$name($arguments);
        }
        $error = 'Method '.$name.' does not exist in the MenuManager model';
        throw new \Exception($error);
    }

}