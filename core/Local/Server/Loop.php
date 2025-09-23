<?php ##########################################################################
################################################################################

namespace Local\Server;

use React;
use Nether\Common;
use Nether\Console;

################################################################################
################################################################################

class Loop
extends Common\Prototype {

	public Console\Client
	$Term;

	public React\EventLoop\LoopInterface
	$API;

	protected bool
	$Halt = FALSE;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetAPI():
	React\EventLoop\LoopInterface {

		return $this->API;
	}

	public function
	GetCurrentDateTimeStamp():
	string {

		return (new Common\Date)->Get(Common\Values::DateFormatYMDT24V);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	SetAPI(React\EventLoop\LoopInterface $API):
	static {

		$this->API = $API;

		return $this;
	}

	public function
	SetHalt(bool $State):
	static {

		$this->Halt = $State;

		return $this;
	}

	public function
	SetTerminal(Console\Client $Term):
	static {

		$this->Term = $Term;

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Run():
	void {

		// override in child class

		// todo: move to an interface.

		return;
	}

};
