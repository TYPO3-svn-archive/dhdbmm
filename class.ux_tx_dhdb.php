<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Oliver Klee <typo3-coding@oliverklee.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class ux_tx_dhdb for the "dhdbmm" extension.
 *
 * This class represents a data handler that is capable of loading and saving
 * m:m relations.
 *
 * @package TYPO3
 * @subpackage tx_dhdbmm
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ux_tx_dhdb extends tx_dhdb {
	/**
	 * indicates whether the mm fields already have been retrieved from the
	 * configuration
	 *
	 * @var boolean
	 */
	protected $mmFieldsHaveBeenRetrieved = FALSE;

	/**
	 * list of fields that are m:n relations as key/mm-table pairs
	 *
	 * @var array
	 */
	protected $mmFields = array();

	/**
	 * the data to insert into m:n tables using the
	 * following format for each element:
	 *
	 * 'table' => name of the m:n table,
	 * 'data' => array(
	 *		'sorting' => the sorting position,
	 *		'uid_foreign' => the foreign key
	 * )
	 *
	 * @var array
	 */
	protected $mmInserts = array();

	/**
	 * Iterates over the form data in $formData and processes the fields that
	 * are marked as m:n relations the following way:
	 * 1. For each entry in the comma-separated list of values, an element
	 *    in $this->mmInserts is created.
	 * 2. The comma-separated list will be converted to an integer containing
	 *    the number of relations for this field.
	 *
	 * After this function has been called, $this->mmInserts will be filled with
	 * the retrieved data (which might still be empty).
	 *
	 * @param array $formData
	 *        the current form data must not be empty, will be modified
	 */
	protected function extractMmRelationsFromFormData(array &$formData) {
		$this->retrieveMmFields();

		foreach ($formData as $key => $value) {
			if (isset($this->mmFields[$key])) {
				if ($value != '') {
					$sorting = 1;
					$allDataItems = explode(',', $value);
					$value = count($allDataItems);

					foreach ($allDataItems as $currentDataItem) {
						$this->mmInserts[] = array(
							'table' => $this->mmFields[$key],
							'data' => array(
								// uses the default sorting
								'sorting' => $sorting,
								'uid_foreign' => intval($currentDataItem)
							)
						);
						$sorting++;
					}
				} else {
					$value = 0;
				}
				$formData[$key] = $value;
			}
		}
	}

	/**
	 * Retrieves the keys and MM table names for m:n relations as key/value
	 * pairs and stores them in $this->mmFields in the following form:
	 *
	 * field key => name of the m:n table
	 *
	 * The data will be retrieved from the datahandler in the XML file where it
	 * needs to be stored in the following format:
	 *
	 * <mmrelations>
	 * 	<relation field="place" mmtable="tx_seminars_seminars_place_mm" />
	 *	<relation field="speakers" mmtable="tx_seminars_seminars_speakers_mm" />
	 * </mmrelations>
	 *
	 * If $this->mmFields has already been set, this function will be a no-op.
	 */
	protected function retrieveMmFields() {
		if ($this->mmFieldsHaveBeenRetrieved) {
			return;
		}

		$relationRawData = $this->oForm->_navConf(
			'/control/datahandler/mmrelations'
		);

		if (is_array($relationRawData)) {
			foreach ($relationRawData as $currentRelation) {
				if (isset($currentRelation['field'])
					&& isset($currentRelation['mmtable'])
				) {
					$fieldName = $currentRelation['field'];
					$mmTableName = $currentRelation['mmtable'];
					$this->mmFields[$fieldName] = $mmTableName;
				}
			}
		}

		$this->mmFieldsHaveBeenRetrieved = TRUE;
	}

	/**
	 * Takes the entered form data and inserts/updates it in the DB, using the
	 * table name set in /control/datahandler/tablename.
	 * For fields that have a m:n table defined in $this->mmFields, a real m:n
	 * relation is created instead of a comma-separated list of foreign keys.
	 *
	 * This function can insert new records and update existing records.
	 *
	 * This function is an exact copy from tx_dhdb with the following calls
	 * added:
	 * - extractMmRelationsFromFormData
	 * - storeMmRelations (2 times)
	 *
	 * @param boolean $bShouldProcess whether the data should be processed at all
	 */
	public function _doTheMagic($bShouldProcess = TRUE) {
		$tablename	= $this->tableName();
		$keyname	= $this->keyName();

		if($tablename != "" && $keyname != "") {

			if($this->i18n() && ($aNewI18n = $this->newI18nRequested()) !== FALSE) {

				// first check that parent exists
				if(($aParent = $this->__getDbData($tablename, $keyname, $aNewI18n["i18n_parent"])) === FALSE) {
					$this->oForm->mayday("DATAHANDLER DB cannot create requested i18n for non existing parent:" . $aNewI18n["i18n_parent"]);
				}

				//then check that no i18n record exists for requested sys_language_uid on this parent record

				$sSql = $GLOBALS["TYPO3_DB"]->SELECTquery(
					$keyname,
					$tablename,
					"l18n_parent='" . $aNewI18n["i18n_parent"] . "' AND sys_language_uid='" . $aNewI18n["sys_language_uid"] . "'"
				);

				$rSql = $this->oForm->_watchOutDB(
					$GLOBALS["TYPO3_DB"]->sql_query($sSql),
					$sSql
				);

				if($GLOBALS["TYPO3_DB"]->sql_fetch_assoc($rSql) !== FALSE) {
					$this->oForm->mayday("DATAHANDLER DB cannot create requested i18n for parent:" . $aNewI18n["i18n_parent"] . " with sys_language_uid:" . $aNewI18n["sys_language_uid"] . " ; this version already exists");
				}

				$aChild = array();
				$aChild["sys_language_uid"] = $aNewI18n["sys_language_uid"];
				$aChild["l18n_parent"] = $aNewI18n["i18n_parent"];	// notice difference between i and l
				$aChild["crdate"] = time();
				$aChild["tstamp"] = time();
				$aChild["cruser_id"] = $GLOBALS["TSFE"]->fe_user->user["uid"];
				$aChild["pid"] = $aParent["pid"];

				$sSql = $GLOBALS["TYPO3_DB"]->INSERTquery(
					$tablename,
					$aChild
				);

				$rSql = $this->oForm->_watchOutDB(
					$GLOBALS["TYPO3_DB"]->sql_query($sSql),
					$sSql
				);

				$this->newEntryId = $GLOBALS["TYPO3_DB"]->sql_insert_id();
				$this->bHasCreated = TRUE;
				$this->refreshAllData();
			}

			if($bShouldProcess && $this->_allIsValid()) {

				// il n'y a aucune erreur de validation
				// on peut traiter les donnes
				// on met a jour / insere l'enregistrement dans la base de donnees



				$aRs = array();

				$aFormData = $this->_processBeforeInsertion(
					$this->getDataPreparedForDB()
				);

				if(count($aFormData) > 0) {
					$this->extractMmRelationsFromFormData($aFormData);

					$editEntry = $this->_currentEntryId();

					if($editEntry) {

						$aFormData = $this->_processBeforeEdition($aFormData);

						if($this->i18n() && $this->i18n_updateChildsOnSave() && $this->i18n_currentRecordUsesDefaultLang()) {

							// updating non translatable child data

							$aUpdateData = array();

							$this->oForm->_debug("", "DB update, taking care of sys_language_uid " . $this->i18n_getSysLanguageUid());

							reset($aFormData);
							while(list($sName, ) = each($aFormData)) {
								if(
									!array_key_exists($sName, $this->oForm->aORenderlets) ||
									!$this->oForm->aORenderlets[$sName]->_translatable()
								) {
									$aUpdateData[$sName] = $aFormData[$sName];
								}

							}

							if(!empty($aUpdateData)) {

								$this->oForm->_debug($aUpdateData, "EXECUTION OF DATAHANDLER DB - EDITION MODE in " . $tablename . "[" . $keyname . "=" . $editEntry . "] - UPDATING NON TRANSLATED I18N CHILDS");

								$sSql = $GLOBALS["TYPO3_DB"]->UPDATEquery(
									$tablename,
									"l18n_parent = '" . $editEntry . "'",
									$aUpdateData
								);

								$this->oForm->_watchOutDB(
									$GLOBALS["TYPO3_DB"]->sql_query($sSql),
									$sSql
								);
							}
						}

						if($this->fillStandardTYPO3fields()) {
							if(!array_key_exists("tstamp", $aFormData)) {
								$aFormData['tstamp'] = time();
							}
						}

						$this->oForm->_debug($aFormData, "EXECUTION OF DATAHANDLER DB - EDITION MODE in " . $tablename . "[" . $keyname . "=" . $editEntry . "]");

						$sSql = $GLOBALS["TYPO3_DB"]->UPDATEquery(
							$tablename,
							$keyname . " = '" . $editEntry . "'",
							$aFormData
						);

						$this->oForm->_watchOutDB(
							$GLOBALS["TYPO3_DB"]->sql_query($sSql),
							$sSql
						);

						$this->storeMmRelations($editEntry);

						$this->oForm->_debug($GLOBALS["TYPO3_DB"]->debug_lastBuiltQuery, "DATAHANDLER DB - SQL EXECUTED");

						// updating stored data
						$this->__aStoredData = array_merge($this->__aStoredData, $aFormData);
						$this->bHasEdited = TRUE;
						$this->_processAfterEdition($this->_getStoredData());

					} else {

						// creating data

						$aFormData = $this->_processBeforeCreation($aFormData);
						if(is_array($aFormData) && count($aFormData) !== 0) {
							if($this->i18n()) {
								$this->oForm->_debug("", "DB insert, taking care of sys_language_uid " . $this->i18n_getSysLanguageUid());
								$aFormData["sys_language_uid"] = $this->i18n_getSysLanguageUid();
							}

							if($this->fillStandardTYPO3fields()) {
								if(!array_key_exists("pid", $aFormData)) {
									$aFormData['pid'] = $GLOBALS['TSFE']->id;
								}

								if(!array_key_exists("cruser_id", $aFormData)) {
									$aFormData['cruser_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
								}

								if(!array_key_exists("crdate", $aFormData)) {
									$aFormData['crdate'] = time();
								}

								if(!array_key_exists("tstamp", $aFormData)) {
									$aFormData['tstamp'] = time();
								}
							}

							$this->oForm->_debug($aFormData, "EXECUTION OF DATAHANDLER DB - INSERTION MODE in " . $tablename);

							$sSql = $GLOBALS["TYPO3_DB"]->INSERTquery(
								$tablename,
								$aFormData
							);

							$this->oForm->_watchOutDB(
								$GLOBALS["TYPO3_DB"]->sql_query($sSql),
								$sSql
							);

							$this->oForm->_debug($GLOBALS["TYPO3_DB"]->debug_lastBuiltQuery, "DATAHANDLER DB - SQL EXECUTED");

							$this->newEntryId = $GLOBALS["TYPO3_DB"]->sql_insert_id();
							$this->oForm->_debug("", "NEW ENTRY ID [" . $keyname . "=" . $this->newEntryId . "]");

							$this->storeMmRelations($this->newEntryId);

							$this->bHasCreated = TRUE;

							// updating stored data
							$this->__aStoredData = array();
							$this->_getStoredData();
						} else {
							$this->newEntryId = FALSE;
							$this->oForm->_debug("", "NOTHING CREATED IN DB");

							// updating stored data
							$this->__aStoredData = array();
						}

						$this->_processAfterCreation($this->_getStoredData());
					}
				} else {
					$this->oForm->_debug("", "EXECUTION OF DATAHANDLER DB - NOTHING TO DO - SKIPPING PROCESS " . $tablename);
				}

				/*   /process/afterinsertion */
				$this->_processAfterInsertion($this->_getStoredData());

			} else {
				/* nothing to do */
			}
		} else {
			$this->oForm->mayday("DATAHANDLER configuration isn't correct : check /tablename AND /keyname in your datahandler conf");
		}
	}

	/**
	 * Retrieves the data of the current record from the DB as an associative
	 * array. m:n relations are returned as a comma-separated list of UIDs.
	 *
	 * @param boolean $sName (not sure what this is, but FORMidable has it)
	 *
	 * @return array data from the DB as an associative array
	 */
	public function _getStoredData($sName = FALSE) {
		$result = parent::_getStoredData($sName);
		if (!$result) {
			return $result;
		}

		$this->retrieveMmFields();
		// deals with data that has m:n relations
		foreach ($this->mmFields as $key => $mmTable) {
			// Do we have any data (with $result[$key] being the number
			// of related records)?
			if ($result[$key] > 0) {
				$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid_foreign',
					$mmTable,
					'uid_local = ' . $this->_currentEntryId(),
					'',
					'sorting'
				);
				if ($dbResult) {
					$foreignUids = array();
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
						$foreignUids[] = $row['uid_foreign'];
					}
					// ceates a comma-separated list of UIDs
					$result[$key] = implode(',', $foreignUids);
				} else {
					$result[$key] = '';
				}
			}
		}

		return $result;
	}

	/**
	 * Takes the m:n relations stored in $this->mmInserts and stores them in the
	 * DB with $uid as the local key. All previous relations for that key
	 * will be removed.
	 *
	 * @param integer $uid the uid of the current record, must be > 0
	 */
	protected function storeMmRelations($uid) {
		$this->retrieveMmFields();

		// removes all old m:n records
		foreach ($this->mmFields as $currentTable) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery(
				$currentTable,
				'uid_local = ' . $uid
			);
		}

		// creates all new m:n records
		$sorting = 0;

		foreach ($this->mmInserts as $currentInsert) {
			$data = $currentInsert['data'];
			$data['uid_local'] = $uid;
			$data['sorting'] = $sorting;

			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				$currentInsert['table'],
				$data
			);

			$sorting++;
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dhdbmm/class.ux_tx_dhdb.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/dhdbmm/class.ux_tx_dhdb.php']);
}
?>