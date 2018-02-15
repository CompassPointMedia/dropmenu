<?php

namespace Compasspointmedia\Julietmenu\Model;

use Illuminate\Database\Eloquent\Model;
use Compasspointmedia\Julietmenu\MenuManagerCommand;
use Compasspointmedia\Julietmenu\Model\JulietNode;
use Compasspointmedia\Julietmenu\Model\JulietNodeHierarchy;
use Compasspointmedia\Julietmenu\Model\JulietUtils as Utils;



class MenuManager extends Model {

    protected $groupDefaultName = 'default';

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

    public function node($arguments = []){
        /*
         * so we are doing something like this
         *
         * artisan juliet:menu node Home            create that node (in default group)
         * artisan juliet:menu node Home --under A  create under A (and A must exist in the group)
         * artisan juliet:menu node Home --after B  create after B (ditto for this position)
         *
         * Now there are three creation types based on a node name match:
         * Create it even if it exists at all               (most picky)
         * Reuse it if it's present in this group
         * Reuse it if it's present in any group            (least picky)
         *
         */
        // specifications for this method
        $default = [
            'name' => '',
            'description' => '',
            'group' => $this->groupDefaultName,
            'under' => '',
            'before' => '',
            'after' => '',
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
    public function nodeObject(){

    }
}
/*
 * Rules:
 * A group is a self-existent entity (think tree).  A group never functions as a node or an object. [2]
 * A group may exist without any attachments in juliet_node_hierarchy
 * Nodes represent taxonomic and conceptual organization (think branch), not content
 * Objects represent content (think fruit)
 * Nodes attach to other nodes, or null, but never an object.
 * A node must be in at least one group. [4]
 * A node can be in more than one group.
 * A node can have the same name as another node, but not in the same group on the same level.
 * A node can be used at several locations in a group. [3]
 * Objects need not be in any group, or under a node (think, a fruit is still a fruit off the tree, you can eat it).
 * If an object is attached, it attaches to a node, never another object or group [1]
 * An object `should not` attach within a group more than once, but theoretically could.
 * A node can contain more than one object (think multiple fruits from one twig).
 * If a node has multiple objects, one is the primary.
 *
 * [1] since this is true, the group_node_id must also be present for the object's id in child_node_id and the node id in parent_node_id, because the node can be in multiple groups, so all three are needed to make the relationship unambiguous.
 * [2] this means that a record of type `group` in juliet_nodes may have its id appear only in juliet_nodes_hierarchy.group_node_id
 * [3] think about this; if the node name was "Warranty Info", it might appear in a complex menu under several products or product types.  It means the same thing in each case, but the object under it (page, pdf etc) pertains to a different product.  It makes sense to have that node "re-used" for each of these places.  This is the default behavior of the Juliet Menu creation system.
 * [4] this rule is negotiable; but it's preferred
 *
 *
 *
 *
 */

