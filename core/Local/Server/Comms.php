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

	protected string
	$Address;

	protected React\Socket\ServerInterface
	$API;

	protected Server\Loops\Run
	$Loop;

	protected Console\Client
	$Term;

	#[Common\Meta\PropertyObjectify]
	protected WeakMap
	$Connections;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Open():
	static {

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

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	SetAddress(string $Address):
	static {

		$this->Address = $Address;

		return $this;
	}

	public function
	SetLoop(Server\Loops\Run $Loop):
	static {

		$this->Loop = $Loop;

		return $this;
	}

	public function
	SetTerm(Console\Client $Term):
	static {

		$this->Term = $Term;

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnConnect(React\Socket\Connection $Socket):
	void {

		$Socket->on('data', function (string $Data) use($Socket) {
			$this->OnData($this->Connections[$Socket], $Data);
			return;
		});

		$Socket->on('error', function (Exception $e) use($Socket) {
			unset($this->Connections[$Socket]);
			//echo 'error: ' . $e->getMessage(), PHP_EOL;
			return;
		});

		$Socket->on('close', function () use($Socket) {
			unset($this->Connections[$Socket]);
			//echo 'closed', PHP_EOL;
			return;
		});

		$this->Connections[$Socket] = new CommsRemote([
			'API' => $Socket
		]);

		return;
	}

	public function
	OnData(CommsRemote $Socket, string $Data):
	void {

		$Pos = NULL;
		$Line = NULL;

		////////

		$Socket->DataBuffer .= $Data;

		if(!str_contains($Socket->DataBuffer, "\n"))
		return;

		while($Pos = strpos($Socket->DataBuffer, "\n")) {

			// read and trim the buffer.

			$Line = substr($Socket->DataBuffer, 0, $Pos);
			$Socket->DataBuffer = substr($Socket->DataBuffer, $Pos + 1);

			// decode the message.

			$Data = json_decode($Line, TRUE);

			if(!is_array($Data))
			continue;

			if(!array_key_exists('Cmd', $Data))
			continue;

			////////

			if($Data['Cmd'] === 'JobAdd') {
				$Msg = CommsMsg\JobAdd::FromData($this->Loop, $Data);
				$Msg->Process($Socket);
				continue;
			}
		}

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	New(string $Address):
	static {

		$Output = new static;
		$Output->Address = $Address;

		return $Output;
	}

};
