<?php

namespace oTools\Sessions\Identifiers;

interface IdentifierInterface
{
	public function exists();
	public function isSet();
	public function get();
	public function touch();
	public function forget();
}
