<?php

namespace oTools\ticket\generators;

interface generator
{
	public function generate() : string;
}