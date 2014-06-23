<?php

namespace legendofmcpe\statscore;

class Table implements \ArrayAccess{
	// Note: In this class, X is counted vertically downwards and Y is counted horizontally rightwards to ease understanding in code while optimizing the visual appearance of the config file output.
	/** @var mixed[][] access with $table[$x][$y] */
	protected $table;
	public function __construct($file, $suppressNoticeWrite = false, $suppressNoticeRead = false, $readEmptyDefault = null){
		$this->file = $file;
	}
	public function save(){
		$out = "# Table generated on ".date("M j, Y")." at ".date("h:m:s")." by legendofmcpe\\statscore\\Table".PHP_EOL;
		foreach($this->table as $x => $row){
			$out .= "$x | ";
			foreach($row as $item){
				$out .= $this->writeValue($item);
				$out .= " | "; // TODO align the columns
			}
			$out = substr($out, 0, -2);
			$out .= PHP_EOL;
		}
		file_put_contents($this->file, $out, LOCK_EX);
	}
	public function reload(){
		$this->table = [];
		$data = file_get_contents($this->file);
		foreach(explode(PHP_EOL, $data) as $line){
			if($line === "" or substr($line, 0, 1) === "#") continue;
			$items = explode("|", $line);
			$key = trim(array_shift($items));
			$row = [];
			foreach($items as $item){
				$row[] = $this->readValue($item);
			}
			$this->table[$key] = $row;
		}
	}
	public function getAll(){
		return $this->table;
	}
	/**
	 * @param string|int $x
	 * @param int $y
	 * @param mixed $defaultValue
	 */
	public function get($x, $y, $defaultValue = false){
		return isset($this->table[$x][$y]) ? $this->table[$x][$y]:$defaultValue;
	}
	/**
	 * @param string|int $x
	 * @param int $y
	 * @param mixed $value
	 * @param mixed $autoFill
	 */
	public function set($x, $y, $value, $autoFill = null){
		if(!isset($this->table[$x])){
			$this->table[$x] = [];
		}
		for($i = 0; $i < $y; $i++){
			if(!isset($this->table[$x][$i])){
				$this->table[$x][$i] = $autoFill;
			}
		}
		$this->table[$x][$y] = $value;
	}
	public function getRow($x, $defaultValue = []){
		return isset($this->x) ? $this->x:$defaultValue;
	}
	public function setRow($x, array $value){
		$this->table[$x] = $value;
	}
	public function getColumn($y, $defaultValue = null){
		$out = [];
		foreach(array_keys($this->table) as $x){
			$out[$x] = $this->get($x, $y, $defaultValue);
		}
		return $out;
	}
	public function setColumn($y, $value, $autoFill = null){
		foreach(array_keys($this->table) as $x){
			$this->set($x, $y, is_array($value) ? $value[$x]:$value, $autoFill);
		}
	}
	public function getKeyedColumn($keyColumn, $valueColumn){
		$data = [];
		for($i = 0; isset($this->table[$i][$keyColumn]) and isset($this->table[$i][$valueColumn]); $i++){
			$data[$this->table[$i][$keyColumn]] = $this->table[$i][$valueColumn];
		}
		return $data;
	}
	protected function readValue($v){
		if(is_numeric($v)){
			return $v + 0; // cast to number
		}
		switch(strtolower($v)){
			case "null":
				return null;
			case "false":
				return false;
			case "true":
				return true;
			default:
				return trim($v, "'\"");
		}
	}
	protected function writeValue($v){
		if(is_bool($v)){
			return ($v ? "true":"false");
		}
		if(is_null($v)){
			return "null";
		}
		if(is_string($v)){
			if(is_numeric($v) or strtolower($v) === "false" or strtolower($v) === "true" or strtolower($v) === "null"){
				return "'$v'";
			}
		}
		return "$v";
	}
	public function offsetExists($k){
		return isset($this->table[$k]);
	}
	public function offsetGet($k){
		return $this->table[$k];
	}
	public function offsetSet($k, $v){
		$this->table[$k] = (array) $v;
	}
	public function offsetUnset($k){
		unset($this->table[$k]);
	}
}
