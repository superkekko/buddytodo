<?php
class api extends authentication {
	function beforeroute($f3) {}

	function afterroute($f3) {}

	function read($f3) {
		if (!$this->checklogged($f3)) {
			$f3->reroute("/logout");
		}

		$current_user = $f3->get('active_user');
		$type = $f3->get('GET.type');

		if ($type == 'notificationCount') {
			$result = $f3->get('DB')->exec("SELECT count(1) as count FROM task where user_upd = ? and due_date < ? and due_date <> ''", array($current_user['user_id'], date("Y-m-d H:i:s")));

			header('Content-Type: application/json');
			if (!empty($result) && $result[0]['count'] != 0) {
				echo json_encode($result[0]);
			} else {
				http_response_code(204);
			}
			exit;
		} elseif ($type == 'notification') {
			$result = $f3->get('DB')->exec("SELECT name FROM task where user_upd = ? and due_date < ? and due_date <> ''", array($current_user['user_id'], date("Y-m-d H:i:s")));

			header('Content-Type: application/json');
			if (!empty($result)) {
				echo json_encode($result);
			} else {
				http_response_code(204);
			}
			exit;
		} else {
			header('Content-Type: application/json');
			http_response_code(400);
		}
	}

	function jsontask($f3) {
		if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'];
			list($tokenType, $token) = explode(' ', $authorizationHeader);

			if ($tokenType === 'Bearer' && !empty($token)) {
				$user = $f3->get('DB')->exec("SELECT * FROM user where bearer=?", $token);

				if (!empty($user[0])) {
					$postData = json_decode(file_get_contents('php://input'), true);

					$result = $f3->get('DB')->exec("SELECT t.name, t.tags, t.list, t.due_date, t.comp_date, t.share, (select sum(ROUND((JULIANDAY(end_time) - JULIANDAY(start_time)) * 86400)) from time_track tr where tr.task_id=t.id and end_time is not null) as time_count FROM task t where t.user_ins = ? or (t.group_id = ? and t.share = ?)", array($user[0]['user_id'], $user[0]['group_id'], 1));

					$return_array = ['data' => $result];
					header('Content-Type: application/json');
					echo json_encode($return_array);
				}
			} else {
				header('Content-Type: application/json');
				http_response_code(400);
			}
		} else {
			header('Content-Type: application/json');
			http_response_code(400);
		}
	}
}