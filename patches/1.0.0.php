<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `automator_filter` =============================
if(!isset($tables['macro_action'])) {
	$sql = "
		CREATE TABLE IF NOT EXISTS macro_action (
			id INT UNSIGNED DEFAULT 0 NOT NULL,
			group_id INT UNSIGNED DEFAULT 0 NOT NULL,			
			created INT UNSIGNED DEFAULT 0 NOT NULL,
			name varchar(64) DEFAULT '' NOT NULL,
			criteria_ser MEDIUMTEXT,
			actions_ser MEDIUMTEXT,
			PRIMARY KEY (id)
		) ENGINE=MyISAM;
	";
	$db->Execute($sql);	
}


return TRUE;
