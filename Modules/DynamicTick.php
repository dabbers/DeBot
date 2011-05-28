<?php

/**
 * Change the tick rate of your bot on the fly or automatically
 * @author ss23 <ss23@ss23.geek.nz>
 */

class m_DynamicTick extends GlobalModule {
	private $mode = 'auto';
	private $tickRate = 50000;

	/**
	 * Array of child modules bound to bots
	 */
	private $bots;

	/**
	 * The minimum amount tick rate we want to reach.
	 * Setting this below 2 will give you errors.
	 */
	private $minimumTick = 5;

	/**
	 * The maximum tick rate we want to reach.
	 * Setting this too low may rape your server.
	 * Keep this above 50000 unless you have a reason not to.
	 */
	private $maximumTick = 50000;

	public function init() {

		Timers::obj()->addTimer('', 60, -1, array($this, 'checkCPU'));
	}

	public function checkCPU() {
		if ($this->mode == 'auto') {
			// No idea if I'll bother with this on Windows
			if (function_exists('sys_getloadavg')) {
				$load = sys_getloadavg();
				if ($load[0] < .1) {
					// The load is low, let's slow down the tick rate.
					if ($this->tickRate > $this->minimumTick) {
						$this->tickRate = round($this->tickRate * 0.9);
					}
				} else if ($load[0] > .5) {
					// Looks like we're under a little bit of pressure
					if ($this->tickRate < $this->maximumTick) {
						$this->tickRate = round($this->tickRate * 1.1);
					}
				}
			}
		}
		Config::obj()->offsetSet('TickRate', $this->tickRate);
	}

	public function onMsg($oBot, $sFrom, $sTo, $sMsg, $sSpecial = null) {
		error_reporting(-1);
		$txt = explode(' ', strtolower($sMsg));
		
		if ($txt[0] == CMD . 'settick') {
			if (empty($txt[2]) || (count($txt) != 3) || (!ctype_digit($txt[2]) && $txt[2] != 'auto')) {
				$oBot->msg($sTo, "Syntax: '" . CMD . " setTick auto' or '" . CMD . " setTick 100'");
			} else {
				if ($txt[2] == "auto") {
					// Automatically set tick rate
					$this->mode = 'auto';
				} else {
					// Use a static numerical tick rate.
					$this->mode = 'static';
					$this->tickRate = (int)$txt[2];
				}
				$this->checkCPU();
			}
		} else if ($txt[0] == CMD.'gettick') {
			$oBot->msg($sTo, "Mode: " . $this->mode .", current rate: " . $this->tickRate);
		}
	}

}

