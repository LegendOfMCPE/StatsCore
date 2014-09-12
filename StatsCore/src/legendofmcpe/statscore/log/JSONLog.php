<?php

namespace legendofmcpe\statscore\log;

class JSONLog extends NestedArrayLog{
	/**
	 * @param string $file
	 * @return array a nested array from a log file
	 */
	protected function readFromFile($file){
		return json_decode(file_get_contents($file));
	}
	/**
	 * @param string $file
	 * @param array $array
	 */
	protected function writeToFile($file, array $array){
		file_put_contents($file, json_encode($array, JSON_BIGINT_AS_STRING | JSON_PRETTY_PRINT));
	}
	protected function getPlayerFile($name){
		return realpath($this->dir.DIRECTORY_SEPARATOR.strtolower($name).".json");
	}
}
