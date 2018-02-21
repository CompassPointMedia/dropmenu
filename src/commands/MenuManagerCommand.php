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
use Compasspointmedia\Julietmenu\Model\JulietUtils as Utils;

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
    {--node= : Node name or ID}
    {--settings= : Pass a json string for settings}
    {--object-name= : Name of object if different from node}
    {--object-description= : Description of object if different from node}
    {--uri= : URI of node or object}
    {--no-create : Do not create the element if it is not present}
    {--create : Create the element}
    {--primary : Make an object the primary object even if another node is present}
    {--secondary : Make an object the secondary object even if there is no primary}
    {--lean : Lean output}
    ';

    protected $optionDefaults = ['group'];

    private $commands = [
        'commands' => [],
        'group' => [
            'description' => 'Creates or returns id of a specified taxonomic group (menu).  No arguments, and group name will be assumed to be `default`',
            'options' => ['no-create'],
            'unset' => ['group'],
            'arg_map' => [
                'arg1' => 'name',
                'arg2' => 'description',
            ],
        ],
        'node' => [
            'description' => 'Creates or returns the id of a specified node, and assigns it to the specified or default --group [group must exist].  Use --under <node-name> to place the node under another node.  Use --before and --after position the node horizontally [this sets the `priority` field(s)].  A node of the same name cannot exist in the same location on the tree.  By default a node already used somewhere else on the tree will be "re-used" for the specified position as long as it\'s different, but in the same group.  This mode can be changed to always recycle nodes, even if in other groups only, OR create a new node even if a node by that name exists.  See `$nodeCreateMode` in the coding.  Returns an array of the group id, created node id, and the parent node id (or NULL)',
            'arg_map' => [
                'arg1' => 'name',
                'arg2' => 'description',
            ],
            'validate' => [
                'name'
            ],
            'options' => ['no-create', 'group', 'before', 'after', 'under'],
        ],
        'nodeobject' => [
            'description' => 'Creates or uses a node, using --under, --before, and --after as for `node`, and attaches a NEW object of the same name to it as primary.  Use --object-name and --object-description if you want the object name and description to be different.  Use `'.self::META_SIGNATURE_NAME.' object {object-name} --under {existing-node-name}` to create a new object under an existing node',
            'arg_map' => [
                'arg1' => 'name',
                'arg2' => 'description',
            ],
            'validate' => [
                'name'
            ],
            'options' => [
                'no-create', 'group', 'before', 'after', 'under', 'primary', 'secondary', 'uri',
                'object-name', 'object-description'
            ],
        ],
        'object' => [
            'description' => 'Creates an end object with the specified name.  Use --under optionally to specify an existing parent node id or name, and --primary to make it the primary object under that node',
            'arg_map' => [
                'arg1' => 'name',
                'arg2' => 'description',
            ],
            'validate' => [
                'name'
            ],
            'options' => ['uri', 'create'],
        ],
        'structure' => [
            'description' => 'Output the hierarchical structure of a group.  Use --settings to inject settings in the root.  Specify --node <name-or-id> to load only that branch of the tree.  The `base_uri` value will still be considered from the root of the tree.  This can be used for "lazy loading" of a section of a nav menu.  The node must exist in the group',
            'arg_map' => [
                'arg1' => 'group'
            ],
            'validate' => [ 'group' ],
            'options' => ['node', 'settings', 'group', 'lean'],
            'unset' => [ 'no-create'],
        ]
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
        $command = strtolower($this->argument('action'));

        //Make sure command is recognized
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
        //Unset system options for clarity
        unset($opts['help']);
        unset($opts['quiet']);
        unset($opts['verbose']);
        unset($opts['version']);
        unset($opts['ansi']);
        unset($opts['no-ansi']);
        unset($opts['no-interaction']);
        unset($opts['env']);

        //Map argument names for models
        if(!empty($comm['arg_map'])){
            foreach($comm['arg_map'] as $from => $to) {
                $args[$to] = $args[$from];
                unset($args[$from]);
            }
        }else{
            // default
            $args['name'] = empty($args['arg1']) ? '' : $args['arg1'];
            unset($args['arg1']);
            $args['description'] = empty($args['arg2'] ? '' : $args['arg2']);
            unset($args['arg2']);
        }

        //Unset options with default values if specified
        if(!empty($comm['unset'])){
            foreach($comm['unset'] as $unset) unset($opts[$unset]);
        }


        //Unset disallowed options; we will tell them if they enter an irrelevant option for clarity
        $disallowed = [];
        $allowed = !empty($comm['options']) ? $comm['options'] : [];
        foreach ($opts as $opt => $val) {
            if(!is_null($val)){
                if(is_bool($val)){
                    //boolean values only represent flags and cannot be passed by user via CLI
                    if($val === true && !in_array($opt, $allowed)){
                        $disallowed[] = $opt;
                    }else if(in_array($opt, $allowed)){
                        continue;
                    }
                    //else will be unset
                }else if(!empty($this->optionDefaults[$opt]) && $this->optionDefaults[$opt] !== $val && !in_array($opt, $allowed)){
                    //A non-null, non-allowed value; error.
                    $disallowed[] = $opt;
                }else{
                    continue;
                }
            }
            unset($opts[$opt]);
        }
        if($disallowed){
            $last = array_pop($disallowed);
            $str = implode('`, `', $disallowed);
            $str = implode('` and `', $str ? [$str, $last] : [$last]);
            $str = '`' . $str . '`';
            $error = 'Error:';
            $error .= ' Option' . ($disallowed ? 's ' : ' ') . $str . ($disallowed ? ' are' : ' is') . ' not allowed for the command "' . $command . '"';
            $this->info($error);
            exit;
        }

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
            $error .= 'File: ' .$exception->getFile() . "\n";
            $error .= 'Line: ' .$exception->getLine() . "\n";
            $error .= $exception->getMessage();
            $this->error($error);
            exit(self::EXIT_METHOD);
        }

        //This might be modified if say the user demanded a verbose response etc.
        //However the intended purpose is to pipe returned values as arguments to other commands.
        if(!is_null($return) && !$this->option('no-interaction')) {
            if(Utils::is_2d_array($return)){
                //for now just print it out; soon we'll migrate to JSON
                print_r($return);
            }else if(is_array($return)){
                echo implode(',',$return);
            }else{
                echo $return;
            }
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
