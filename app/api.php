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
}