-- the following SQL creates the three tables needed for menu hierarchy

-- Create syntax for TABLE 'juliet_node'
CREATE TABLE `juliet_node` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '8',
  `system_name` char(32) DEFAULT NULL COMMENT 'Differentiates System Name from Name',
  `name` char(75) NOT NULL DEFAULT '',
  `description` char(255) NOT NULL DEFAULT '',
  `uri` char(255) DEFAULT NULL,
  `type` enum('group','node','object') NOT NULL DEFAULT 'node' COMMENT 'Group identifies a group for a tree and is required on all elements',
  `category` char(75) NULL DEFAULT NULL,
  `page_type` char(30) DEFAULT NULL COMMENT 'Allows page to be tied to a component like e-commerce',
  `component_settings` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `active` (`active`),
  KEY `system_name` (`system_name`),
  KEY `name` (`name`),
  KEY `uri` (`uri`),
  KEY `type` (`type`),
  KEY `category` (`category`)
) ENGINE=MyISAM AUTO_INCREMENT=93 DEFAULT CHARSET=latin1 COMMENT='by Compass Point Media';

-- Create syntax for TABLE 'juliet_node_hierarchy'
CREATE TABLE `juliet_node_hierarchy` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'This allows a node to be assigned within a tree multiple times',
  `node_id` int(11) unsigned NOT NULL,
  `parent_node_id` int(11) unsigned DEFAULT NULL,
  `group_node_id` int(11) unsigned DEFAULT NULL COMMENT 'This is an extension/split of a typical hierarchical table; its primary use is to allow ID to be used multiple times, so one object can occur in multiple trees',
  `rlx` enum('Primary','Secondary') CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL,
  `priority` tinyint(3) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `node_id` (`node_id`),
  KEY `parent_node_id` (`parent_node_id`),
  KEY `group_node_id` (`group_node_id`),
  KEY `rlx` (`rlx`)
) ENGINE=MyISAM AUTO_INCREMENT=88 DEFAULT CHARSET=latin1 COMMENT='by Compass Point Media';

-- Create syntax for TABLE 'juliet_node_settings'
CREATE TABLE `juliet_node_settings` (
  `node_id` int(11) NOT NULL,
  `settings` text COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`node_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='by Compass Point Media';