<?php

define('SMARTY_DIR', dirname(__FILE__) . "/Smarty-4.2.1/libs/");
require_once(SMARTY_DIR . "Smarty.class.php");
require_once(dirname(__FILE__) . "/config.php");

class MySmarty extends Smarty {
	public function __construct() {
		parent::__construct();

		date_default_timezone_set("GMT+0");
	}

	public function dbh() {
		$opt = $this->opt();
		$dbh=new PDO(
			$opt["pdo_connection_string"],
			$opt["pdo_username"],
			$opt["pdo_password"]);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}

	public function opt() {
		static $opt;
		if (!isset($opt)) {
			$opt = getGlobalOptions();
		}
		return $opt;
	}

	public function display($template = null, $cache_id = null, $compile_id = null, $parent = null) {
		parent::assign('isadmin', isset($_SESSION['admin']) ? $_SESSION['admin'] : false);
		parent::assign('opt', $this->opt());
		parent::display($template, $cache_id, $compile_id, "");
	}
}
?>
