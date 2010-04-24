<?php
class ChMacrosPatchContainer extends DevblocksPatchContainerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
		
		/*
		 * [JAS]: Just add a sequential build number here (and update plugin.xml) and
		 * write a case in runVersion().  You should comment the milestone next to your build 
		 * number.
		 */

		$file_prefix = dirname(dirname(__FILE__)) . '/patches';
		
		$this->registerPatch(new DevblocksPatch('cerberusweb.macros',1,$file_prefix.'/1.0.0.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.macros',2,$file_prefix.'/1.0.1.php',''));
		$this->registerPatch(new DevblocksPatch('cerberusweb.macros',3,$file_prefix.'/1.0.2.php',''));
	}
};
