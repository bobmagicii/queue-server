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
	public function
	HandleRun():
	int {

		$OptStackDB = $this->GetOption('--db') ?: $this->StackDB;
		$OptSocketAddr = $this->GetOption('--socket') ?: $this->SocketAddr;
		$OptFresh = (bool)$this->GetOption('fresh');

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
	public function
	HandleStatus():
	int {

		// send status query over socket.

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
				'JType' => 'shellcmd',
				'JData' => [
					'Cmd' => $Cmd
				]
			]
		]);

		$Client = new React\Socket\Connector;

		($Client)
		->connect($this->SocketAddr)
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

	protected function
	GetPharFileFilters():
	Common\Datastore {

		$Output = parent::GetPharFileFilters();

		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/monolog'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/dealerdirect'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/fileeye'));
		$Output->Push(fn(string $Path)=> !str_starts_with($Path, 'vendor/squizlabs'));

		return $Output;
	}

};
