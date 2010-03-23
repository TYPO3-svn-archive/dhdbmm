<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

require_once(t3lib_extMgm::extPath('ameos_formidable') . 'api/class.mainobject.php');
require_once(t3lib_extMgm::extPath('ameos_formidable') . 'api/class.maindatahandler.php');
require_once(t3lib_extMgm::extPath('ameos_formidable') . 'api/base/dh_db/api/class.tx_dhdb.php');

require_once(t3lib_extMgm::extPath('dhdbmm') . 'class.ux_tx_dhdb.php');

$TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ameos_formidable/api/base/dh_db/api/class.tx_dhdb.php']
	= t3lib_extMgm::extPath('dhdbmm') . 'class.ux_tx_dhdb.php';
?>