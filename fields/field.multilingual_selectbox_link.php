<?php
	/*
	Copyright: Deux Huit Huit 2015
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/class.field.php');
	require_once(EXTENSIONS . '/selectbox_link_field/fields/field.selectbox_link.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLang.php');
	
	/**
	 *
	 * Field class that will represent relationships between entries
	 * @author Deux Huit Huit
	 *
	 */
	class FieldMultilingual_SelectBox_Link extends FieldSelectBox_Link
	{
		public function __construct(){
			parent::__construct();
			$this->_name = __('Multilingual Select Box Link');
		}
		
		public function fetchIDfromValue($value) {
			$id = null;
			$related_field_ids = $this->get('related_field_id');
			
			$lc = FLang::getLangCode();

			if (empty($lc)) {
				$lc = FLang::getMainLang();
			}
			
			$value = Lang::createHandle($value);
			
			$try_parent = false;
			
			foreach($related_field_ids as $related_field_id) {
				try {
					$return = Symphony::Database()->fetchCol("id", sprintf("
						SELECT
							`entry_id` as `id`
						FROM
							`tbl_entries_data_%d`
						WHERE
							`handle` = '%s' OR `handle-{$lc}` = '%s'
						LIMIT 1", $related_field_id, $value, $value
					));

					// Skipping returns wrong results when doing an
					// AND operation, return 0 instead.
					if(!empty($return)) {
						$id = $return[0];
						break;
					}
				} catch (Exception $ex) {
					// Try the parent since this would normally be the case when a handle
					// column doesn't exist!
					$try_parent = true;
				}
			}
			
			if ($try_parent) {
				return parent::fetchIDfromValue($value);
			}

			return (is_null($id)) ? 0 : (int)$id;
		}
		
		public function fetchAssociatedEntrySearchValue($data, $field_id=NULL, $parent_entry_id=NULL){
			// We dont care about $data, but instead $parent_entry_id
			if(!is_null($parent_entry_id)) return $parent_entry_id;

			if(!is_array($data)) return $data;
			
			$handle = addslashes($data['handle']);
			
			$try_parent = false;
			
			$searchvalue = array();

			try {
				$searchvalue = Symphony::Database()->fetchRow(0, sprintf("
					SELECT `entry_id` FROM `tbl_entries_data_%d`
					WHERE `handle` = '%s' OR `handle-{$lc}` = '%s'
					LIMIT 1",
					$field_id, $handle, $handle
				));
			} catch (Exception $ex) {
				// Try the parent since this would normally be the case when a handle
				// column doesn't exist!
				$try_parent = true;
			}
			
			if ($try_parent) {
				return parent::fetchAssociatedEntrySearchValue($data, $field_id, $parent_entry_id);
			}

			return $searchvalue['entry_id'];
		}
		
	}