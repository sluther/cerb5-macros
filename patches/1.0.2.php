<?php
$db = DevblocksPlatform::getDatabaseService();
$tables = $db->metaTables();

// `macro_action` =============================
if(isset($tables['macro_action'])) {
	$db->Execute("ALTER TABLE macro_action CHANGE COLUMN source source_extension_id VARCHAR(255)");	
}


return TRUE;