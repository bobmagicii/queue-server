<?php ##########################################################################
################################################################################

namespace Local\Server;

use React;
use Local\Queue;
use Local\Server;
use Nether\Common;
use Nether\Console;

use Exception;
use WeakMap;

################################################################################
################################################################################

class Comms
extends Common\Prototype {

	#[Common\Meta\Info('Socket address to open in addr:port format.')]
	protected string
	$Address;

	////////////////////////////////
	////////////////////////////////

	protected React\Socket\ServerInterface
	$API;

	protected Server\Loop
	$Loop;

	#[Common\Meta\PropertyObjectify]
	protected WeakMap
	$Connections;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\Info('Open the socket to allow communication with the queue.')]
	public function
	Open():
	static {

		if(!isset($this->Loop))
		throw new Exception('no event loop spefified');

		if(!isset($this->Address))
		throw new Exception('no socket address specified');

		////////

		$this->API = new React\Socket\SocketServer(
			$this->Address,
			[],
			$this->Loop->GetAPI()
		);

		($this->API)
		->on(
			'connection',
			fn(React\Socket\ConnectionInterface $S)
			=> $this->OnConnect($S)
		);

		////////

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	SetAddress(string $Address):
	static {

		if(!str_contains($Address, ':'))
		throw new Exception('expecting addr:port format');

		////////

		$this->Address = $Address;

		return $this;
	}

	public function
	SetLoop(Server\Loop $Loop):
	static {

		$this->Loop = $Loop;

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnConnect(React\Socket\Connection $Socket):
	void {

		$Socket->on('data', function (string $Data) use($Socket) {
			$this->OnDataRecv($this->Connections[$Socket], $Data);
			return;
		});

		$Socket->on('error', function (Exception $e) use($Socket) {
			unset($this->Connections[$Socket]);
			return;
		});

		$Socket->on('close', function () use($Socket) {
			unset($this->Connections[$Socket]);
			return;
		});

		////////

		$this->Connections[$Socket] = new CommsRemote([
			'API' => $Socket
		]);

		return;
	}

	public function
	OnDataRecv(CommsRemote $Socket, string $Data):
	void {

		$Socket->DataBuffer .= $Data;

		////////

		$Pos = NULL;
		$Line = NULL;

		////////

		// PROTOCOL SPEC:
		// { "Cmd": "Swag", ... }\n

		if(!str_contains($Socket->DataBuffer, "\n"))
		return;

		while($Pos = mb_strpos($Socket->DataBuffer, "\n")) {

			// read and trim the buffer.

			$Line = mb_substr($Socket->DataBuffer, 0, $Pos);
			$Socket->DataBuffer = mb_substr($Socket->DataBuffer, ($Pos + 1));

			// decode the message.

			$Msg = Server\Message::FromJSON($Line);

			$this->Loop->Term->PrintLn(sprintf(
				'[%s] [Incoming Comms] %s',
				$this->Loop->GetCurrentDateTimeStamp(),
				$Msg->Cmd
			));

			if($Msg instanceof Server\Messages\ServerProcessInterface)
			$Msg->Process($this->Loop, $Socket);

			continue;
		}

		return;
	}

};
