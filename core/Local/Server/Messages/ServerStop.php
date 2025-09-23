<?php ##########################################################################
################################################################################

namespace Local\Server\Messages;

use Local\Server;
use Local\Queue;
use Nether\Common;

################################################################################
################################################################################

class ServerStop
extends Server\Message
implements Server\Messages\ServerProcessInterface {

	#[Common\Meta\PropertyListable]
	public string
	$Cmd = 'ServerStop';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Process(Server\Loops\Run $Loop, Server\CommsRemote $Socket):
	void {

		$Loop->SetHalt(TRUE);

		////////

		if(!$Loop->Jobs->Count())
		$Loop->Kick();

		////////

		return;
	}

};
