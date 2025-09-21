<?php ##########################################################################
################################################################################

namespace Local\Queue;

use Nether\Common;

################################################################################
################################################################################

class Stack {

	protected DB
	$DB;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Next():
	Job {

		$Now = Common\Date::Unixtime();
		$Next = NULL;

		// find if there is a job ready to be done.

		$Next = Job::Find([ 'Next' => $Now ]);

		if($Next->Count() === 1) {
			$Job = new Job($Next[0]);
			$Job->SetDatabase($this->DB);
			return $Job;
		}

		// find if there are jobs waiting to be done in the future.

		$Next = Job::Find([ 'Future' => $Now ]);

		if($Next->Total() >= 1)
		throw new Error\QueueIdle;

		////////

		throw new Error\QueueEmpty;
		return new $Job;
	}

	public function
	NewJob(string $Type, mixed $Data):
	Job {

		if(!is_string($Data))
		$Data = json_encode($Data);

		////////

		$Job = new Job([
			'DB'          => $this->DB,
			'TimeCreated' => Common\Date::Unixtime(),
			'JType'       => $Type,
			'JData'       => $Data
		]);

		////////

		return $Job;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetDatabase():
	DB {

		return $this->DB;
	}

	public function
	GetDatabaseName():
	string {

		return $this->DB->GetDatabaseName();
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	FetchCountPending():
	int {

		$Now = Common\Date::Unixtime();
		$SQL = $this->DB->NewVerseQuery();

		$SQL->Select('Jobs');
		$SQL->Fields('COUNT(*) AS Total');
		$SQL->Where('TimeStarted=0 AND TimeCompleted=0 AND TimeStartAfter<=:Now');

		$Result = $SQL->Query([ ':Now'=> $Now ]);
		$Row = $Result->Next();

		return $Row->Total;
	}

	public function
	FetchCountFuture():
	int {

		$Now = Common\Date::Unixtime();
		$SQL = $this->DB->NewVerseQuery();

		$SQL->Select('Jobs');
		$SQL->Fields('COUNT(*) AS Total');
		$SQL->Where('TimeStarted=0 AND TimeCompleted=0 AND TimeStartAfter>:Now');

		$Result = $SQL->Query([ ':Now'=> $Now ]);
		$Row = $Result->Next();

		return $Row->Total;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	New(string $Filename, bool $Fresh=FALSE):
	static {

		$Output = new static;
		$Output->DB = DB::Touch($Filename, $Fresh);

		return $Output;
	}

};
