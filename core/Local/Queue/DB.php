<?php ##########################################################################
################################################################################

namespace Local\Queue;

use Nether\Common;
use Nether\Database;

################################################################################
################################################################################

class DB
extends Common\Prototype {

	protected Database\Connection
	$CTX;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public array
	$Tables = [
		'Jobs' => <<< SQL
		CREATE TABLE IF NOT EXISTS Jobs (
			ID INTEGER PRIMARY KEY AUTOINCREMENT,
			UUID TEXT,
			TimeCreated INTEGER,
			TimeStartAfter INTEGER,
			TimeStarted INTEGER,
			TimeCompleted INTEGER,
			JType TEXT,
			JData TEXT
		);
		SQL
	];

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
	Connect():
	static {

		if(!$this->CTX->IsConnected())
		$this->CTX->Connect();

		return $this;
	}

	public function
	Open(bool $Fresh=FALSE):
	static {

		$this->Connect();

		////////

		if($Fresh || !$this->HasTables())
		$this->DefineTables();

		////////

		return $this;
	}

	public function
	NewVerseQuery():
	Database\Verse {

		return $this->CTX->NewVerse();
	}

	public function
	GetDatabaseName():
	string {

		return $this->CTX->Database;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	HasTables():
	bool {

		$SQL = $this->CTX->NewVerse();

		$SQL->Select('sqlite_master');
		$SQL->Fields('COUNT(*) AS TableCount');
		$SQL->Where('type="table"');

		$Result = $SQL->Query();
		$Row = $Result->Next();

		////////

		if(!$Row)
		return FALSE;

		if(!$Row->TableCount)
		return FALSE;

		////////

		return TRUE;
	}

	protected function
	DefineTables():
	static {


		foreach(static::$Tables as $Table => $SQL) {
			$this->CTX->Query(sprintf('DROP TABLE IF EXISTS %s', $Table));
			$this->CTX->Query($SQL);
		}

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	Touch(string $Filename, bool $Fresh=FALSE):
	static {

		$Output = new static([
			'CTX' => new Database\Connection(
				Name:     'Default',
				Type:     'sqlite',
				Hostname: 'localhost',
				Username: '',
				Password: '',
				Database: $Filename
			)
		]);

		$Output->Open($Fresh);

		////////

		$Manager = new Database\Manager;
		$Manager->Add($Output->CTX);

		////////

		return $Output;
	}

};
