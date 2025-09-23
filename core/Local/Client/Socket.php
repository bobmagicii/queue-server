<?php ##########################################################################
################################################################################

namespace Local\Client;

use React;
use Nether\Common;
use Local\Server;
use Local\Queue;

################################################################################
################################################################################

class Socket
extends Common\Prototype {

	protected React\Socket\ConnectorInterface
	$API;

	protected React\Socket\ConnectionInterface
	$CTX;

	protected React\Promise\PromiseInterface
	$PTX;

	////////

	protected mixed
	$InputDataFunc = NULL;

	protected string
	$InputBuffer = '';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Connect(string $Address):
	static {

		$this->API = new React\Socket\Connector;
		$this->PTX = $this->API->connect($Address);
		$this->InputBuffer = '';

		////////

		($this->PTX)
		->then(function(React\Socket\ConnectionInterface $C) {
			$this->CTX = $C;
			$this->CTX->on('data', $this->OnDataRecv(...));
			return;
		});

		return $this;
	}

	public function
	Disconnect():
	static {

		$this->CTX->close();

		return $this;
	}

	public function
	Send(mixed $Message):
	static {

		if(!is_string($Message))
		$Message = json_encode($Message);

		////////

		$this->PTX = (
			($this->PTX)
			->then(fn()=> $this->Write($Message))
		);

		return $this;
	}

	protected function
	Write(mixed $Message):
	void {

		$this->CTX->write("{$Message}\n");

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	SetDataFunc(callable $Func):
	static {

		$this->InputDataFunc = $Func;

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnDataRecv(string $Data):
	void {

		$this->InputBuffer .= $Data;

		////////

		if(!str_contains($this->InputBuffer, "\n"))
		return;

		////////

		while($Pos = mb_strpos($this->InputBuffer, "\n")) {

			// read and trim the buffer.

			$Line = mb_substr($this->InputBuffer, 0, $Pos);
			$this->InputBuffer = mb_substr($this->InputBuffer, ($Pos + 1));

			// decode the message.

			if(is_callable($this->InputDataFunc))
			($this->InputDataFunc)($this, Server\Message::FromJSON($Line));
			else
			echo $Line, PHP_EOL;

			return;
		}

		return;
	}

};
