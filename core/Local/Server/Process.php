<?php ##########################################################################
################################################################################

namespace Local\Server;

use React;
use Nether\Common;

################################################################################
################################################################################

class Process
extends Common\Prototype {

	public React\ChildProcess\Process
	$API;

	public string
	$Command;

	public string
	$STDOUT = '';

	public ?int
	$ExitCode = NULL;

	public ?int
	$ExitSignal = NULL;

	public mixed
	$WhenStart = NULL;

	public mixed
	$WhenDone = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Start():
	static {

		$this->API = new React\ChildProcess\Process($this->Command);

		($this->API)
		->start();

		// i know you hate it but these are bound after the call to start
		// on purpose for reactphp reasons.

		($this->API)
		->on('exit', $this->OnExit(...));

		($this->API)
		->stdout
		->on('data', $this->OnStandardOutput(...));

		if(is_callable($this->WhenStart))
		($this->WhenStart)($this);

		////////

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnStandardOutput(string $Data):
	void {

		$this->STDOUT .= $Data;

		return;
	}

	public function
	OnExit(?int $Code, ?int $Signal):
	void {

		$this->ExitCode = $Code;
		$this->ExitSignal = $Signal;

		if(is_callable($this->WhenDone))
		($this->WhenDone)($this);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	New(string $Command, ?callable $OnStart=NULL, ?callable $OnDone=NULL):
	static {

		$Output = new static;
		$Output->Command = $Command;
		$Output->WhenStart = $OnStart;
		$Output->WhenDone = $OnDone;

		return $Output;
	}

};
