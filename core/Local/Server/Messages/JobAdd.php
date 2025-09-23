<?php ##########################################################################
################################################################################

namespace Local\Server\Messages;

use Local\Server;
use Local\Queue;
use Nether\Common;

################################################################################
################################################################################

class JobAdd
extends Server\Message
implements Server\Messages\ServerProcessInterface {

	#[Common\Meta\PropertyListable]
	public string
	$Cmd = 'JobAdd';

	#[Common\Meta\PropertyListable]
	#[Common\Meta\PropertyFactory('FromArray')]
	public array|Queue\Job
	$Job;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Process(Server\Loops\Run $Loop, Server\CommsRemote $Socket):
	void {

		$this->Job->SetDatabase($Loop->GetStackDatabase());
		$this->Job->Save();

		////////

		$Message = Server\Messages\JobAdded::FromJob($this->Job);

		$Socket->API->write("{$Message->ToSlimJSON()}\n");

		////////

		$Loop->Term->PrintLn(sprintf(
			'[%s] [Job Add] [%s]',
			$Loop->GetCurrentDateTimeStamp(),
			$this->Job->UUID
		));

		$Loop->Kick();

		return;
	}

};
