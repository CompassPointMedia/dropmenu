<?php

namespace Compasspointmedia\Julietmenu\Model;

use Illuminate\Database\Eloquent\Model;
use Compasspointmedia\Julietmenu\MenuManagerCommand;
use Compasspointmedia\Julietmenu\Model\JulietNode;
use Compasspointmedia\Julietmenu\Model\JulietNodeHierarchy;
use Compasspointmedia\Julietmenu\Model\JulietUtils as Utils;



class MenuManager extends Model {

    protected $groupDefaultName = 'default';

    /**
     * This is a failsafe against infinite recursion probably due to bad code writing
     * @var int recursionStop
     */
    protected $recursionStop = 15;

    const ALWAYS_CREATE = 1;    // most picky method; always create a node even if the name is exactly the same
    const REUSE_IN_GROUP = 2;
    const REUSE_ALWAYS = 3;     // least picky method

    public $nodeCreateMode = self::REUSE_IN_GROUP;

    public function __construct() {
        parent::__construct();

        // Instantiate tables we need to work with
        $this->node = new JulietNode();
        $this->hierarchy = new JulietNodeHierarchy();

    }

    /**
     * Group: get or create a group and return the group id.
     *
     * @param array $arguments
     * @return bool
     * @throws \Exception
     */
    public function group($arguments = []){
        $default = [
            'name' => '',
            'description' => '',
            'no-create' => false,
        ];
        extract($arguments = array_merge($default, $arguments));

        if(empty($name)) $name = $this->groupDefaultName;

        // Group count should be small; let's get all group members.
        $gid = false;
        $a = $this->node->where('type', 'group')->select('id','name')->get()->toArray();
        foreach ($a as $v) {
            if ($match = Utils::match($name, [$v['id'], $v['name']])) {
                $gid = $v['id'];
            }
        }
        if (!$gid) {
            if (is_numeric($name)) {
                $error = 'The id you passed ('.$name.') does not exist, or is not a group element; if you wish to create a group, use a non-numeric string and not a number.';
                throw new \Exception($error);
            }
            if ($arguments['no-create']) return false;
            // This block is responsible for creating a group record. Note a group does not require nodes by formal Juliet rules.
            $save = new JulietNode();
            $save->name = $name;
            $save->description = ($arguments['description'] ? : '');
            $save->type = 'group';
            $save->save();
            $gid = $save->id;
        }
        return $gid;
    }

    /**
     * Node-Object: given a group, create or recycle a node, optionally located under or adjacent to another node
     *
     * @param array $arguments
     * @return array
     * @throws \Exception
     */
    public function node($arguments = []){
        // specifications for this method
        $default = [
            'name' => '',
            'description' => '',
            'group' => $this->groupDefaultName,
            'under' => '',
            'before' => '',
            'after' => '',
            'return-node-id' => false,              //return a node's id only, if present
        ];
        // process arguments
        extract($arguments = array_merge($default, $arguments));

        // required arguments
        if(!strlen(trim($name))){
            throw new \Exception('A name is required');
        }

        if($arguments['before'] && $arguments['after']){
            $error = 'You specified both a --before and --after option.  You can only select one';
            throw new \Exception($error);
        }

        // handle group id; note we prevent them from creating a new group that doesn't exist yet on-the-fly.
        if($gid = $this->group([
            'name' => $group,
            'no-create' => (strtolower($group) === $this->groupDefaultName ? false : true),
        ])) {
            // group is recognized, we have id
        } else {
            $error = 'You cannot create this group ('.$group.') on-the-fly when creating a node; type `artisan ' . MenuManagerCommand::META_SIGNATURE_NAME . ' groups` to get a list of all groups and their id numbers.';
            throw new \Exception($error);
        }

        // Is there any node present in nodes table with this name?
        $sql = "SELECT
        n.id, n.active, n.name, n.description, 
        h.parent_node_id, h.group_node_id, h.priority, h.rlx,
        LCASE(ng.name) AS group_name
        FROM juliet_node n 
        LEFT JOIN juliet_node_hierarchy h ON n.type = 'node' AND n.id = h.node_id
        LEFT JOIN juliet_node ng ON h.group_node_id = ng.id
        WHERE :name IN(n.id, n.name)
        ORDER BY h.group_node_id";
        $results = \DB::select(\DB::raw($sql), ['name' => $name]);

        $nid = false;
        $groups = [];
        $groupNames = [];
        if(!$results || $this->nodeCreateMode === self::ALWAYS_CREATE) {
            //just create it
        }else{
            //build the relationships
            foreach($results as $result){
                $result = get_object_vars($result);
                $nid = $result['id'];
                if ($result['group_node_id']) {
                    $groupNames[$result['group_name']] = $result['group_node_id'];
                    $parent = $result['parent_node_id'] ? : '';
                    $groups[$result['group_node_id']][$nid] = $parent; // may be NULL
                }
            }
            if($this->nodeCreateMode === self::REUSE_IN_GROUP){
                if(empty($groups[$gid])){
                    //create it
                }else{
                    //@todo: decide which node to use; for now use first one
                    foreach($groups[$gid] as $nid => $parent){
                        break;
                    }
                }
            }else{ // self::REUSE_ALWAYS
                $group = !empty($groups[$groupNames[$this->groupDefaultName]]) ? $groups[$groupNames[$this->groupDefaultName]] : current($groups);
                //@todo: decide which node to use; for now use first one
                foreach($group as $nid => $parent){
                    break;
                }
            }
        }

        if($arguments['return-node-id']){
            //Simply return the found node id - even not present
            return $nid;
        }

        // handle any position that is not at the root
        $uid = false;
        if(!empty($under)){
            $sql = "SELECT
            n.id, n.name, h.group_node_id
            FROM juliet_node n LEFT JOIN
            juliet_node_hierarchy h ON n.id = h.node_id AND h.group_node_id = :gid 

            WHERE n.type = 'node' AND :under IN(n.id, n.name)";
            $results = \DB::select(\DB::raw($sql), ['gid' => $gid, 'under' => $under]);
            $error = '';
            if(!$results){
                $error = 'You have entered an "under" node value ('.$under.') that does not exist in this position';
            }else{
                $positions = 0;
                foreach($results as $result){
                    $result = get_object_vars($result);
                    if(!$result['group_node_id']) {
                        $error = 'The "under" node value you selected ('.$result['id'].':'.$result['name'].') exists but not in this group';
                        break;
                    }
                    $positions++;
                }
                $uid = $result['id'];
                if($positions > 1) $error = 'The "under" node value selected ('.$result['id'].':'.$result['name'].') exists in multiple positions within this group, so it\'s ambiguous';
            }
            if($error){
                throw new \Exception($error);
            }
        }

        //Prevent duplicate names in the same position
        $sql = "SELECT
        n.name, np.id AS parent_id, np.name AS parent_name
        FROM juliet_node_hierarchy h 
        JOIN juliet_node n ON h.node_id = n.id AND n.type='node'
        LEFT JOIN juliet_node np ON h.parent_node_id = np.id AND n.type = 'node'
        
        WHERE 
        (n.name = :name OR n.id = :id) AND 
        h.group_node_id = :gid AND 
        " . (!$uid ? "h.parent_node_id IS NULL" : "h.parent_node_id = :uid");
        $params = ['name' => $name, 'id' => $name, 'gid' => $gid];
        if($uid) $params['uid'] = $uid;
        $results = \DB::select(\DB::raw($sql), $params);
        if(!empty($results[0])){
            $result = get_object_vars($results[0]);
            $error = 'You already have a node "'.$result['name'].'"'.($result['parent_id'] ? ' under node `'.$result['parent_name'] . '`' : '').' in this position for this group';
            throw new \Exception($error);
        }

        //Determine if --before or --after directives can be honored
        $priority = 1;
        if($before || $after) {
            $place_str = $before . $after;
            $place     = $before ? 'before' : 'after';
            $sql       = "SELECT 
            h.priority
            FROM juliet_node_hierarchy h 
            JOIN juliet_node n ON h.node_id = n.id AND n.type='node'
            
            WHERE 
            :name IN(n.name, n.id) AND 
            h.group_node_id = :gid AND 
            " . (!$uid ? "h.parent_node_id IS NULL" : "h.parent_node_id = :uid");
            $params    = ['name' => $place_str, 'gid' => $gid];
            if ($uid) $params['uid'] = $uid;
            $results = \DB::select(\DB::raw($sql), $params);
            if (empty($results[0])) {
                $error = 'You specified this node should go ' . $place . ' a node "' . $place_str . '", however no such node name or id exists in this position';
                throw new \Exception($error);
            }
            $result = get_object_vars($results[0]);
            $priority = (int) $result['priority'] + ($before ? 0 : 1);

        }else if(!empty($first)){
            //@todo: add a --first flag (we really don't need a last as it's implicit)

        }else{
            //Find priority in this location of the group
            $sql = "SELECT
            MAX(h.priority) AS priority
            FROM juliet_node_hierarchy h
            
            WHERE h.group_node_id = :gid AND 
            " . (!$uid ? "h.parent_node_id IS NULL" : "h.parent_node_id = :uid");
            $params = ['gid' => $gid];
            if($uid) $params['uid'] = $uid;
            $results = \DB::select(\DB::raw($sql), $params);
            if(!empty($results[0])){
                $result = get_object_vars($results[0]);
                $priority = (int) $result['priority'] + 1;
            }

        }

        //--- Everything from here out changes state but should be reliable ---
        //NOTE: if the priority values are duplicates, this may not provide the ordering the user expects
        //todo: we need an 'auditorder' command for Juliet menu to fix this
        if($before || $after){ //todo: remember we also need to develop "first" here
            $sql = "UPDATE juliet_node_hierarchy h SET
            h.priority = h.priority + 1
            
            WHERE h.priority >= $priority AND 
            h.group_node_id = :gid AND 
            " . (!$uid ? "h.parent_node_id IS NULL" : "h.parent_node_id = :uid");
            $params = ['gid' => $gid];
            if($uid) $params['uid'] = $uid;
            \DB::statement($sql, $params);
        }

        //Save the record in node
        if(!$nid){
            $save = new JulietNode();
            $save->name = $name;
            $save->description = ($arguments['description'] ? : '');
            $save->type = 'node';
            $save->save();
            $nid = $save->id;
        }

        //Save the record in hierarchy
        $save = new JulietNodeHierarchy();
        $save->node_id = $nid;
        if($uid) $save->parent_node_id = $uid;
        $save->group_node_id = $gid;
        $save->priority = $priority;
        $save->save();
        /* NOTE: hierarchy_id is something that should not exist or be referenced @todo: remove from table the laravel way
        $hid = $save->id;
        */

        return [
            $gid,
            $nid,
            $uid ? : 'NULL',
        ];
    }

    public function nodeObject($arguments = []){
        // specifications for this method
        $default = [
            'name' => '',
            'description' => '',
            'object-name' => '',                    //if different from node
            'object-description' => '',
            'group' => $this->groupDefaultName,
            'under' => '',
            'before' => '',
            'after' => '',
            'primary' => '',
            'secondary' => '',
            'return-node-id' => false,              //return a node's id only, if present
        ];

        // process arguments
        extract($arguments = array_merge($default, $arguments));

        // required arguments
        if(!strlen(trim($name))){
            throw new \Exception('A name is required');
        }

        if($arguments['before'] && $arguments['after']){
            $error = 'You specified both a --before and --after option.  You can only select one';
            throw new \Exception($error);
        }

        $node = $this->node([
            'name' => $arguments['name'],
            'description' => $arguments['description'],
            'group' => $arguments['group'],
            'under' => $arguments['under'] ? : '',
            'before' => $arguments['before'] ? : '',
            'after' => $arguments['after'] ? : '',

        ]);
        if(!$node){
            throw new \Exception('Call to method node() did not return any values');
        }

        $oid = $this->object([
            'name' => $arguments['object-name'] ? : $arguments['name'],
            'description' => $arguments['object-description'] ? : $arguments['description'],
            'create' => true,
        ]);

        //Save the record in hierarchy
        $save = new JulietNodeHierarchy();
        $save->node_id = $oid;
        $save->parent_node_id = $node[1];
        $save->group_node_id = $node[0];
        $save->rlx = $secondary? 'Secondary' : 'Primary';
        $save->save();

        return [
            $save->group_node_id,
            $oid,
            $save->parent_node_id
        ];
    }

    public function object($arguments = []){
        $default = [
            'name' => '',
            'description' => '',
            'uri' => NULL,
            'create' => false,
        ];

        // process arguments
        extract($arguments = array_merge($default, $arguments));

        // required arguments
        if(!strlen(trim($name))){
            throw new \Exception('An object name or id is required');
        }

        $sql = "SELECT
        n.id, n.name, n.description FROM juliet_node n WHERE n.type='object' AND :name IN(n.id, n.name) 
        ";
        $params = ['name' => $name];
        $results = \DB::select(\DB::raw($sql), $params);
        if(!$results) {
            //just create it
            if(is_numeric($name)){
                throw new \Exception('No object with id of '.$name. ' was found');
            }
        }else{
            if($create){
                throw new \Exception('An object with this name or id ('.$name.') already exists');
            }
            //we could address more than one result, not done
            foreach($results as $result){
                $result = get_object_vars($result);
                return $result['id'];
            }
        }

        $save = new JulietNode();
        $save->name = $name;
        $save->description = $arguments['description'] ? : '';
        $save->uri = $arguments['uri'];
        $save->type = 'object';
        $save->save();
        return $save->id;

    }

    /**
     * Generate structure including container information.  This can handle lazy loading if either a node name or ID is provided correctly.
     *
     * @param array $arguments
     * @throws \Exception
     */
    public function structure($arguments = []){
        // specifications for this method
        $default = [
            'group' => $this->groupDefaultName,
            'node' => '',
            'settings' => '',
            'lean' => '',
        ];
        // process arguments
        extract($arguments = array_merge($default, $arguments));

        // required arguments
        if(!strlen(trim($group))){
            throw new \Exception('A group name is required');
        }

        // handle group id; note we prevent them from creating a new group that doesn't exist yet on-the-fly.
        if($gid = $this->group([
            'group' => $group,
            'no-create' => true,
        ])) {
            // group is recognized, we have id
        } else {
            //todo: `groups` is not yet created!
            $error = 'You cannot create a group ('.$group.') on-the-fly; type `artisan ' . MenuManagerCommand::META_SIGNATURE_NAME . ' groups` to get a list of all groups and their id numbers.';
            throw new \Exception($error);
        }

        //load a section of this structure for lazy loading or partial menu
        if($node){
            if ($nid = $this->node([
                'name' => $node,
                'group' => $group,
                'no-create' => true,
                'return-node-id' => true,
            ])){
               //Ready to process
            }else{
                $error = 'You requested a specific node ('.$node.') which was not found';
                throw new \Exception($error);
            }
        }else{
            $nid = NULL;
        }

        //now that we have the gid and nid, let's find the initial level and base_uri
        $start = microtime(true);
        $ancestors = $this->ancestors($gid, $nid, ['output' => 'all']);

        $path = Utils::array_by_key($ancestors, 'name');
        array_walk($path, [$this, 'pretty_url']);

        $base_uri = '/'.implode('/', $path);
        $config['base_uri'] = $base_uri;
        $config['lean'] = $arguments['lean'];
        $results = $this->structure_map($gid, $nid, count($ancestors), $config);
        $stop = microtime(true);

        //todo: we need the group name here but I don't want to execute that query in this scope
        $results = [
            'group_node_id' => $gid,
            'node_id' => $nid ? : '',
            'level' => count($ancestors),
            'base_uri' => $base_uri,
            'children' => $results,
            'fetched_at' => date('Y-m-d H:i:s', floor($start)),
            'fetch_time_elapsed' => round($stop - $start, 4),
        ];
        if(!empty($settings)){
            $results['settings'] = $settings;
        }
        print_r($results);

    }

    /**
     * todo: this definitely needs to be abstracted
     * we want user to be able to call their own engine for prettifying this
     * also.. not here but on the $path, this includes replacement strategies, skipping certain nodes so we don't end up with stupid stuff like /cleveland/services/services etc.
     *
     * @param $val
     * @param null $key
     * @return mixed|string
     */
    function pretty_url(&$val, $key = NULL)
    {
        $val = trim($val);
        $val = strtolower($val);
        $val = preg_replace('/[^a-z0-9_]+/', '', $val);
        $val = str_replace(' ','-',$val);
        $val = str_replace('--', '-', $val);
        $val = str_replace('--', '-', $val);
        return $val;
    }

    /**
     * Ancestors: get information about ancestors of a specified node
     *  - returns empty (count = 0) for a root level node [$nid = NULL]
     *  - returns parent/root (count = 1) for level 1 menu items
     *  - etc.
     * So, ancestors is a list of nodes _above_me_
     * Therefore my level is count($ancestors) + 1
     *
     * @param $gid
     * @param $nid
     * @param array $options
     * @param array $return
     * @param array $config
     * @return array
     * @throws \Exception
     */
    public function ancestors($gid, $nid, $options = [], $return = [], $config = []){
        $default = [
            'output' => 'all',       //options are: all, id, and name
        ];
        $config['level'] = empty($config['level']) ? 1 : $config['level'];
        extract($options = array_merge($default, $options));

        if(is_null($nid)) return [];

        if(!$gid){
            throw new \Exception('You must pass a group id');
        }

        switch ($output){
            case 'id':
                $select = '';
                $join = '';
                break;
            case 'name':
                $select = ', n.name';
                $join = 'JOIN juliet_node n ON h.node_id = n.id';
                break;
            case 'all':
                $select = ', n.name, n.description';
                $join = 'JOIN juliet_node n ON h.node_id = n.id';
                break;
            default:
                throw new \Exception('An output option of `id`, `name`, or `all` is required');
        }

        $sql = "SELECT
        h.parent_node_id, h.node_id AS id $select
        FROM juliet_node_hierarchy h $join
        WHERE h.group_node_id = :gid AND h.node_id = :nid
        ";
        if($config['level'] > $this->recursionStop){
            throw new \Exception('You have gone up ancestry more than '.$this->recursionStop.' times');
        }
        $params = ['gid' => $gid, 'nid' => $nid];

        if($result = \DB::select(\DB::raw($sql), $params)){
            $result = get_object_vars($result[0]);
            if($output === 'all'){
                $temp = [$result];
                foreach($return as $node) $temp[] = $node;
                $return = $temp;
            }else{
                $temp = [$result[$output]];
                foreach($return as $node) $temp[] = $node;
                $return = $temp;
            }

            //recursion for parent
            if($result['parent_node_id']){
                $config['level']++;
                $return = $this->ancestors($gid, $result['parent_node_id'], $options, $return, $config);
            }
        }
        return $return;
    }

    /**
     * Structure map: returns a PHP recursive array of a menu structure at any location (but default is the root)
     * Attaches the actual level to all children.  Level 1, 2, etc. represent the actual level on the menu (not zero-based)
     *
     * @param $gid
     * @param null $id
     * @param int $level
     * @param array $config
     * @return array
     * @throws \Exception
     */
    public function structure_map($gid, $id = NULL, $level = 0, $config = []){

        $sql = "SELECT
        n.id, n.name, n.description, n.active, n.uri, n.created_at, n.updated_at, h.priority,
        n2.id AS obj_id,
        n2.name AS obj_name,
        n2.description AS obj_description,
        n2.active AS object_active,
        n3.id AS secondary_id,
        n3.name AS secondary_name,
        n3.description AS secondary_description
        
        FROM juliet_node_hierarchy h 
        JOIN juliet_node n ON h.node_id = n.id AND n.type = 'node'
        -- todo: document query logic here; this is probably not great for very large node tables
        LEFT JOIN
        (juliet_node_hierarchy h2 JOIN juliet_node n2 ON h2.node_id = n2.id AND n2.type = 'object' AND h2.rlx = 'Primary')  
        ON h2.group_node_id = :gid2 AND h2.parent_node_id = h.node_id
        LEFT JOIN
        (juliet_node_hierarchy h3 JOIN juliet_node n3 ON h3.node_id = n3.id AND n3.type = 'object' AND h3.rlx = 'Secondary')
        ON h3.group_node_id = :gid3 AND h3.parent_node_id = h.node_id  
        WHERE h.group_node_id = :gid AND h.parent_node_id " . (is_null($id) ? 'IS NULL' : " = :id") . "

        ORDER BY h.priority, h.created_at";
        $params = ['gid' => $gid, 'gid2' => $gid, 'gid3' => $gid];
        if(!is_null($id)) $params['id'] = $id;
        $results = \DB::select(\DB::raw($sql), $params);


        //return empty array for no children
        if(!$results) return [];

        if($this->recursionStop && $level > $this->recursionStop){
            throw new \Exception('Currently this method does not go more than 15 levels of recursion deep');
        }

        foreach($results as $key => $result){
            if(!isset($ids[$result->id])){
                //simplify result
                $result = get_object_vars($result);

                $sub_config = $config;
                $name = $result['name'];
                $sub_config['base_uri'] = (!empty($sub_config['base_uri']) ? rtrim($sub_config['base_uri'], '/') : '') . '/' . $this->pretty_url($name);

                $results[$key] = $result;
                $results[$key]['level'] = $level + 1;
                $results[$key]['base_uri'] = $sub_config['base_uri'];

                if($config['lean']){
                    foreach($results[$key] as $n=>$v){
                        if(!strlen($v) || $n==='created_at' || $n==='updated_at') unset($results[$key][$n]);
                    }
                }

                $children = $this->structure_map($gid, $result['id'], $level + 1, $sub_config);
                if($children) $results[$key]['children'] = $children;

                $ids[$result['id']] = $key;
            }else{
                $_key = $ids[$result->id];
                unset($results[$key]);
            }
        }
        return $results;
    }
}
