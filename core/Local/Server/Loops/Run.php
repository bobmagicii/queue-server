<?php ##########################################################################
################################################################################

namespace Local\Server\Loops;

use React;
use Local\Queue;
use Local\Server;
use Nether\Common;
use Nether\Console;

################################################################################
################################################################################

class Run
extends Server\Loop {

	public Console\Client
	$Term;

	protected React\EventLoop\LoopInterface
	$API;

	protected Queue\Stack
	$Stack;

	#[Common\Meta\PropertyObjectify]
	protected Common\Datastore
	$Jobs;

	protected int
	$JobsMax = 2;

	protected Server\Comms
	$Comms;

	protected ?React\EventLoop\TimerInterface
	$TimerIdle = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetAPI():
	React\EventLoop\LoopInterface {

		return $this->API;
	}

	public function
	GetStackDatabase():
	Queue\DB {

		return $this->Stack->GetDatabase();
	}

	public function
	GetCurrentDateTimeStamp():
	string {

		return (new Common\Date)->Get(Common\Values::DateFormatYMDT24V);
	}

	public function
	SetComms(Server\Comms $Server):
	static {

		$this->Comms = $Server;
		$this->Comms->SetTerm($this->Term);
		$this->Comms->SetLoop($this);
		$this->Comms->Open();

		////////

		($this->API)
		->futureTick($this->OnCommsSet(...));

		////////

		return $this;
	}

	public function
	SetStack(Queue\Stack $Stack):
	static {

		$this->Stack = $Stack;

		//$Job = $this->Stack->NewJob('test', [ 'arg' => 'one' ]);
		//$Job->SetTimeStartAfter(Common\Date::Unixtime() + 10);
		//$Job->Save();

		////////

		Console\Elements\H2::New(
			Client: $this->Term,
			Text: 'Job Stack',
			Print: 2
		);

		Console\Elements\ListNamed::New(
			Client: $this->Term,
			Items: [
				'DB'           => $this->Stack->GetDatabaseName(),
				'Pending Jobs' => $this->Stack->FetchCountPending(),
				'Future Jobs'  => $this->Stack->FetchCountFuture()
			],
			Print: 2
		);

		////////

		return $this;
	}

	public function
	SetTerminal(Console\Client $Term):
	static {

		$this->Term = $Term;

		////////

		($this->API)
		->futureTick($this->OnTerminalSet(...));

		////////

		return $this;
	}

	public function
	Run():
	void {

		Console\Elements\H2::New(
			Client: $this->Term,
			Text: 'Log',
			Print: 2
		);

		($this->API)
		->futureTick($this->Next(...));

		($this->API)
		->run();

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Kick():
	void {

		($this->API)
		->futureTick($this->Next(...));

		return;
	}

	public function
	Next():
	void {

		$Now = Common\Date::Unixtime();
		$Err = NULL;
		$Job = NULL;

		////////

		if($this->Jobs->Count() >= $this->JobsMax)
		return;

		////////

		try {
			$Job = $this->Stack->Next();
			$this->JobStart($Job);
		}

		catch(Queue\Error\QueueIdle $Err) {
			$this->ResetTimerIdle($Err->Until - $Now);

			if($this->Jobs->Count() === 0)
			$this->Term->PrintLn(sprintf(
				'[%s] [Queue] Idle (%s)',
				$this->GetCurrentDateTimeStamp(),
				new Common\Units\Timeframe($Now, $Err->Until)
			));
		}

		catch(Queue\Error\QueueEmpty $Err) {
			if($this->Jobs->Count() === 0)
			$this->Term->PrintLn(sprintf(
				'[%s] [Queue] Empty',
				$this->GetCurrentDateTimeStamp()
			));
		}

		return;
	}

	public function
	ResetTimerIdle(int $Delay=1):
	void {

		if($this->TimerIdle)
		$this->API->cancelTimer($this->TimerIdle);

		////////

		$this->TimerIdle = $this->API->addTimer($Delay, $this->Kick(...));

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	JobStart(Queue\Job $Job):
	static {

		if($Job->GetType() === 'test') {
			$this->JobStartTest($Job);
		}

		if($Job->GetType() === 'shellcmd') {
			$this->JobStartShellCmd($Job);
		}

		return $this;
	}

	protected function
	JobStartTest(Queue\Job $Job):
	static {

		$this->Jobs[$Job->UUID] = $Job;
		$Job->Start();

		////////

		$Fork = Server\Process::New(
			Command: 'sleep 4',
			OnDone: (fn()=> $this->JobComplete($Job))
		);

		$Fork->Start();

		$this->Term->PrintLn(sprintf(
			'[%s] [Job Start] [%s] %s',
			$this->GetCurrentDateTimeStamp(),
			$Job->UUID,
			$Fork->Command
		));

		return $this;
	}

	protected function
	JobStartShellCmd(Queue\Job $Job):
	static {

		$this->Jobs[$Job->UUID] = $Job;
		$Job->Start();
		$JData = $Job->GetData();

		////////

		$Fork = Server\Process::New(
			Command: $JData['Cmd'],
			OnDone: (fn()=> $this->JobComplete($Job))
		);

		$Fork->Start();

		$this->Term->PrintLn(sprintf(
			'[%s] [Job Start] [%s] %s',
			$this->GetCurrentDateTimeStamp(),
			$Job->UUID,
			$Fork->Command
		));

		////////

		return $this;
	}

	public function
	JobComplete(Queue\Job $Job):
	static {

		$Job->Complete();
		unset($this->Jobs[$Job->UUID]);

		////////

		$this->Term->PrintLn(sprintf(
			'[%s] [Job Done] [%s]',
			$this->GetCurrentDateTimeStamp(),
			$Job->UUID
		));

		$this->Kick();

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnStackSet():
	void {

		return;
	}

	protected function
	OnTerminalSet():
	void {

		return;
	}

	protected function
	OnCommsSet():
	void {

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnSignal(int $Signal):
	void {

		$this->Term->PrintLn();

		Console\Elements\H4::New(
			Client: $this->Term,
			Text: sprintf('Signal: %d', $Signal),
			Print: 2
		);

		////////

		if($Signal === SIGINT) {
			$this->Term->PrintLn('Shutting down.', 2);
			$this->API->Stop();
		}

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	New():
	static {

		$Output = new static;
		$Output->API = React\EventLoop\Loop::Get();

		////////

		($Output->API)
		->addSignal(
			SIGINT,
			$Output->OnSignal(...)
		);

		////////

		return $Output;
	}

};
