<?php

namespace Compasspointmedia\Julietmenu;

/**
 * This file is part of Menu Package,
 * a dynamic menu solution for Laravel.
 *
 * @license MIT
 * @package Compasspointmedia\Menu
 *
 * MenuManager
 * -----------
 * Following juliet:menu commands are available:
 *
 * help - until I enable `php artisan help juliet:menu`, type in php artisan juliet:menu help for a list of commands
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
 * php artisan juliet:menu node Home
 * php artisan juliet:menu nodeobject "My Home Page" /home --under Home
 * php artisan juliet:menu nodeobject Notifications --under Home
 * php artisan juliet:menu nodeobject Preferences --under-last
 * php artisan juliet:menu nodeobject "Rental Search" search
 * php artisan juliet:menu node "Property Maintenance"
 * php artisan juliet:menu nodeobject "Add House/Single-Unit" --under-last
 * php artisan juliet:menu nodeobject "Add Apartment/Multi-Unit" --under "Property Management"
 *
 */
use Illuminate\Console\Command;
# use Illuminate\Support\Facades\Config;
use Compasspointmedia\Julietmenu\Model\MenuManager;

class MenuManagerCommand extends Command
{

    const EXIT_GENERAL = 1;
    const EXIT_METHOD = 2;      // exception thrown by called method.
    const META_SIGNATURE_NAME = 'juliet:menu';
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = self::META_SIGNATURE_NAME . ' 
    {action=commands} 
    {arg1? : Primary first argument (optional depending on action)} 
    {arg2? : Primary second argument (optional depending on action)}
    {--group=default : Name or ID of the group (menu) for a command}
    {--under= : Name or ID of the node another node or object should be under}
    {--before= : Name or ID of the node another node or object should be before}
    {--after= : Name or ID of the node another node or object should be after}
    ';

    private $commands = [
        'commands' => [],
        'group' => [
            'description' => 'Create a taxonomic group with the specified name. Returns created or existing group id',
            'required' => [],
        ],
        'node' => [
            'description' => 'Create a node (navigational node). Use --under to specify name or id of parent node and --group to specify taxonomy group (default taxonomy group is simply called `default`).  Returns created or existing node id',
            'arg_map' => [
                'arg1' => 'name',
                'arg2' => 'description',
            ],
            'validate' => [
                'name'
            ],

        ],
        'object' => [
            'description' => 'Create an end object with the specified name.  Use --under optionally to specify an existing parent node id or name, and --primary to make it the primary object under that node'
        ],
        'nodeobject' => [
            'description' => 'Create a new navigational node and a primary object.  Use --under, --before, or --after optionally to specify an existing parent node id or name, and --group to specify an existing group.  Using --group is only necessary when the node in the option is part of multiple groups.',
        ],
        'something' => [

        ],

    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Conveniently manages menu elements from the command line per the Juliet CMS specification';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        //We need an action first to determine all other tasks
        $command = $this->argument('action');

        if($command === 'commands' || !array_key_exists($command, $this->commands)) {
            if($command !== 'commands'){
                $this->error(' Command "' . $command . '" is not recognized ');
            }
            $this->listCommands();
            if($command !== 'commands'){
                //Prevent next CLI command from happening..
                exit(self::EXIT_GENERAL);
            }else{
                return true;
            }
        }

        $comm = $this->commands[$command];
        $args = $this->arguments();
        $opts = $this->options();
        if(!empty($comm['arg_map'])){
            foreach($comm['arg_map'] as $from => $to) {
                $args[$to] = $args[$from];
                unset($args[$from]);
            }
        }else{
            // default
            $args['name'] = empty($args['arg1']) ? '' : $args['arg1'];
            unset($args['arg1']);
        }

        //Unset system options for clarity
        unset($opts['help']);
        unset($opts['quiet']);
        unset($opts['verbose']);
        unset($opts['version']);
        unset($opts['ansi']);
        unset($opts['no-ansi']);
        unset($opts['no-interaction']);
        unset($opts['env']);

        //Prepare arguments for method call - note options can override
        $args = array_merge($args, $opts);

        // handle error(s)
        $errors = [];
        if(!empty($comm['validate'])){
            foreach($comm['validate'] as $n => $v){
                if(is_int($n)){
                    $field = $v;
                    $validation = 'required';
                }else{
                    $field = $n;
                    $validation = $v;
                }
                // Validation can be improved and moved to a this->validate method
                switch($validation){
                    case 'required';
                        // Note we can still pass values like 0, but not whitespace only
                        if(!strlen(trim($args[$field]))){
                            $arg_map = '';
                            if(!empty($comm['arg_map']) && $arg_map = array_search($field, $comm['arg_map'])){
                                $arg_map = ' ('.$arg_map.')';
                            }
                            $errors[] = $field . $arg_map . ': '. 'A value is required';
                        }
                        break;
                }
            }
            if($errors){
                $this->info('Following syntax errors:');
                foreach($errors as $error){
                    $this->error($error);
                }
                exit(self::EXIT_GENERAL);
            }
        }

        //Instantiate the MenuManager model
        $this->menu = new MenuManager();

        //Call the specified command; use a try-catch to handle errors in CLI friendly way
        try{
            $return = $this->menu->$command($args);
        }catch(\Exception $exception){
            $error = 'Error in ' . get_class($this->menu) . ':' . $command . '()' . "\n";
            $error .= $exception->getMessage();
            $this->error($error);
            exit(self::EXIT_METHOD);
        }

    }

    private function listCommands(){
        $chunk = 80; //can this be dynamic
        $margin = 4;
        //get left column
        $leftCol =
        array_reduce(array_keys($this->commands), function($carry, $item)
        {
            $carry = max($carry, strlen($item));
            return $carry;
        });

        //output results
        $str = '';
        foreach($this->commands as $n=>$v){
            if($n === 'commands') continue;
            $description = (!empty($v['description']) ? $v['description'] : '');
            $description = trim(wordwrap($description, $chunk, "\n" . str_repeat(' ', $leftCol + $margin + 1)));
            $str .= $n . ($description ? ':' . str_repeat(' ', $leftCol - strlen($n) + $margin) . $description  : '') . "\n";
        }
        $this->info($str);
    }

}
