<?php

namespace legendofmcpe\statscore;

/**
 * Class Table
 * Note: In this class, X is counted vertically downwards and Y is counted horizontally rightwards to ease understanding in code while optimizing the visual appearance of the config file output.
 * @package legendofmcpe\statscore
 */
class Table implements \ArrayAccess{
	const ALIGN_LEFT = 0b00;
	const ALIGN_MIDDLE = 0b01;
	const ALIGN_RIGHT = 0b10;
	const ALIGN_TRIM = 0b11;
	public static $DEFAULT_ALIGN = self::ALIGN_MIDDLE;
	/** @var mixed[][] access with $table[$x][$y] */
	protected $table;
	protected $alignType;
	protected $tmpMaxLengths = [];
	public function __construct($file, $alignType = false){
		$this->file = $file;
		$this->reload();
		if(is_int($alignType) or !isset($this->alignType)){
			$this->alignType = $alignType;
		}
		elseif(!isset($this->ailgnType)){
			$this->alignType = self::ALIGN_TRIM;
		}
		if(!isset($this->alignType)){
			$this->alignType = ($alignType === false ? self::$DEFAULT_ALIGN:$alignType);
		}
	}
	public function save(){
		$meta = $this->getMetadata();
		$out = "# Table generated on ".date("M j, Y")." at ".date("h:m:s")." by legendofmcpe\\statscore\\Table".PHP_EOL;
		$out .= "# Please do NOT edit the following value:".PHP_EOL; // while I'm going to do that when I debug :D
		$out .= "# LEGENDOFMCPE\\STATSCORE\\TABLE::METADATA: $meta".PHP_EOL;
		$this->updateTmpMaxLen();
		foreach($this->table as $x => $row){
			$out .= "$x | ";
			foreach($row as $y => $item){
				$str = $this->writeValue($item);
				$multiplier = strlen($str) - $this->tmpMaxLengths[$y];
				$out .= $this->alignItem($str, $multiplier);
				$out .= " | ";
			}
			$out = substr($out, 0, -3);
			$out .= PHP_EOL;
		}
		file_put_contents($this->file, $out, LOCK_EX);
	}
	public function getTmpMaxLen(){
		foreach($this->table as $row){
			foreach($row as $y => $item){
				$str = $this->writeValue($item);
				if(!isset($this->tmpMaxLengths[$y])){
					$this->tmpMaxLengths[$y] = 0;
				}
				$this->tmpMaxLengths[$y] = max($this->tmpMaxLengths[$y], strlen($str));
			}
		}
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
		preg_match_all("/# LEGENDOFMCPE\\STATSCORE\\TABLE::METADATA: ([0-9]{1,})".PHP_EOL."/", $data, $matches);
		if(isset($matches[1][0])){
			$this->alignType = ((int) $matches[1][0]) & 0b11;;
		}
		else{
			trigger_error("The metadata in StatsCore Table file {$this->file} cannot be found. The default value will be used.", E_USER_WARNING);
			$this->alignType = self::$DEFAULT_ALIGN;
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
	protected function getMetadata(){
		$out = 0b00000000;
		$out |= $this->alignType;
		return $out;
	}
	public function alignItem($str, $multiplier){
		switch($this->alignType & 0b11){
			case self::ALIGN_LEFT:
				return $str.str_repeat(" ", $multiplier);
			case self::ALIGN_RIGHT:
				return str_repeat(" ", $multiplier).$str;
			case self::ALIGN_MIDDLE:
				$half = $multiplier / 2;
				return str_repeat(" ", (int) floor($half)).$str.str_repeat(" ", (int) ceil($half)); // I almost forgot such functions floor() and ceil() existed xD
			default:
				return $str;
		}
	}
}
