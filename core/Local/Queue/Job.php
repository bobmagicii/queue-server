<?php ##########################################################################
################################################################################

namespace Local\Queue;

use Nether\Common;
use Nether\Database;

################################################################################
################################################################################

#[Database\Meta\TableClass('Jobs')]
class Job
extends Database\Prototype {

	protected DB
	$DB;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE, AutoInc: TRUE)]
	#[Database\Meta\PrimaryKey]
	public ?int
	$ID = NULL;

	#[Database\Meta\TypeVarChar(Size: 36)]
	public ?string
	$UUID = NULL;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeCreated = 0;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeStartAfter = 0;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeStarted = 0;

	#[Database\Meta\TypeIntBig(Unsigned: TRUE)]
	public int
	$TimeCompleted = 0;

	#[Database\Meta\TypeVarChar(Size: 36)]
	public ?string
	$JType = NULL;

	#[Database\Meta\TypeText]
	public ?string
	$JData = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Start():
	static {

		$this->SetTimeStart();
		$this->Save();

		return $this;
	}

	public function
	Complete():
	static {

		$this->SetTimeComplete();
		$this->Save();

		return $this;
	}

	public function
	Save():
	static {

		if(!$this->ID)
		$this->InsertDB($this->DB);
		else
		$this->UpdateDB($this->DB);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetType():
	?string {

		return $this->JType;
	}

	public function
	GetData():
	mixed {

		return json_decode($this->JData, TRUE);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	SetDatabase(DB $DB):
	static {

		$this->DB = $DB;

		return $this;
	}

	public function
	SetTimeCreate(?int $When=NULL):
	static {

		if($When === NULL)
		$When = Common\Date::Unixtime();

		$this->TimeCreated = $When;

		return $this;
	}

	public function
	SetTimeStartAfter(?int $When=NULL):
	static {

		if($When === NULL)
		$When = Common\Date::Unixtime();

		$this->TimeStartAfter = $When;

		return $this;
	}

	public function
	SetTimeStart(?int $When=NULL):
	static {

		if($When === NULL)
		$When = Common\Date::Unixtime();

		$this->TimeStarted = $When;

		return $this;
	}

	public function
	SetTimeComplete(?int $When=NULL):
	static {

		if($When === NULL)
		$When = Common\Date::Unixtime();

		$this->TimeCompleted = $When;

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	InsertDB(DB $DB):
	void {

		$Table = static::GetTableInfo();
		$SQL = $DB->NewVerseQuery();
		$Result = NULL;

		$this->UUID = Common\UUID::V7();
		$this->TimeCreated = Common\Date::Unixtime();
		$this->TimeStarted = 0;
		$this->TimeCompleted = 0;

		////////

		$SQL->Insert($Table->Name);

		$SQL->Fields([
			'UUID'           => ':UUID',
			'TimeCreated'    => ':TimeCreated',
			'TimeStartAfter' => ':TimeStartAfter',
			'TimeStarted'    => ':TimeStarted',
			'TimeCompleted'  => ':TimeCompleted',
			'JType'          => ':JType',
			'JData'          => ':JData'
		]);

		$Result = $SQL->Query([
			':UUID'           => $this->UUID,
			':TimeCreated'    => $this->TimeCreated,
			':TimeStartAfter' => $this->TimeStartAfter,
			':TimeStarted'    => $this->TimeStarted,
			':TimeCompleted'  => $this->TimeCompleted,
			':JType'          => $this->JType,
			':JData'          => $this->JData
		]);

		////////

		$this->ID = (int)$Result->GetInsertID();

		return;
	}

	protected function
	UpdateDB(DB $DB):
	void {

		$Table = static::GetTableInfo();
		$SQL = $DB->NewVerseQuery();
		$Result = NULL;

		////////

		$SQL->Update($Table->Name);
		$SQL->Where('ID=:ID');

		$SQL->Fields([
			'TimeCreated'   => ':TimeCreated',
			'TimeStarted'   => ':TimeStarted',
			'TimeCompleted' => ':TimeCompleted',
			'JType'         => ':JType',
			'JData'         => ':JData'
		]);

		$Result = $SQL->Query([
			':ID'            => $this->ID,
			':TimeCreated'   => $this->TimeCreated,
			':TimeStarted'   => $this->TimeStarted,
			':TimeCompleted' => $this->TimeCompleted,
			':JType'         => $this->JType,
			':JData'         => $this->JData
		]);

		////////

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static protected function
	FindExtendOptions(Common\Datastore $Input):
	void {

		$Input->Define('Next', NULL);
		$Input->Define('Future', NULL);

		return;
	}

	static protected function
	FindExtendFilters(Database\Verse $SQL, Common\Datastore $Input):
	void {

		if($Input['Next'] !== NULL) {
			$SQL->Where('TimeStarted=0 AND TimeStartAfter <= :Next');
			$SQL->Sort('TimeCreated', $SQL::SortAsc);
			$SQL->Limit(1);
		}

		if($Input['Future'] !== NULL) {
			$SQL->Where('TimeStarted=0 AND TimeStartAfter > :Future');
			$SQL->Sort('TimeCreated', $SQL::SortAsc);
			$SQL->Limit(1);
		}

		return;
	}

	static protected function
	FindExtendSorts(Database\Verse $SQL, Common\Datastore $Input):
	void {

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	FromArray(iterable $Input):
	static {

		$Output = new static($Input);

		return $Output;
	}

};
