<?php ##########################################################################
################################################################################

namespace Local\Server\Messages;

use Local\Queue;
use Local\Server;
use Nether\Common;

################################################################################
################################################################################

class StatusQuery
extends Server\Message
implements Server\Messages\ServerProcessInterface {

	#[Common\Meta\PropertyListable]
	public string
	$Cmd = 'StatusQuery';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Process(Server\Loops\Run $Loop, Server\CommsRemote $Socket):
	void {

		$Message = new Server\Messages\StatusResponse;
		$Message->NumRunning = $Loop->Jobs->Count();
		$Message->NumPending = $Loop->Stack->FetchCountPending();
		$Message->NumFuture = $Loop->Stack->FetchCountFuture();

		$Socket->API->write("{$Message->ToSlimJSON()}\n");

		return;
	}

};
