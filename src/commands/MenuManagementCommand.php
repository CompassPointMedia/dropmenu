<?php

namespace Compasspointmedia\Julietmenu;

/**
 * This file is part of Menu Package,
 * a dynamic menu solution for Laravel.
 *
 * @license MIT
 * @package Compasspointmedia\Menu
 *
 * MenuManagement
 * --------------
 * Following julietmenu:menu commands are available:
 *
 * help - until I enable `php artisan help julietmenu:menu`, type in php artisan julietmenu:menu help for a list of commands
 * group [void] - list all menu groups.  If no groups, `default` will be created automatically.  Normally you'll only have default
 * group-add {name} - add a menu group with the given name
 * node [void] --group=(name or id) - list all nodes
 * node-add {name} {description*} --parent=(root) --
 * create a node of name {arg1}
 * object {like: regexp} --used --unused --group=(name or id) - list all objects with given options.
 * object-add {name} {uri*} --description - add an object ONLY.
 *
 *
 * Big Ideas:
 * we want to be able to view the menu as we create this.  This would be a great ng serve type application if we developed it that way.
 * this way we could also check page content as we go.  Also set up configuration of menu, so you have a menu and a theme you're develop-
 * ing simultaneously.
 *
 *
 * EMG:
 * php artisan julietmenu:menu node Home
 * php artisan julietmenu:menu nodeobject "My Home Page" /home --under Home
 * php artisan julietmenu:menu nodeobject Notifications --under Home
 * php artisan julietmenu:menu nodeobject Preferences --under-last
 * php artisan julietmenu:menu nodeobject "Rental Search" search
 * php artisan julietmenu:menu node "Property Maintenance"
 * php artisan julietmenu:menu nodeobject "Add House/Single-Unit" --under-last
 * php artisan julietmenu:menu nodeobject "Add Apartment/Multi-Unit" --under "Property Management"
 *
 */
use Illuminate\Console\Command;
# use Illuminate\Support\Facades\Config;
use Compasspointmedia\Julietmenu\Model\Menu;

class MenuManagementCommand extends Command
{

    protected $mapping = [
        'node' => [

        ]

    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'julietmenu:menu 
    {action=list} 
    {arg1? : Primary first argument (optional depending on action)} 
    {arg2? : Primary second argument (optional depending on action)}
    {--group=default : Name or ID of the group (menu) for a command}
    {--under= : Name or ID of the node another node or object should be under}
    {--nogroup : do not add to a group}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manages menu elements per the Juliet CMS specification';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        // validate action

        // validate contextual arguments and options under action

        // handle error(s)

        //

        $this->menu = new Menu($this->arguments(), $this->options());
        $action = $this->argument('action');
        $this->menu->$action();

    }

}
