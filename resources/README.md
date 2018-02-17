Juliet Menu Resources
====

The containing folder for this readme was created as of version 2.0.2 but may be removed later on.  Let's try to keep a concise list of resources needed for creating menus.

**Overview and Introduction**

remaining steps to be done:
 - document what I have so far
 - document what steps I intend to take
 - get the color transition in place
 - get a SASS compiling code


This project is meant to be a modular method of generating and working with multi-level CSS dropdown menus which are also responsive.  Most dropdown menus are handled with a <div> container, some type of "hamburger" button which is hidden depending on the media, and a recursive combination of &lt;ul>, &lt;li>, &lt;a>, and then another repeat of that combination below the &lt;a> (but inside the &lt;li>) if that &lt;a> is a "node".


In most cases this hierarchy is going to be coming from a database , so this project expresses the output, along with most of the logical information about the menu, in a PHP array (which could also be a JSON object).  These menus can be quite complex, but essentially you'll want to declare the label, the URI, and ideally the title for SEO and accessibility.  If that "node" has children, it usually does not have a URI, but instead is simply a parent organizer for the resources (children) below it.  In this case we provide some type of indicator so the user is aware of it.


This project was done in Laravel 5 as this is what I'm familiar with.  It includes an example of a basic page with a fully functional menu.


This project includes a SQL dump to create a series of tables which will express the nav hierarchy, and in turn grab this data into the array which will be sent to the menu_generate_html method.


I intend this to do one thing simply and well (create a multi-level nav menu), but my eventual goal is to allow it to handle lazy loading, deal with a sitemap.xml file, and export/cache itself individually.  A style generator would also be nice.


This project may be reinventing the wheel at some points but I hope the sequence from the database all the way to the HTML output is helpful for some other developer at some point.  The configuration of the array is really the key to the project, and I plan to enhance that over time to make the menu as feature-rich as possible.  That includes, especially, portable integration with javascript.  I'm open to suggestions and comments on any ways to change/improve this.


 * Rules:
 * A group is a self-existent entity (think tree).  A group never functions as a node or an object. [1]
 * A group may exist without any attachments in juliet_node_hierarchy
 * Nodes represent taxonomic and conceptual organization (think branch), not content
 * Objects represent content (think fruit)
 * Nodes attach to other nodes, or null, but never an object.
 * A node must be in at least one group. [2]
 * A node can be in more than one group.
 * A node can have the same name as another node, but not in the same group on the same level.
 * A node can be used at several locations in a group. [3]
 * Objects need not be in any group, or under a node (think, a fruit is still a fruit off the tree, you can eat it).
 * If an object is attached, it attaches to a node, never another object or group [4]
 * An object `should not` attach within a group more than once, but theoretically could.
 * A node can contain more than one object (think multiple fruits from one twig).
 * If a node has multiple objects, one is the primary.
 *
 * [1] this means that a record of type `group` in juliet_nodes may have its id appear only in juliet_nodes_hierarchy.group_node_id
 * [2] this rule is negotiable; but it's preferred
 * [3] think about this; if the node name was "Warranty Info", it might appear in a complex menu under several products or product types.  It means the same thing in each case, but the object under it (page, pdf etc) pertains to a different product.  It makes sense to have that node "re-used" for each of these places.  This is the default behavior of the Juliet Menu creation system.
 * [4] since this is true, the group_node_id must also be present for the object's id in child_node_id and the node id in parent_node_id, because the node can be in multiple groups, so all three are needed to make the relationship unambiguous.

