<?php ##########################################################################
################################################################################

namespace Local\Server\Messages;

use Local\Queue;
use Local\Server;
use Nether\Common;

################################################################################
################################################################################

class StatusResponse
extends Server\Message {

	#[Common\Meta\PropertyListable]
	public string
	$Cmd = 'StatusResponse';

	#[Common\Meta\PropertyListable]
	public int
	$NumRunning = 0;

	#[Common\Meta\PropertyListable]
	public int
	$NumPending = 0;

	#[Common\Meta\PropertyListable]
	public int
	$NumFuture = 0;

};
