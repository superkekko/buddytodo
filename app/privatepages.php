<?php
class privatepages extends authentication {
	function beforeroute($f3) {

		if (!$this->checklogged($f3)) {
			$f3->reroute("/logout");
		}
		
		$current_user = $f3->get('active_user');
		
		$lists_raw = $f3->get('DB')->exec("SELECT distinct list FROM task where user_ins = ? or (group_id = ? and share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));
		$lists = [];
		foreach ($lists_raw as $item) {
			$lists[] = $item['list'];
		}
		$lists = array_unique($lists);
		asort($lists);
		$f3->set('list', $lists);

		$tags_raw = $f3->get('DB')->exec("SELECT distinct tags FROM task where user_ins = ? or (group_id = ? and share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));
		$tags = [];
		foreach ($tags_raw as $item) {
			foreach (explode(',', $item['tags']) as $subitem) {
				$tags[] = $subitem;
			}
		}
		$tags = array_unique($tags);
		asort($tags);
		$f3->set('tags', $tags);
	}

	function afterroute($f3) {
		$f3->set('site_url', $this->siteURL());
		echo Template::instance()->render('private-layout.html');
	}

	function allview($f3) {
		$current_user = $f3->get('active_user');

		$results = $f3->get('DB')->exec("SELECT t.*, (select count(1) from time_track tr where tr.task_id=t.id and end_time is null) as open_count FROM task t where t.user_ins = ? or (t.group_id = ? and t.share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));

		$tasks = [];
		foreach ($results as $result) {
			$times = $f3->get('DB')->exec("SELECT * from time_track where task_id=?", $result['id']);

			$work_seconds = 0;
			foreach ($times as $time) {
				$date_start = strtotime($time['start_time']);
				if (empty($time['end_time'])) {
					$date_end = time();
				} else {
					$date_end = strtotime($time['end_time']);
				}
				$work_seconds = $work_seconds + floor($date_end - $date_start);
			}

			$result['work_seconds'] = $work_seconds;
			$tasks[] = $result;
		}

		$f3->set('task', $tasks);
		$f3->set('content', 'private-item.html');
	}

	function lists($f3) {
		$current_user = $f3->get('active_user');

		$results = $f3->get('DB')->exec("SELECT distinct list FROM task where user_upd = ? or (group_id = ? and share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));
		$alllist = [];
		foreach ($results as $result) {
			$alllist[] = $result['list'];
		}

		if (($key = array_search("", $alllist)) !== false) {
			unset($alllist[$key]);
		}

		$f3->set('list_item', $alllist);

		$f3->set('content', 'private-list.html');
	}

	function listview($f3) {
		$current_user = $f3->get('active_user');
		$id = $f3->get('PARAMS.id');

		$results = $f3->get('DB')->exec("SELECT t.*, (select count(1) from time_track tr where tr.task_id=t.id and end_time is null) as open_count FROM task t where (t.user_upd = ?  or (group_id = ? and share = ?)) and t.list = ?", array($current_user['user_id'], $id));

		$tasks = [];
		foreach ($results as $result) {
			$times = $f3->get('DB')->exec("SELECT * from time_track where task_id=?", $result['id']);

			$work_seconds = 0;
			foreach ($times as $time) {
				$date_start = strtotime($time['start_time']);
				if (empty($time['end_time'])) {
					$date_end = time();
				} else {
					$date_end = strtotime($time['end_time']);
				}
				$work_seconds = $work_seconds + floor($date_end - $date_start);
			}

			$result['work_seconds'] = $work_seconds;
			$tasks[] = $result;
		}

		$f3->set('task', $tasks);
		$f3->set('content', 'private-item.html');
	}

	function tags($f3) {
		$current_user = $f3->get('active_user');

		$results = $f3->get('DB')->exec("SELECT distinct tags FROM task where user_upd = ? or (group_id = ? and share = ?)", array($current_user['user_id'], $current_user['group_id'], 1));
		$alltags = [];

		foreach ($results as $result) {
			$alltags = array_merge($alltags, explode(',', $result['tags']));
		}

		if (($key = array_search("", $alltags)) !== false) {
			unset($alltags[$key]);
		}

		$f3->set('list_item', array_unique($alltags));

		$f3->set('content', 'private-tag.html');
	}

	function tagview($f3) {
		$current_user = $f3->get('active_user');
		$id = $f3->get('PARAMS.id');

		$results = $f3->get('DB')->exec("SELECT t.*, (select count(1) from time_track tr where tr.task_id=t.id and end_time is null) as open_count FROM task t where (t.user_upd = ? or (group_id = ? and share = ?)) and (',' || t.tags || ',') LIKE ?", array($current_user['user_id'], '%,'.$id.',%'));

		$tasks = [];
		foreach ($results as $result) {
			$times = $f3->get('DB')->exec("SELECT * from time_track where task_id=?", $result['id']);

			$work_seconds = 0;
			foreach ($times as $time) {
				$date_start = strtotime($time['start_time']);
				if (empty($time['end_time'])) {
					$date_end = time();
				} else {
					$date_end = strtotime($time['end_time']);
				}
				$work_seconds = $work_seconds + floor($date_end - $date_start);
			}

			$result['work_seconds'] = $work_seconds;
			$tasks[] = $result;
		}

		$f3->set('task', $tasks);
		$f3->set('content', 'private-item.html');
	}

	function edit($f3) {
		$current_user = $f3->get('active_user');

		$id = $f3->get('POST.id');
		$task = trim($f3->get('POST.task'));
		
		if(!empty($f3->get('POST.share'))){
			$share = 1;
		}else{
			$share = 0;
		}

		if ($task == 'delete') {
			$f3->get('DB')->exec("DELETE FROM task WHERE id=?", $id);
		} elseif ($task == 'complete') {
			$f3->get('DB')->exec("UPDATE task SET comp_date=?, user_upd=?, time_upd=? WHERE id=?", array(date("Y-m-d H:i:s"), $current_user['user_id'], date("Y-m-d H:i:s"), $id));
		} elseif ($task == 'uncomplete') {
			$f3->get('DB')->exec("UPDATE task SET comp_date=?, user_upd=?, time_upd=? WHERE id=?", array('', $current_user['user_id'], date("Y-m-d H:i:s"), $id));
		} elseif ($task == 'start-count') {
			$f3->get('DB')->exec("INSERT INTO time_track (task_id, start_time, user_ins, time_ins, user_upd, time_upd) VALUES (?,?,?,?,?,?)", array($id, date("Y-m-d H:i:s"), $current_user['user_id'], date("Y-m-d H:i:s"), $current_user['user_id'], date("Y-m-d H:i:s")));
		} elseif ($task == 'end-count') {
			$results = $f3->get('DB')->exec("SELECT * FROM time_track where task_id=? AND end_time is null", $id);
			$f3->get('DB')->exec("UPDATE time_track SET end_time=?, user_upd=?, time_upd=? WHERE id=?", array(date("Y-m-d H:i:s"), $current_user['user_id'], date("Y-m-d H:i:s"), $results[0]['id']));
		} elseif ($task == 'edit') {
			if ($f3->get('POST.tags') == '') {
				$tags = null;
			} else {
				$tags = implode(',', $f3->get('POST.tags'));
			}

			if ($id == 0) {
				$f3->get('DB')->exec("INSERT INTO task (name, tags, list, due_date, comp_date, group_id, share, user_ins, time_ins, user_upd, time_upd) VALUES (?,?,?,?,?,?,?,?,?,?,?)", array($f3->get('POST.name'), $tags, $f3->get('POST.list'), $f3->get('POST.due-date'), '', $current_user['group_id'], $share, $current_user['user_id'], date("Y-m-d H:i:s"), $current_user['user_id'], date("Y-m-d H:i:s")));
			} else {
				$f3->get('DB')->exec("UPDATE task SET name=?, tags=?, list=?, due_date=?, share=?, user_upd=?, time_upd=? WHERE id=?", array($f3->get('POST.name'), $tags, $f3->get('POST.list'), $f3->get('POST.due-date'), $share, $current_user['user_id'], date("Y-m-d H:i:s"), $id));
			}
		} elseif ($task == 'add-file') {
			$file = $f3->get('FILES.upload');

			$reader = Box\Spout\Reader\Common\Creator\ReaderEntityFactory::createXLSXReader();

			$reader->open($file['tmp_name']);
			$first_row = true;

			foreach ($reader->getSheetIterator() as $sheet) {
				foreach ($sheet->getRowIterator() as $row) {
					if ($first_row) {
						$first_row = false;
						continue;
					} else {
						$cells = $row->getCells();

						$task_name = $cells[0]->getValue();
						$task_tags = $cells[1]->getValue();
						$task_list = $cells[2]->getValue();
						if(!empty($cells[3])){
							$task_due_date = $cells[3]->getValue();	
						}else{
							$task_due_date = '';
						}
						
						$f3->get('DB')->exec("INSERT INTO task (name, tags, list, due_date, comp_date, user_ins, time_ins, user_upd, time_upd) VALUES (?,?,?,?,?,?,?,?,?)", array($task_name, $task_tags, $task_list, $task_due_date, '', $current_user['user_id'], date("Y-m-d H:i:s"), $current_user['user_id'], date("Y-m-d H:i:s")));
					}
				}
			}
			$reader->close();
		}

		$f3->reroute($f3->get('URI'));
	}
}