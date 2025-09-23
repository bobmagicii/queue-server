<?php ##########################################################################
################################################################################

namespace Local\Server;

use React;
use Local\Server;
use Local\Queue;
use Local\Client;
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
	$SocketAddr;

	protected string
	$StackDB;

	protected string
	$DBName;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnPrepare():
	void {

		// todo: config file

		$this->AppRoot = $this->GetOption('AppRoot');
		$this->SocketAddr = '127.0.0.1:42001';
		$this->DBName = 'queue.sqlite';
		$this->StackDB = Common\Filesystem\Util::Pathify($this->AppRoot, $this->DBName);

		Console\Elements\H2::New(
			Client: $this,
			Text: 'Config',
			Print: 2
		);

		Console\Elements\ListNamed::New(
			Client: $this,
			Items: [
				'AppRoot'   => $this->AppRoot,
				'SocketAddr' => $this->SocketAddr,
				'StackDB'    => $this->StackDB
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
	#[Console\Meta\Value('--socket', 'Socket server addr:port.')]
	#[Console\Meta\Value('--db', 'Path to SQLite database.')]
	#[Common\Meta\Date('2025-09-21')]
	public function
	HandleRun():
	int {

		$OptStackDB = $this->GetOption('--db') ?: $this->StackDB;
		$OptSocketAddr = $this->GetOption('--socket') ?: $this->SocketAddr;
		$OptFresh = (bool)$this->GetOption('fresh');

		////////

		$Loop = Loops\Run::New(
			Client:     $this,
			StackDB:    $OptStackDB,
			StackFresh: $OptFresh,
			SocketAddr: $OptSocketAddr
		);

		$Loop->Run();

		return 0;
	}

	#[Console\Meta\Command('status')]
	#[Console\Meta\Info('Print queue server status.')]
	#[Common\Meta\Date('2025-09-22')]
	public function
	HandleStatus():
	int {

		$Message = new Server\Messages\StatusQuery;

		(new Client\Socket)
		->SetDataFunc(function(Client\Socket $C, Server\Message $Data) {

			if($Data instanceof Server\Messages\StatusResponse)
			Console\Elements\ListNamed::New(
				Client: $this,
				Items: [
					'Running' => $Data->NumRunning,
					'Pending' => $Data->NumPending,
					'Future'  => $Data->NumFuture
				],
				Print: 2
			);

			$C->Disconnect();
			return;
		})
		->Connect($this->SocketAddr)
		->Send($Message);

		return 0;
	}

	#[Console\Meta\Command('cmd')]
	#[Console\Meta\Info('Adds a shellcmd item to the queue.')]
	#[Console\Meta\Arg('...', 'The command to run.')]
	#[Common\Meta\Date('2025-09-22')]
	public function
	HandleQueueCmd():
	int {

		$OG = (array)$_SERVER['argv'];
		$Mark = array_search('cmd', $OG);
		$Args = Common\Datastore::FromArray(array_slice($OG, $Mark + 1));
		$Cmd = $Args->Join(' ');

		////////

		$Message = new Server\Messages\JobAdd([
			'Job' => Queue\Job::FromArray([
				'JType' => 'shellcmd',
				'JData' => json_encode([ 'Cmd' => $Cmd ])
			])
		]);

		(new Client\Socket)
		->SetDataFunc(function(Client\Socket $C, Server\Message $Data) {

			if($Data instanceof Server\Messages\JobAdded)
			Console\Elements\ListNamed::New(
				Client: $this,
				Items: [ 'Job UUID' => $Data->JobUUID ],
				Print: 2
			);

			$C->Disconnect();
			return;
		})
		->Connect($this->SocketAddr)
		->Send($Message);

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

	protected function
	GetPharFileFilters():
	Common\Datastore {

		$Output = parent::GetPharFileFilters();

		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/monolog'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/dealerdirect'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/fileeye'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/squizlabs'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/fileeye'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/psr'));

		return $Output;
	}

};
