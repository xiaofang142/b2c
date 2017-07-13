<?php

/**

 * [MxWeixin System] Copyright (c) 2014 WEIXIN.MX

 * MxWeixin is NOT a free software, it under the license terms, visited http://yqhls.cn/ for more details.

 */

defined('IN_IA') or exit('Access Denied');



class MusicModuleProcessor extends WeModuleProcessor {

	public function respond() {

		global $_W;

		$rid = $this->rule;

		$sql = "SELECT * FROM " . tablename('music_reply') . " WHERE `rid`=:rid ORDER BY RAND()";

		$item = pdo_fetch($sql, array(':rid' => $rid));

		if (empty($item['id'])) {

			return false;

		}

		return $this->respMusic(array(

			'Title'	=> $item['title'],

			'Description' => $item['description'],

			'MusicUrl' => $item['url'],

			'HQMusicUrl' => $item['hqurl'],

		));

	}

}

