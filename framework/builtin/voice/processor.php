<?php

/**

 * [MxWeixin System] Copyright (c) 2014 WEIXIN.MX

 * MxWeixin is NOT a free software, it under the license terms, visited http://yqhls.cn/ for more details.

 */

defined('IN_IA') or exit('Access Denied');



class VoiceModuleProcessor extends WeModuleProcessor {

	public function respond() {

		global $_W;

		$rid = $this->rule;

		$sql = "SELECT `mediaid` FROM " . tablename('voice_reply') . " WHERE `rid`=:rid";

		$mediaid = pdo_fetchcolumn($sql, array(':rid' => $rid));

		if (empty($mediaid)) {

			return false;

		}

		return $this->respVoice($mediaid);

	}

}

