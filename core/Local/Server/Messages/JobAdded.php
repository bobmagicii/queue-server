<?php ##########################################################################
################################################################################

namespace Local\Server\Messages;

use Local\Queue;
use Local\Server;
use Nether\Common;

################################################################################
################################################################################

class JobAdded
extends Server\Message {

	#[Common\Meta\PropertyListable]
	public string
	$Cmd = 'JobAdded';

	#[Common\Meta\PropertyListable]
	public string
	$JobUUID;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	FromUUID(string $UUID):
	static {

		$Output = new static([
			'JobUUID' => $UUID
		]);

		return $Output;
	}

	static public function
	FromJob(Queue\Job $Job):
	static {

		return static::FromUUID($Job->UUID);
	}

};
