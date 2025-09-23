<?php ##########################################################################
################################################################################

namespace Local\Server;

use Nether\Common;

################################################################################
################################################################################

class Message
extends Common\Prototype {

	#[Common\Meta\PropertyListable]
	public string
	$Cmd = 'NOP';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	use
	Common\Package\ToArray,
	Common\Package\ToJSON;

	static public function
	FromJSON(string $JSON):
	static {

		$Output = NULL;
		$Data = json_decode($JSON, TRUE);

		if(is_array($Data))
		if(array_key_exists('Cmd', $Data))
		if(class_exists(sprintf('Local\\Server\\Messages\\%s', $Data['Cmd']), TRUE)) {
			$Class = sprintf('Local\\Server\\Messages\\%s', $Data['Cmd']);
			$Output = new $Class($Data);
		}

		if(!$Output)
		$Output = new static($Data);

		return $Output;
	}

};
