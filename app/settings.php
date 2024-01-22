<?php
class settings extends privatepages {
	function settings($f3) {
		$current_user = $f3->get('active_user');

		$f3->set('password_change', $f3->get('GET.status'));

		$result = $f3->get('DB')->exec("SELECT bearer FROM user WHERE user_id = ?", $current_user['user_id']);
		$f3->set('bearer', $result[0]['bearer']);

		$f3->set('content', 'private-settings.html');
	}

	function settingsedit($f3) {
		$current_user = $f3->get('active_user');
		$task = $f3->get('POST.task');

		if ($task == 'token-refresh') {
			$check = true;
			while ($check) {
				$bearer = $this->generateRandomString(50);
				$result = $f3->get('DB')->exec("SELECT count(1) as occurence FROM user WHERE bearer=?", $bearer);
				if ($result[0]['occurence'] == 0) {
					$check = false;
				}
			}
			$f3->get('DB')->exec("UPDATE user SET bearer=? where user_id=?", array($bearer, $current_user['user_id']));
		} elseif ($task == 'password-change') {
			$password_old = $f3->get('POST.password-old');
			$password_new = $f3->get('POST.password-new');

			if ($this->encriptDecript($f3, $current_user['password'], 'd') !== $password_old) {
				$f3->set('password_error', true);
				$this->settings($f3);
			} else {
				$f3->get('DB')->exec("UPDATE user SET password=? where user_id=?", array($this->encriptDecript($f3, $password_new), $current_user['user_id']));
				$f3->set('password_changed',true);
				$this->settings($f3);
			}
		}

		$f3->reroute('/settings');
	}

	function supersettings($f3) {
		$current_user = $f3->get('active_user');
		
		if($current_user['superadmin'] != 1){
			$f3->reroute("/");
		}
		$result = $f3->get('DB')->exec("SELECT count(1) as rows FROM user_session WHERE token_expire>=?", date("Y-m-d H:i:s"));
		$f3->set('active_session', $result[0]['rows']);

		$results = $f3->get('DB')->exec("SELECT * FROM user");
		$f3->set('users', $results);

		$f3->set('content', 'private-super-settings.html');
	}

	function supersettingsedit($f3) {
		$current_user = $f3->get('active_user');
		
		if($current_user['superadmin'] != 1){
			$f3->reroute("/");
		}
		
		$task = $f3->get('POST.task');

		if ($task == 'delete') {
			$user_id = $f3->get('POST.delete-id');

			$f3->get('DB')->exec("DELETE FROM user WHERE id = ?", $user_id);
		} elseif ($task == 'edit') {
			$user_id = $f3->get('POST.user-id');

			if ($user_id == 0) {
				$result = $f3->get('DB')->exec("SELECT count(1) as rows FROM user WHERE user_id = ?", $f3->get('POST.user-user'));
				if ($result[0]['rows'] > 0) {
					$f3->set('same_userid', true);
					$this->supersettings($f3);
				}
				if ($f3->get('POST.user-superadmin') == '1') {
					$f3->get('DB')->exec("INSERT INTO user(user_id, group_id, password, bearer, superadmin) VALUES(?,?,?,?,?)",
						array($f3->get('POST.user-user'), $f3->get('POST.user-group'), $this->encriptDecript($f3, $f3->get('POST.user-password')), $this->generateRandomString(50), 1));
				} else {
					$f3->get('DB')->exec("INSERT INTO user(user_id, group_id, password, bearer, superadmin) VALUES(?,?,?,?,?)",
						array($f3->get('POST.user-user'), $f3->get('POST.user-group'), $this->encriptDecript($f3, $f3->get('POST.user-password')), $this->generateRandomString(50), 0));
				}
			} else {
				if ($f3->get('POST.user-password') != '') {
					$f3->get('DB')->exec("UPDATE user SET password=? WHERE id=?", array($this->encriptDecript($f3, $f3->get('POST.user-password')), $user_id));
				}
				if ($f3->get('POST.user-superadmin') == '1') {
					$f3->get('DB')->exec("UPDATE user SET group_id=?, superadmin=? WHERE id=?",
						array($f3->get('POST.user-group'), 1, $user_id));
				} else {
					$f3->get('DB')->exec("UPDATE user SET group_id=?, superadmin=? WHERE id=?",
						array($f3->get('POST.user-group'), 0, $user_id));
				}
			}
		} elseif ($task == 'end-session') {
			$f3->get('DB')->exec("DELETE FROM user_session");
			$f3->get('DB')->exec("UPDATE sqlite_sequence SET seq=? where name=?", array(1, 'user_session'));
		} elseif ($task == 'delete-campaign-hits') {
			$f3->get('DB')->exec("UPDATE campaign SET hit=0");
		} elseif ($task == 'delete-data-visit') {
			$f3->get('DB')->exec("DELETE FROM visitor");
			$f3->get('DB')->exec("DELETE FROM page_view");
			$f3->get('DB')->exec("UPDATE sqlite_sequence SET seq=? where name=?", array(1, 'page_view'));
			$f3->get('DB')->exec("DELETE FROM referrer");
			$f3->get('DB')->exec("UPDATE sqlite_sequence SET seq=? where name=?", array(1, 'referrer'));
		}

		$f3->reroute('/supersettings');
	}
}