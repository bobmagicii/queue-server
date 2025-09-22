<?php ##########################################################################
################################################################################

namespace Local\Queue\Error;

use Exception;

################################################################################
################################################################################

class QueueIdle
extends Exception {

	public ?int
	$Until;

	public function __Construct(?int $Until=NULL) {
		parent::__Construct('queue is idle');

		$this->Until = $Until;

		return;
	}

}
