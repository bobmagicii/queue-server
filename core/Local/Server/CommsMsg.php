<?php ##########################################################################
################################################################################

namespace Local\Server;

use Nether\Common;
use Local\Server;

################################################################################
################################################################################

class CommsMsg
extends Common\Prototype {

	public string
	$Cmd;

	public function
	Process(CommsRemote $Seocket):
	static {

		return $this;
	}

};
