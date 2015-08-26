<?php
namespace presenters;

interface IPresenter {
	public function run();
}

abstract class Factory {
	public static function getPresenter() {
		$mode = isset($_SERVER['REQUEST_URI']) ?
			parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) :
			'/details';

		switch ($mode) {
		case '/':
		case '/login':
			$pres = new Login();
			break;
		case '/details':
			$pres = new Details();
			break;
		default:
			throw new PresenterError("Unknown mode: $mode");
		};

		return $pres;
	}
}

abstract class Presenter implements IPresenter {
	protected $user;

	function __construct() {
		if (isset($_SESSION['user'])) {
			$this->user = $_SESSION['user'];
		} else {
			throw new SecurityError();
		}
	}
}

class PresenterError extends \Exception { } 

class SecurityError extends \Exception {
	public function handle() {
		$return = isset($_GET['return']) ?
			'?return=' . urlencode($_GET['return']) :
			'';
		$r = new \views\Redirect('/login' . $return);
		$r->found();
	}
}

class Details extends Presenter implements IPresenter {
	public function run() {
		$view = new \views\SmartyView('details');
		$view->render($vars);
	}
}

class Login implements IPresenter {
	public function run() {
		switch ($_SERVER['REQUEST_METHOD']) {
		case 'POST':
			require_once 'settings.php';
			try {
				$user = $_POST['user'];
				$pass = $_POST['password'];
				$conn = new \ldap\Connection($host, $port, $user, $pass);
			} catch (\ldap\LDAPError $e) {
				$this->showLogin('Username or password incorrect');
			}
			break;
		default:
			if (isset($_SESSION['user'])) {
				$r = new \views\Redirect('/details');
				$r->found();
			} else {
				$this->showLogin();
			}
		}
	}

	private function showLogin($msg = null) {
		$vars = array('msg' => $msg);
		$view = new \views\SmartyView('login');
		$view->render($vars);
	}
}
