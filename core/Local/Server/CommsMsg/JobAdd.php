<?php ##########################################################################
################################################################################

namespace Local\Server\CommsMsg;

use React;
use Local\Server;
use Local\Queue;
use Nether\Common;

################################################################################
################################################################################

class JobAdd
extends Server\CommsMsg {

	#[Common\Meta\PropertyFactory('FromArray')]
	public array|Queue\Job
	$Job = [];

	public Server\Loops\Run
	$Loop;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Process(Server\CommsRemote $Socket):
	static {

		$this->Job->SetDatabase($this->Loop->GetStackDatabase());
		$this->Job->Save();

		$Socket->API->write("{$this->Job->UUID}\n");
		$this->Loop->Kick();

		$this->Loop->Term->PrintLn(sprintf(
			'[%s] [Job Add] [%s]',
			$this->Loop->GetCurrentDateTimeStamp(),
			$this->Job->UUID
		));

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	FromData(Server\Loop $Loop, array $Data):
	static {


		$Data['Job']['JData'] = json_encode($Data['Job']['JData']);

		$Output = new static($Data);
		$Output->Loop = $Loop;

		return $Output;
	}

};
