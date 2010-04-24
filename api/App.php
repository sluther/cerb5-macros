<?php

class ChMacrosConfigTab extends Extension_ConfigTab {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
		$this->DevblocksExtension($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$macros = DAO_Macro::getAll(true);
		$tpl->assign('macros', $macros);
		$tpl->display('file:' . $tpl_path . 'index.tpl');
	}
	
	function saveTabAction() {
		$translate = DevblocksPlatform::getTranslationService();
		$worker = CerberusApplication::getActiveWorker();
		
		if(!$worker || !$worker->is_superuser) {
			echo $translate->_('common.access_denied');
			return;
		}
		
	    @$deletes = DevblocksPlatform::importGPC($_REQUEST['deletes'],'array',array());
	    
	    @$active_worker = CerberusApplication::getActiveWorker();
	    if(!$active_worker->is_superuser)
	    	return;
	    
	    // Deletes
	    if(!empty($deletes)) {
	    	DAO_Macro::delete($deletes);
	    }
		
		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','macros')));
	}
	
	function showMacroPanelAction() {		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);
		
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		} 

		$sources = DevblocksPlatform::getExtensions('cerberusweb.macros.source', false);
		$tpl->assign('sources', $sources);

		$source_ext_id = 'cerberusweb.macros.ticket';

		/*
		 we need to set the source_extension_id for the macro object so that renderConfig() knows
		 which source to render the config for
		 if editing an existing macro, this will already be set
		 however, if we're making a new macro, it needs to be set explicitly
		 */
		if(null != ($macro = DAO_Macro::get($id))) {
			$source_ext_id = $macro->source_extension_id;
		} else {
			/*
			 there's probably a better way to handle this, but if we're creating a new macro
			 we need to set the $macro var so renderConfig() won't bitch about
			 not being passed a Model_Macro object
			 */
			$macro = new Model_Macro();
			$macro->source_extension_id = $source_ext_id;
		}
		
		$tpl->assign('macro', $macro);
		
		if(false !== $source_ext = DevblocksPlatform::getExtension($source_ext_id, true))
		{
			$tpl->assign('source_ext', $source_ext);			
		}
		


		// Custom Fields
		$custom_fields =  DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		// Custom Macro Sources
		$source_manifests = DevblocksPlatform::getExtensions('cerberusweb.macros.sources', true);
		$tpl->assign('source_manifests', $source_manifests);
		
		$ext_action_mfts = DevblocksPlatform::getExtensions('cerberusweb.macros.actions', true);
		$tpl->assign('ext_action_mfts', $ext_action_mfts);

		$tpl->display('file:' . $tpl_path . 'peek.tpl');
	}
	
//	function showMacroConfigAction()
//	{
//		@$source = 
//		
//	}

	function saveMacroPanelAction() {
		$translate = DevblocksPlatform::getTranslationService();
		
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string');
		@$source_ext_id = DevblocksPlatform::importGPC($_REQUEST['source_ext_id'],'string');
		@$do   = DevblocksPlatform::importGPC($_REQUEST['do'],'array',array());
//		@$values = DevblocksPlatform::importGPC($_REQUEST['values'],'array',array());
		if(empty($name))
			$name = $translate->_('Macro Action');
		// Actions
		if(is_array($do))
		foreach($do as $act) {
			$action = array();
			$shortact = array_pop(explode('.', $act));
			$value = DevblocksPlatform::importGPC($_REQUEST['do_'.$shortact]);
			switch($act) {
				// Move group/bucket
				case 'cerberusweb.macros.action.move':
					@$move_code = $value;
					if(0 != strlen($move_code)) {
						list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($move_code);
						$action = array(
							'group_id' => intval($g_id),
							'bucket_id' => intval($b_id),
						);
					}
					break;
				// Assign to worker
				case 'cerberusweb.macros.action.assign':
					@$worker_id = $value;
					if(0 != strlen($worker_id))
						$action = array(
							'worker_id' => intval($worker_id)
						);
					break;
				// Spam training
				case 'spam':
					@$is_spam = DevblocksPlatform::importGPC($_REQUEST['do_spam'],'string',null);
					if(0 != strlen($is_spam))
						$action = array(
							'is_spam' => (!$is_spam?0:1)
						);
					break;
				// Set status
				case 'cerberusweb.macros.action.status':
					@$status = DevblocksPlatform::importGPC($_REQUEST['do_status'],'string',null);
					if(0 != strlen($status)) {
						$action = array(
							'is_waiting' => (3==$status?1:0), // explicit waiting
							'is_closed' => ((0==$status||3==$status)?0:1), // not open or waiting
							'is_deleted' => (2==$status?1:0), // explicit deleted
						);
					}
					break;
				default: // ignore invalids
					// Custom fields
					if("cf_" == substr($act,0,3)) {
						$field_id = intval(substr($act,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						$action = array();
							
						// [TODO] Operators
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'D': // dropdown
							case 'U': // URL
							case 'W': // worker
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
							case 'M': // multi-dropdown
							case 'X': // multi-checkbox
								$in_array = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $k => $v) {
									$out_array[$v] = $v;
								}
								
								$action['value'] = $out_array;
								break;
							case 'E': // date
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = $value;
								break;
							case 'N': // number
							case 'C': // checkbox
								$value = DevblocksPlatform::importGPC($_REQUEST['do_cf_'.$field_id],'string','');
								$action['value'] = intval($value);
								break;
						}
						
					} else {
						continue;
					}
					break;
			}
			
			$actions[$act] = $action;
		}
		
		$fields = array(
			DAO_Macro::NAME => $name,
			DAO_Macro::SOURCE_EXTENSION_ID => $source_ext_id,
			DAO_Macro::ACTIONS_SER => serialize($actions),
		);
		
		// Create
   		if(empty($id)) {
	   		$id = DAO_Macro::create($fields);
	   		
	   	// Update
   		} else {
			DAO_Macro::update($id, $fields);
   		}
   		
   		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','macros')));
	}
	
	function showSourceActionsAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string');
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro_id'], 'integer');
//		var_dump($ext_id);
//		var_dump($macro_id);
		if(null == ($macro = DAO_Macro::get($macro_id))) {
			/*
			 there's probably a better way to handle this, but if we're creating a new macro
			 we need to set the $macro var so renderConfig() won't bitch about
			 not being passed a Model_Macro object
			 */
			$macro = new Model_Macro();			
		}
		
		if(false !== $ext_id = DevblocksPlatform::getExtension($ext_id, true))
		{
			/*
			 we're passing in the $ext_id->manifest->id so renderConfig() knows what 
			 source we're rendering for
			 */
			$ext_id->renderConfig($macro, $ext_id->manifest->id);
		}
		
	}
};

class ChMacrosGroupTab extends Extension_GroupTab {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
		$this->DevblocksExtension($manifest);
	}
	
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->display('file:' . $tpl_path . 'index.tpl');
	}
	
	function saveTab() {}
	function showMacroFilterPanelAction()
	{
		@$group_id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = $this->_TPL_PATH;
		$tpl->assign('path', $tpl_path);

		$tpl->assign('group_id', $group_id);
		
		$active_worker = CerberusApplication::getActiveWorker();
		if(!$active_worker->isTeamManager($group_id) && !$active_worker->is_superuser) {
			return;
		}
		
		$team_rules = DAO_GroupInboxFilter::getByGroupId($group_id);
		$tpl->assign('team_rules', $team_rules);
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Custom Field Sources
		$source_manifests = DevblocksPlatform::getExtensions('cerberusweb.fields.source', false);
		$tpl->assign('source_manifests', $source_manifests);
		
		// Custom Fields
		$custom_fields =  DAO_CustomField::getAll();
		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->display('file:' . $tpl_path . 'peek.tpl');
	}
}

class ChMacrosPage extends CerberusPageExtension {
	private $_TPL_PATH = '';
	
	function __construct($manifest) {
		$this->_TPL_PATH = dirname(dirname(__FILE__)) . '/templates/';
		parent::__construct($manifest);
	}
	
	// [TODO] Refactor to isAuthorized
	function isVisible() {
		$worker = CerberusApplication::getActiveWorker();
		
		if(empty($worker)) {
			return false;
		} elseif($worker->is_superuser) {
			return true;
		}
	}
	
	function getActivity() {
	    return new Model_Activity('activity.macros');
	}
	
	function render() {
		// render the page
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl_path = $this->_TPL_PATH;
		$tpl->display('file:' . $tpl_path . 'index.tpl');
		
	}
	
	function runMacroAction()
	{
		
		@$macro_id = DevblocksPlatform::importGPC($_REQUEST['macro_id'], 'integer');
		@$ids = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		$ids = explode(',', $ids);
		
		if(null !== $macro = DAO_Macro::get($macro_id)) {
			
			switch($macro->source_extension_id)
			{
				case 'cerberusweb.macros.ticket':
					$fields = array();
					
					// loop over the actions, saving the $params as $fields
					foreach($macro->actions as $action => $params)
					{
						switch($action)	{
							case 'cerberusweb.macros.action.assign':
								$fields['next_worker_id'] = $params['worker_id'];
								break;
							case 'cerberusweb.macros.action.move':
								$fields['team_id'] = $params['group_id'];
								$fields['category_id'] = $params['bucket_id'];
								break;
							case 'cerberusweb.macros.action.status':
								$fields['is_waiting'] = $params['is_waiting'];
								$fields['is_closed'] = $params['is_closed'];
								$fields['is_deleted'] = $params['is_deleted'];
								break;
							default:
//								$fields[] = $params;
								break;	
						}
					}
					// update the ticket
					DAO_Ticket::updateTicket($ids, $fields);
					break;
				case 'cerberusweb.macros.address':
					foreach($macro->actions as $action => $params)
					{
						switch($action)	{
							default:
								DAO_Address::update($ids, $params);
						}
					}
					break;					
				case 'cerberusweb.macros.opportunity':

					foreach($macro->actions as $action => $params)
					{
						switch($action)	{
							default:
								DAO_CrmOpportunity::update($ids, $params);
						}
					}
				case 'cerberusweb.macros.task':
					foreach($macro->actions as $action => $params)
					{
						switch($action)	{
							default:
								DAO_Task::update($ids, $params);
						}
					}
				case 'cerberusweb.macros.organization':
					foreach($macro->actions as $action => $params)
					{
						switch($action)	{
							default:
								DAO_ContactOrg::update($ids, $params);
						}
					}
				default:
					
					break;
				}
		}
		$view = C4_AbstractViewLoader::getView($view_id);
	    $view->render();
	}
	function showMacroPanelAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl_path = $this->_TPL_PATH;
		$tpl->display('file:' . $tpl_path . 'peek.tpl');
	}
	
	function saveMacroPanelAction() {
		@$name = DevblocksPlatform::importGPC($_REQUEST['name'],'string');
		
		$fields = array(
   			DAO_Macro::NAME => $name,
   			DAO_Macro::CRITERIA_SER => serialize($criterion),
   			DAO_Macro::ACTIONS_SER => serialize($actions),
   		);		
	}
	
	// Ajax
	function showTabAction() {
		@$ext_id = DevblocksPlatform::importGPC($_REQUEST['ext_id'],'string','');
		
		if(null != ($tab_mft = DevblocksPlatform::getExtension($ext_id)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_MacrosTab) {
			$inst->showTab();
		}
	}
	
	/*
	 * [TODO] Proxy any func requests to be handled by the tab directly, 
	 * instead of forcing tabs to implement controllers.  This should check 
	 * for the *Action() functions just as a handleRequest would
	 */
	function handleTabActionAction() {
		@$tab = DevblocksPlatform::importGPC($_REQUEST['tab'],'string','');
		@$action = DevblocksPlatform::importGPC($_REQUEST['action'],'string','');

		if(null != ($tab_mft = DevblocksPlatform::getExtension($tab)) 
			&& null != ($inst = $tab_mft->createInstance()) 
			&& $inst instanceof Extension_MacrosTab) {
				if(method_exists($inst,$action.'Action')) {
					call_user_func(array(&$inst, $action.'Action'));
				}
		}
	}
	
};

class ChMacrosEventListener extends DevblocksEventListenerExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function handleEvent($event) {}
	
};

class DAO_Macro extends DevblocksORMHelper {
	const ID = 'id';
	const POS = 'pos';
	const CREATED = 'created';
	const NAME = 'name';
	const SOURCE_EXTENSION_ID = 'source_extension_id';
	const CRITERIA_SER = 'criteria_ser';
	const ACTIONS_SER = 'actions_ser';
	const CACHE_MACRO_ACTIONS = 'macro_actions';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO macro_action (id, created) ".
			"VALUES (%d, %d)",
			$id,
			time()
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'macro_action', $fields);
		
		self::clearCache();
	}
	
	static function getAll($nocache=false) {
	    $cache = DevblocksPlatform::getCacheService();
	    
	    if($nocache ||null === ($macroactions = $cache->load(DAO_Macro::CACHE_MACRO_ACTIONS))) {
	    	$macroactions = self::getWhere();
	    	$cache->save($macroactions, DAO_Macro::CACHE_MACRO_ACTIONS);
	    }
	    return $macroactions;
	}
	
	/**
	 * @param string $where
	 * @return Model_Macro[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, created, name, criteria_ser, actions_ser, source_extension_id ".
			"FROM macro_action ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY created DESC";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Macro	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param resource $rs
	 * @return Model_Macro[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while($row = mysql_fetch_assoc($rs)) {
			$object = new Model_Macro();
			$object->id = $row['id'];
			$object->created = $row['created'];
			$object->name = $row['name'];
			$object->source_extension_id = $row['source_extension_id'];
			$criteria_ser = $row['criteria_ser'];
			$actions_ser = $row['actions_ser'];

			$object->criteria = (!empty($criteria_ser)) ? @unserialize($criteria_ser) : array();
			$object->actions = (!empty($actions_ser)) ? @unserialize($actions_ser) : array();

			$objects[$object->id] = $object;
		}
		
		mysql_free_result($rs);
		
		return $objects;
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		if(empty($ids))
			return;
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM macro_action WHERE id IN (%s)", $ids_list));
		
		return true;
	}

//	/**
//	 * Increment the number of times we've matched this rule
//	 *
//	 * @param integer $id
//	 */
//	static function increment($id) {
//		$db = DevblocksPlatform::getDatabaseService();
//		$db->Execute(sprintf("UPDATE macro_action SET pos = pos + 1 WHERE id = %d",
//			$id
//		));
//	}
	
	static function clearCache() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(self::CACHE_MACRO_ACTIONS);
	}

};

class Model_Macro {
	public $id = 0;
	public $pos = 0;
	public $created = 0;
	public $name = '';
	public $criteria = array();
	public $actions = array();
	public $source_extension_id = '';
	
	static function getMatches($object) {
		$matches = array();
		$rules = DAO_MailToGroupRule::getWhere();
		$message_headers = $message->headers;
		$custom_fields = DAO_CustomField::getAll();
		
		// Lazy load when needed on criteria basis
		$address_field_values = null;
		$org_field_values = null;
		
		// Check filters
		if(is_array($rules))
		foreach($rules as $rule) { /* @var $rule Model_MailToGroupRule */
			$passed = 0;

			// check criteria
			foreach($rule->criteria as $crit_key => $crit) {
				@$value = $crit['value'];
							
				switch($crit_key) {
					case 'dayofweek':
						$current_day = strftime('%w');
//						$current_day = 1;

						// Forced to English abbrevs as indexes
						$days = array('sun','mon','tue','wed','thu','fri','sat');
						
						// Is the current day enabled?
						if(isset($crit[$days[$current_day]])) {
							$passed++;
						}
							
						break;
						
					case 'timeofday':
						$current_hour = strftime('%H');
						$current_min = strftime('%M');
//						$current_hour = 17;
//						$current_min = 5;

						if(null != ($from_time = @$crit['from']))
							list($from_hour, $from_min) = explode(':', $from_time);
						
						if(null != ($to_time = @$crit['to']))
							if(list($to_hour, $to_min) = explode(':', $to_time));

						// Do we need to wrap around to the next day's hours?
						if($from_hour > $to_hour) { // yes
							$to_hour += 24; // add 24 hrs to the destination (1am = 25th hour)
						}
							
						// Are we in the right 24 hourly range?
						if((integer)$current_hour >= $from_hour && (integer)$current_hour <= $to_hour) {
							// If we're in the first hour, are we minutes early?
							if($current_hour==$from_hour && (integer)$current_min < $from_min)
								break;
							// If we're in the last hour, are we minutes late?
							if($current_hour==$to_hour && (integer)$current_min > $to_min)
								break;
								
							$passed++;
						}

						break;					
					
					case 'tocc':
						$tocc = array();
						$destinations = DevblocksPlatform::parseCsvString($value);

						// Build a list of To/Cc addresses on this message
						@$to_list = imap_rfc822_parse_adrlist($message_headers['to'],'localhost');
						@$cc_list = imap_rfc822_parse_adrlist($message_headers['cc'],'localhost');
						
						if(is_array($to_list))
						foreach($to_list as $addy) {
							$tocc[] = $addy->mailbox . '@' . $addy->host;
						}
						if(is_array($cc_list))
						foreach($cc_list as $addy) {
							$tocc[] = $addy->mailbox . '@' . $addy->host;
						}
						
						$dest_flag = false; // bail out when true
						if(is_array($destinations) && is_array($tocc))
						foreach($destinations as $dest) {
							if($dest_flag) break;
							$regexp_dest = DevblocksPlatform::strToRegExp($dest);
							
							foreach($tocc as $addy) {
								if(@preg_match($regexp_dest, $addy)) {
									$passed++;
									$dest_flag = false;
									break;
								}
							}
						}
						break;
						
					case 'from':
						$regexp_from = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_from, $fromAddress->email)) {
							$passed++;
						}
						break;
						
					case 'subject':
						// [TODO] Decode if necessary
						@$subject = $message_headers['subject'];

						$regexp_subject = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_subject, $subject)) {
							$passed++;
						}
						break;

					case 'body':
						// Line-by-line body scanning (sed-like)
						$lines = preg_split("/[\r\n]/", $message->body);
						if(is_array($lines))
						foreach($lines as $line) {
							if(@preg_match($value, $line)) {
								$passed++;
								break;
							}
						}
						break;
						
					case 'header1':
					case 'header2':
					case 'header3':
					case 'header4':
					case 'header5':
						@$header = strtolower($crit['header']);

						if(empty($header)) {
							$passed++;
							break;
						}
						
						if(empty($value)) { // we're checking for null/blanks
							if(!isset($message_headers[$header]) || empty($message_headers[$header])) {
								$passed++;
							}
							
						} elseif(isset($message_headers[$header]) && !empty($message_headers[$header])) {
							$regexp_header = DevblocksPlatform::strToRegExp($value);
							
							// Flatten CRLF
							if(@preg_match($regexp_header, str_replace(array("\r","\n"),' ',$message_headers[$header]))) {
								$passed++;
							}
						}
						
						break;
						
					default: // ignore invalids
						// Custom Fields
						if(0==strcasecmp('cf_',substr($crit_key,0,3))) {
							$field_id = substr($crit_key,3);

							// Make sure it exists
							if(null == (@$field = $custom_fields[$field_id]))
								continue;

							// Lazy values loader
							$field_values = array();
							switch($field->source_extension) {
								case ChCustomFieldSource_Address::ID:
									if(null == $address_field_values)
										$address_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $fromAddress->id));
									$field_values =& $address_field_values;
									break;
								case ChCustomFieldSource_Org::ID:
									if(null == $org_field_values)
										$org_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $fromAddress->contact_org_id));
									$field_values =& $org_field_values;
									break;
							}
							
							// No values, default.
							if(!isset($field_values[$field_id]))
								continue;
							
							// Type sensitive value comparisons
							switch($field->type) {
								case 'S': // string
								case 'T': // clob
								case 'U': // URL
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									break;
								case 'N': // number
									if(!isset($field_values[$field_id]))
										break;

									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($crit['oper']) ? $crit['oper'] : "=";
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && intval($field_val) < intval($value))
										$passed++;
									break;
								case 'E': // date
									$field_val = isset($field_values[$field_id]) ? intval($field_values[$field_id]) : 0;
									$from = isset($crit['from']) ? $crit['from'] : "0";
									$to = isset($crit['to']) ? $crit['to'] : "now";
									
									if(intval(@strtotime($from)) <= $field_val && intval(@strtotime($to)) >= $field_val) {
										$passed++;
									}
									break;
								case 'C': // checkbox
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									if(intval($value)==intval($field_val))
										$passed++;
									break;
								case 'D': // dropdown
								case 'X': // multi-checkbox
								case 'M': // multi-picklist
								case 'W': // worker
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : array();
									if(!is_array($value)) $value = array($value);
										
									if(is_array($field_val)) { // if multiple things set
										foreach($field_val as $v) { // loop through possible
											if(isset($value[$v])) { // is any possible set?
												$passed++;
												break;
											}
										}
										
									} else { // single
										if(isset($value[$field_val])) { // is our set field in possibles?
											$passed++;
											break;
										}
										
									}
									break;
							}
						}
						break;
				}
			}
			
			// If our rule matched every criteria, stop and return the filter
			if($passed == count($rule->criteria)) {
				DAO_MailToGroupRule::increment($rule->id); // ++ the times we've matched
				$matches[$rule->id] = $rule;
				
				// Bail out if this rule had a move action
				if(isset($rule->actions['move']))
					return $matches;
			}
		}
		
		// If we're at the end of rules and didn't bail out yet
		if(!empty($matches))
			return $matches;
		
		// No matches
		return NULL;
	}
	
	/**
	 * @param integer[] $ticket_ids
	 */
	function run($ticket_ids) {
		if(!is_array($ticket_ids)) $ticket_ids = array($ticket_ids);
		
		$fields = array();
		$field_values = array();

		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
//		$workers = DAO_Worker::getAll();
		$custom_fields = DAO_CustomField::getAll();
		
		// actions
		if(is_array($this->actions))
		foreach($this->actions as $action => $params) {
			switch($action) {
//				case 'status':
//					if(isset($params['is_waiting']))
//						$fields[DAO_Ticket::IS_WAITING] = intval($params['is_waiting']);
//					if(isset($params['is_closed']))
//						$fields[DAO_Ticket::IS_CLOSED] = intval($params['is_closed']);
//					if(isset($params['is_deleted']))
//						$fields[DAO_Ticket::IS_DELETED] = intval($params['is_deleted']);
//					break;

//				case 'assign':
//					if(isset($params['worker_id'])) {
//						$w_id = intval($params['worker_id']);
//						if(0 == $w_id || isset($workers[$w_id]))
//							$fields[DAO_Ticket::NEXT_WORKER_ID] = $w_id;
//					}
//					break;

				case 'move':
					if(isset($params['group_id']) && isset($params['bucket_id'])) {
						$g_id = intval($params['group_id']);
						$b_id = intval($params['bucket_id']);
						if(isset($groups[$g_id]) && (0==$b_id || isset($buckets[$b_id]))) {
							$fields[DAO_Ticket::TEAM_ID] = $g_id;
							$fields[DAO_Ticket::CATEGORY_ID] = $b_id;
						}
					}
					break;
					
//				case 'spam':
//					if(isset($params['is_spam'])) {
//						if(intval($params['is_spam'])) {
//							foreach($ticket_ids as $ticket_id)
//								CerberusBayes::markTicketAsSpam($ticket_id);
//						} else {
//							foreach($ticket_ids as $ticket_id)
//								CerberusBayes::markTicketAsNotSpam($ticket_id);
//						}
//					}
//					break;

				default:
					// Custom fields
					if(substr($action,0,3)=="cf_") {
						$field_id = intval(substr($action,3));
						
						if(!isset($custom_fields[$field_id]) || !isset($params['value']))
							break;

						$field_values[$field_id] = $params;
					}
					break;
			}
		}

		if(!empty($ticket_ids)) {
			if(!empty($fields))
				DAO_Ticket::updateTicket($ticket_ids, $fields);
			
			// Custom Fields
			
			C4_AbstractView::_doBulkSetCustomFields(ChCustomFieldSource_Ticket::ID, $field_values, $ticket_ids);
		}
	}
	
};

abstract class Extension_MacroSource extends DevblocksExtension {
	const EXTENSION_POINT = 'cerberusweb.macroaction.source';
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function renderConfig(Model_Macro $macro, $source = 'cerberusweb.macros.ticket'){
		$actions_ext_mft = DevblocksPlatform::getExtensions('cerberusweb.macros.action', false);
		$actions = array();
		
		foreach($actions_ext_mft as $mft)
		{
			
			if(isset($mft->params['sources'][0][$source])) {
				$actions[$mft->id] = $mft;				
			}
		}
		
		return $actions;
	}
};

abstract class Extension_MacroAction extends DevblocksExtension {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	function run(){}	
};

class ChMacroSource_Ticket extends Extension_MacroSource {
	
	function __construct($manifest) {
		parent::__construct($manifest);		
	}
	
	public function renderConfig(Model_Macro $macro, $source)
	{
		// we'll render the config here, _and_ populate the settings properly based on the macro
		$actions = parent::renderConfig($macro, $source);
		
		$tpl = DevblocksPlatform::getTemplateService();
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->assign('actions', $actions);
		
		// since the sources dont match we don't want the actions to be set
		// to the values stored in the db
		if($macro->source_extension_id !== $source)
		{
			$macro->actions = array();
		}
		
		$tpl->assign('source', $source);
//		var_dump($source);
		$tpl->assign('macro', $macro);
		
		$tpl->display('file:' . dirname(dirname(__FILE__)) . '/templates/actions.tpl');
	}
};

class ChMacroSource_Address extends Extension_MacroSource {
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	public function renderConfig(Model_Macro $macro, $source)
	{
		// we'll render the config here, _and_ populate the settings properly based on the macro
		$actions = parent::renderConfig($macro, $source);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('actions', $actions);
		
		/*
		 since the sources dont match we don't want the actions to be set
		 to the values stored in the db
		 */
		if($macro->source_extension_id !== $source)
		{
			$macro->actions = array();
		}
		
		$tpl->assign('source', $source);
		$tpl->assign('macro', $macro);

		$tpl->display('file:' . dirname(dirname(__FILE__)) . '/templates/actions.tpl');		
	}
};

class ChMacroSource_Opportunity extends Extension_MacroSource {
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	public function renderConfig(Model_Macro $macro, $source)
	{
		// we'll render the config here, _and_ populate the settings properly based on the macro
		$actions = parent::renderConfig($macro, $source);

		$tpl = DevblocksPlatform::getTemplateService();

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->assign('actions', $actions);
		
		/*
		 since the sources dont match we don't want the actions to be set
		 to the values stored in the db
		 */
		if($macro->source_extension_id !== $source)
		{
			$macro->actions = array();
		}
		
		$tpl->assign('source', $source);
		$tpl->assign('macro', $macro);
		
		$tpl->display('file:' . dirname(dirname(__FILE__)) . '/templates/actions.tpl');
	}
};

class ChMacroSource_Task extends Extension_MacroSource {
	
	function __construct($manifest) {
		parent::__construct($manifest);
	}
	
	public function renderConfig(Model_Macro $macro, $source)
	{
		// we'll render the config here, _and_ populate the settings properly based on the macro
		$actions = parent::renderConfig($macro, $source);

		$tpl = DevblocksPlatform::getTemplateService();

		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);
                    
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->assign('actions', $actions);
		/*
		 since the sources dont match we don't want the actions to be set
		 to the values stored in the db
		 */
		if($macro->source_extension_id !== $source)
		{
			$macro->actions = array();
		}
		
		$tpl->assign('source', $source);
		$tpl->assign('macro', $macro);
		
		$tpl->display('file:' . dirname(dirname(__FILE__)) . '/templates/actions.tpl');
	}
};