<?php

namespace oTools\session\identifiers;

interface identifier
{
	public function isSet();
	public function get();
	public function touch();
	public function forget();
}
