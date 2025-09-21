<?php ##########################################################################
################################################################################

namespace Local\Server;

use React;
use Local\Server;
use Local\Queue;
use Nether\Common;

################################################################################
################################################################################

class CommsRemote
extends Common\Prototype {

	public React\Socket\ConnectionInterface
	$API;

	public string
	$DataBuffer = '';

};
