<?php

namespace Compasspointmedia\Julietmenu;

/**
 * This file is part of Menu Package,
 * a dynamic menu solution for Laravel.
 *
 * @license MIT
 * @package Compasspointmedia\Julietmenu
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class MenuManagerCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'julietmenu:menu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage menu items based on the Juliet CMS system';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
    }

}
