<?php

namespace oTools\session\identifiers;

interface identifier
{
	public function exists();
	public function isSet();
	public function get();
	public function touch();
	public function forget();
}
