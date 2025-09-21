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

		$Table = Job::GetTableInfo();
		$SQL = $this->DB->NewVerseQuery();
		$Result = NULL;
		$Row = NULL;

		////////

		$SQL->Select($Table->Name);
		$SQL->Fields('*');
		$SQL->Where('TimeStarted=0 AND TimeCompleted=0');
		$SQL->Sort('TimeCreated');

		$Result = $SQL->Query();
		$Row = $Result->Next();

		if(!$Row)
		throw new Error\QueueEmpty;

		////////

		$Job = new Job($Row);
		$Job->SetDatabase($this->DB);

		return $Job;
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

	public function
	FetchCountPending():
	int {

		$SQL = $this->DB->NewVerseQuery();
		$SQL->Select('Jobs');
		$SQL->Fields('COUNT(*) AS Total');
		$SQL->Where('TimeStarted=0 AND TimeCompleted=0');

		$Result = $SQL->Query();
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
