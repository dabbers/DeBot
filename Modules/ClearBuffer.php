<?php

/**
 * Clears the message buffer for a server
 * @author ss23 <ss23@ss23.geek.nz>
 */

class m_ClearBuffer extends Module {

	public function init() {
		$this->onCommand('clearbuffer', array($this, 'clearBuffer'), 'msg');
	}

	public function clearBuffer($from, $user, $message) {
		$this->m_oBot['connection'][$this->m_oBot['in']->Network]->clearMessages();
		$this->m_oBot->msg($from, "Buffer cleared");
	}

}
