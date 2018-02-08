<?php

namespace Compasspointmedia\Julietmenu;

/**
 * This file is part of Menu Package,
 * a dynamic menu solution for Laravel.
 *
 * @license MIT
 * @package Compasspointmedia\Menu
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class MigrationCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'juliet:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a migration for Database following the Compasspointmedia\'s Juliet Menu specifications.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->laravel->view->addNamespace('juliet', substr(__DIR__, 0, -8).'views');


        $this->line('');
        $this->info( "Table(s): menus" );

        $message = "A migration that creates 'menus'".
        " table. Migration file will be created in database/migrations directory";

        $this->comment($message);
        $this->line('');

        if ($this->confirm("Proceed with the migration creation? [Yes|no]", "Yes")) {

            $this->line('');

            $this->info("Creating migration...");
            if ($this->createMigration()) {

                $this->info("Migration successfully created!");
                $this->line('');
                $this->info('(!)Further: Now Run Command:');
                $this->line('');
                $this->info('> php artisan migrate');
                $this->line('');
                $this->info("to start the Migration of the 'menus' table");
                $this->line('');
                $this->info('P E A C E   O U T  ! ! !');
            } else {
                $this->error(
                    "Couldn't create migration.\n Check the write permissions".
                    " within the database/migrations directory."
                );
            }

            $this->line('');

        }
    }

    /**
     * Create the migration.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function createMigration()
    {
        $migrationFile = base_path("/database/migrations")."/".date('Y_m_d_His')."_compasspointmedia_julietmenu_tables.php";

        $output = $this->laravel->view->make('julietmenu::generators.migration')->render();

        if (!file_exists($migrationFile) && $fs = fopen($migrationFile, 'x')) {
            fwrite($fs, $output);
            fclose($fs);
            return true;
        }

        return false;
    }
}
