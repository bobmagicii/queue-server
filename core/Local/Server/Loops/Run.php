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

	#[Common\Meta\PropertyObjectify]
	#[Common\Meta\Info('The entire job stack.')]
	public Queue\Stack
	$Stack;

	#[Common\Meta\PropertyObjectify]
	#[Common\Meta\Info('Currently running jobs.')]
	public Common\Datastore
	$Jobs;

	#[Common\Meta\PropertyObjectify]
	#[Common\Meta\Info('Local socket interface.')]
	public Server\Comms
	$Comms;

	////////////////////////////////
	////////////////////////////////

	#[Common\Meta\Info('Timer for when stack begins an IDLE state.')]
	protected ?React\EventLoop\TimerInterface
	$TimerIdle = NULL;

	////////////////////////////////
	////////////////////////////////

	#[Common\Meta\Info('Maximum concurrent running jobs.')]
	protected int
	$MaxJobs = 2;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Common\Prototype\ConstructArgs $Args):
	void {

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetStackDatabase():
	Queue\DB {

		return $this->Stack->GetDatabase();
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	SetComms(Server\Comms $Server):
	static {

		$this->Comms = $Server;
		//$this->Comms->SetTerm($this->Term);
		//$this->Comms->SetLoop($this);
		//$this->Comms->Open();

		return $this;
	}

	public function
	SetStack(Queue\Stack $Stack):
	static {

		$this->Stack = $Stack;

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

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

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

		if($this->Jobs->Count() >= $this->MaxJobs)
		return;

		if($this->Halt) {
			if($this->Jobs->Count() === 0) {
				$this->API->stop();
			}

			return;
		}

		////////

		try {
			$Job = $this->Stack->Next();
			$this->JobStart($Job);

			if($this->Jobs->Count() < $this->MaxJobs)
			$this->Kick();
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
	New(
		Console\Client $Client,
		string $StackDB, bool $StackFresh=FALSE,
		string $SocketAddr='127.0.0.1:42001'
	):
	static {

		$Output = new static;
		$Output->SetAPI(React\EventLoop\Loop::Get());
		$Output->SetTerminal($Client);

		$Output->Stack->SetDatabaseFile($StackDB);
		$Output->Stack->Open($StackFresh);

		$Output->Comms->SetLoop($Output);
		$Output->Comms->SetAddress($SocketAddr);
		$Output->Comms->Open();

		//$Output->SetComms(Server\Comms::New($SocketAddr));

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
