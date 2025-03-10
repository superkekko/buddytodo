<?php
class authentication extends controller {

	function loginpage($f3) {
		//create new session with 15gg live cookie, rootpath, server, secure, httponly and samesite strict
		session_set_cookie_params(1296000, '/', $_SERVER['SERVER_NAME'], true, true);
		ini_set('session.cookie_samesite', 'Lax');
		$token = bin2hex(random_bytes(32));
		if (empty($f3->get('SESSION.token'))) {
			$f3->set('SESSION.token', $token);
			$f3->set('token', $token);
		}else{
			$f3->clear('SESSION');
			$f3->clear('COOKIE');
			$f3->set('SESSION.token', $token);
			$f3->set('token', $token);
		}
		
		echo Template::instance()->render('login.html');
	}

	function login($f3) {
		$username = filter_var($f3->get('POST.username'), FILTER_SANITIZE_STRING);
		$password = filter_var($f3->get('POST.password'), FILTER_SANITIZE_STRING);
		$page_token = filter_var($f3->get('POST.csrf_token'), FILTER_SANITIZE_STRING);

		$session_csrf = $f3->get('SESSION.token');

		$user_check = $f3->get('DB')->exec('SELECT * FROM user WHERE user_id=?', $username);
		$user_check = $user_check[0];
		
		//se non ci sono i token riporto alla loginpage
		if (empty($page_token) || empty($session_csrf)) {
			$f3->set('token_error', true);
			$f3->clear('SESSION');
			$f3->clear('COOKIE');
			$this->loginpage($f3);
			exit;
		}
		
		//se non c'è l'utente riporto alla loginpage
		if (empty($user_check)) {
			$f3->set('user_error', true);
			$f3->clear('SESSION');
			$f3->clear('COOKIE');
			$this->loginpage($f3);
			exit;
		}

		if (hash_equals($page_token, $session_csrf) && !empty($user_check) && $user_check['user_id'] === $username && $this->encriptDecript($f3, $user_check['password'], 'd') === $password) {
			$exipration_date = date('Y-m-d H:i:s', strtotime('+15 day', strtotime(date("Y-m-d H:i:s"))));
			$f3->get('DB')->exec('INSERT INTO user_session(user_id, token, token_expire) VALUES(?,?,?)', array($username, $session_csrf, $exipration_date));
			$f3->set('SESSION.username', $username);

			if ($f3->exists('COOKIE.requestpage', $requestpage)) {
				$f3->clear('COOKIE.requestpage');

				$url_parsed = parse_url($requestpage);
				if ($url_parsed['path'] == '/login' || $url_parsed['path'] == '/logout' || $url_parsed['path'] == '/read') {
					$f3->reroute('/');
				} else {
					$f3->reroute('.'.$url_parsed['path']);
				}
			} else {
				$f3->reroute('/');
			}
		} else {
			$f3->set('login_error', true);

			$f3->clear('SESSION');
			$f3->clear('COOKIE');
			$this->loginpage($f3);
		}
	}

	function logout($f3) {
		$session_username = $f3->get('SESSION.username');
		$session_token = $f3->get('SESSION.token');

		if (!empty($session_username) && !empty($session_token)) {
			$user_data = $f3->get('DB')->exec('SELECT * FROM user_session WHERE user_id=? and token=?', array($session_username, $session_token));
			$user_data = $user_data[0];

			$f3->get('DB')->exec('UPDATE user_session SET token_expire = ? WHERE id=?', array(date('Y-m-d H:i:s'), $user_data['id']));
		}

		$f3->clear('SESSION');
		$requestpage = $f3->get('COOKIE.requestpage');
		//remove cookie from browser
		if(!empty($_COOKIE)){
			foreach(array_keys($_COOKIE) as $key){
				setcookie($key, '', time() - 3600); // empty value and old timestamp
			}
		}
		$f3->clear('COOKIE');
		$f3->set('COOKIE.requestpage', $requestpage, 1296000);

		$f3->reroute('/login');
	}

	function checklogged($f3) {
		$session_username = $f3->get('SESSION.username');
		$session_token = $f3->get('SESSION.token');

		$f3->set('COOKIE.requestpage', $f3->get('REALM'), 1296000);

		$user_present = $f3->get('DB')->exec('SELECT * FROM user WHERE user_id=?', $session_username);
		
		if(!empty($user_present) && !empty($session_token) && !empty($session_username)){
			$user_data = $f3->get('DB')->exec('SELECT * FROM user_session WHERE user_id=? and token=?', array($session_username, $session_token));
			$user_data = $user_data[0];
	
			if (!empty($user_data['token_expire']) && strtotime($user_data['token_expire']) >= strtotime(date('Y-m-d H:i:s'))) {
				$exipration_date = date('Y-m-d H:i:s', strtotime('+15 day', strtotime(date("Y-m-d H:i:s"))));
				$f3->get('DB')->exec('UPDATE user_session SET token_expire = ? WHERE id=?', array($exipration_date, $user_data['id']));
				$active_user = $f3->get('DB')->exec('SELECT * FROM user WHERE user_id=?', $session_username);
				$f3->set('active_user', array('user_id' => $active_user[0]['user_id'], 'bearer' => $active_user[0]['bearer'], 'password' => $active_user[0]['password'], 'group_id' => $active_user[0]['group_id'], 'superadmin' => $active_user[0]['superadmin']));
	
				$f3->set('COOKIE.requestpage', $f3->get('REALM'), 1296000);
				return true;
			} else {
				$f3->set('COOKIE.requestpage', $f3->get('REALM'), 1296000);
				return false;
			}
		}else{
			$f3->set('COOKIE.requestpage', $f3->get('REALM'), 1296000);
			return false;	
		}
	}
}