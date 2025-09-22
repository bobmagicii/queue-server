<?php ##########################################################################
################################################################################

namespace Local\Server;

use React;
use Local\Server;
use Local\Queue;
use Nether\Common;
use Nether\Console;

################################################################################
################################################################################

#[Console\Meta\Application('Queue Server', '0.0.1-dev', Phar: 'queue.phar')]
class App
extends Console\Client {

	protected string
	$AppRoot;

	protected string
	$CommsAddr;

	protected string
	$DBFile;

	protected string
	$DBName;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnPrepare():
	void {

		$this->AppRoot = $this->GetOption('AppRoot');
		$this->CommsAddr = '127.0.0.1:42001';
		$this->DBName = 'queue.sqlite';
		$this->DBFile = Common\Filesystem\Util::Pathify($this->AppRoot, $this->DBName);

		Console\Elements\H2::New(
			Client: $this,
			Text: 'Config',
			Print: 2
		);

		Console\Elements\ListNamed::New(
			Client: $this,
			Items: [
				'AppRoot'   => $this->AppRoot,
				'CommsAddr' => $this->CommsAddr,
				'DBFile'    => $this->DBFile
			],
			Print: 2
		);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Console\Meta\Command('run')]
	#[Console\Meta\Info('Start queue server.')]
	#[Console\Meta\Toggle('--fresh', 'Start a fresh empty DB.')]
	public function
	HandleRun():
	int {

		$OptFresh = (bool)$this->GetOption('fresh');

		$Loop = Loops\Run::New();
		$Stack = Queue\Stack::New($this->DBFile, $OptFresh);
		$Comms = Server\Comms::New($this->CommsAddr);


		////////

		$Loop->SetTerminal($this);
		$Loop->SetStack($Stack);
		$Loop->SetComms($Comms);
		$Loop->Run();

		////////

		return 0;
	}

	#[Console\Meta\Command('status')]
	#[Console\Meta\Info('Print queue server status.')]
	public function
	HandleStatus():
	int {

		$Stack = Queue\Stack::New($this->DBFile);
		$NumPending = $Stack->FetchCountPending();

		Console\Elements\ListNamed::New(
			Client: $this,
			Items: [
				"Pending" => $NumPending
			],
			Print: 2
		);

		return 0;
	}

	#[Console\Meta\Command('cmd')]
	#[Console\Meta\Info('Adds a shellcmd item to the queue.')]
	#[Console\Meta\Arg('...', 'The command to run.')]
	public function
	HandleQueueCmd():
	int {

		$OG = (array)$_SERVER['argv'];
		$Mark = array_search('cmd', $OG);
		$Args = Common\Datastore::FromArray(array_slice($OG, $Mark + 1));
		$Cmd = $Args->Join(' ');

		$Msg = json_encode([
			'Cmd' => 'JobAdd',
			'Job' => [
				//'TimeStartAfter' => (Common\Date::Unixtime() + 10),
				'JType'          => 'shellcmd',
				'JData'          => [
					'Cmd' => $Cmd
				]
			]
		]);

		$Client = new React\Socket\Connector;

		($Client)
		->connect($this->CommsAddr)
		->then(function(React\Socket\ConnectionInterface $C) use($Msg) {
			$C->write("{$Msg}\n");
			$C->once('data', function(string $Data) use($C) {
				echo $Data, PHP_EOL;
				$C->close();
				return;
			});
			return;
		});

		return 0;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	GetPharFiles():
	Common\Datastore {

		$Index = parent::GetPharFiles();
		$Index->Push('core');

		return $Index;
	}

};
