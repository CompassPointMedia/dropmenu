<?php

namespace Compasspointmedia\Julietmenu\Model;

use Illuminate\Database\Eloquent\Model;
use Compasspointmedia\Julietmenu\Model\JulietNode;
use Compasspointmedia\Julietmenu\Model\JulietNodeHierarchy;

class MenuManager extends Model {

    public function __construct() {
        parent::__construct();

        // Instantiate tables we need to work with
        $this->node = new JulietNode();
        $this->hierarchy = new JulietNodeHierarchy();

    }

    public function node($options = []){
        // specifications for this method
        $default = [
            'name' => '',
            'description' => '',
            'group' => 'default',
            'under' => '',
            'before' => '',
            'after' => '',
        ];
        // process arguments
        extract(array_merge($default, $options));

        // required arguments
        if(!strlen(trim($name))){
            throw new \Exception('A name is required');
        }

        // handle group id
        if($gid = $this->group(['name' => $group])) {
            // group is recognized, we have id
        } else {
            exit('exception');
        }

        // Is there any node present in nodes table with this name?
        "SELECT
        n.id, n.active, n.name, n.description, h.node_id, h.parent_node_id, h.group_node_id, h.priority, h.rlx
        FROM juliet_node n 
        LEFT JOIN juliet_node_hierarchy h ON n.type = 'node' AND n.id IN(h.node_id, h.parent_node_id)
        
        WHERE $name IN(n.id, n.name)
        ";

        // If it is present singularly then use it, otherwise pick the node used in this group (if possible)
        $results = DEFINE;
        $groups = [];
        if ($results) {
            foreach($results as $result) {
                $nid = $result['id'];
                if ($result['group_id']) {
                    $groups[$result['group_id']][] = $nid;
                }
            }
            // Pick the node used in this group, and note there may be more than one match
            if (!empty($groups[$gid])) {
                $nid = current($groups[$gid]);
            }
        } else {
            // create the node

            $nid = DEFINE;
        }

        // Figure out the position
        "SELECT
        
        FROM juliet_node_hierarchy h WHERE h.group_node_id = $gid AND ";


        // is this node anywhere in this group?  Find all places.
        // Note that we don't just search for nodes but nodes in group context (same name could be
        $sql = "SELECT
        
        FROM juliet_nodes n JOIN ";


        // handle any position that is not at the root
        $locus = '';
        foreach (['under', 'before', 'after'] as $l) {
            if(!empty($options[$l])) {


                if('select h.child_nodes_id, h.parent_nodes_id from juliet_nodes n JOIN juliet_nodes_hierarchy h ON n.id IN(h.parent_nodes_id, h.child_nodes_id) AND h.group_nodes_id = $gid'


                )

                $locus = $l;
                $reference = $options[$l];


            }
        }


        /* a name of a node need not be unique */

        //get a list of nodes matching that name
        $create = true;
        if (count($matchingNodes)) {
            foreach ($matchingNodes as $n => $v) {
                if ($v['gid'] === $gid) {
                    // We have this node, let's return this value.
                    return $v['id'];
                }
            }
        } else {
            // create the new node

            return $id;
        }

    }
    public function nodeObject(){

    }
}
/*
 * Rules:
 * A group is a self-existemt entity.  A group never functions as a node or an object. [2]
 * Nodes attach to other nodes, or null, but never an object.
 * A node must be in at least one group.
 * A node can be in more than one group.
 * A node can have the same name as another node, but not in the same group on the same level.
 * A node can be re-used at several locations in a group. [3]
 * Objects need not be in any group, or under a node.
 * If an object is attached, it attaches to a node, never another object. [1]
 * An object `should not` attach within a group more than once, but theoretically could.
 * A node can contain more than one object.
 *
 * [1] since this is true, the group_node_id must also be present for the object's id in child_node_id and the node id in parent_node_id, because the node can be in multiple groups, so all three are needed to make the relationship unambiguous.
 * [2] this means that a record of type `group` in juliet_nodes may have its id appear only in juliet_nodes_hierarchy.group_node_id
 * [3] think about this; if the node name was "Warranty Info", it might appear in a complex menu under several products or product types.  I means the same thing in each case, but the object under it pertain to a different product.  It makes sense to have that node "re-used" for each of these places.  This is the default behavior of the Juliet Menu creation system.
 */
