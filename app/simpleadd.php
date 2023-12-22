<?php
class simpleadd extends authentication {
	function beforeroute($f3) {
		if (!$this->checklogged($f3)) {
			$f3->reroute("/login");
		}

		$current_user = $f3->get('active_user');

		$lists_raw = $f3->get('DB')->exec("SELECT distinct list FROM link_list where user_upd = ?", $current_user['user_id']);
		$lists = [];
		foreach ($lists_raw as $item) {
			$lists[] = $item['list'];
		}
		$tags = array_unique($lists);
		asort($lists);
		$f3->set('list', $lists);

		$tags_raw = $f3->get('DB')->exec("SELECT distinct tags FROM link_list where user_upd = ?", $current_user['user_id']);
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
	}
	
	function linkadd($f3) {
		$url = $f3->get('GET.url');
		$title = $f3->get('GET.title');
		echo Template::instance()->render('private-link-add.html');
	}
	
	function linksave($f3) {
		$current_user = $f3->get('active_user');

		$id = $f3->get('POST.id');
		$task = $f3->get('POST.task');
		
		if($f3->get('POST.tags') == ''){
			$tags = null;
		}else{
			$tags = implode(',', $f3->get('POST.tags'));
		}

		$f3->get('DB')->exec("INSERT INTO link_list (name, link, tags, list, status, user_ins, time_ins, user_upd, time_upd) VALUES (?,?,?,?,?,?,?,?,?)",
		array($f3->get('POST.name'), $f3->get('POST.link'), $tags, $f3->get('POST.list'), '', $current_user['user_id'], date("Y-m-d H:i:s"), $current_user['user_id'], date("Y-m-d H:i:s")));

		echo Template::instance()->render('private-link-add-success.html');
	}

}