<?php

########################################################################
# Extension Manager/Repository config file for ext "dhdbmm".
#
# Auto generated 27-04-2010 18:49
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'FORMidable DB m:m data handler',
	'description' => 'This extension provides a data handler for FORMidable that can handle m:m relations.',
	'category' => 'services',
	'author' => 'Oliver Klee',
	'author_email' => 'typo3-coding@oliverklee.de',
	'shy' => '',
	'dependencies' => 'ameos_formidable',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'oliverklee.de',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'ameos_formidable' => '2.0.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:6:{s:9:"ChangeLog";s:4:"2418";s:20:"class.ux_tx_dhdb.php";s:4:"643c";s:16:"ext_autoload.php";s:4:"d6fc";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"2893";s:14:"doc/manual.sxw";s:4:"c2ef";}',
	'suggests' => array(
	),
);

?>