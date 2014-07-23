<?php

namespace legendofmcpe\statscore;

class Table{
	const ALIGN_LEFT = 0;
	const ALIGN_RIGHT = 1;
	const ALIGN_CENTER = 2;
	const ALIGN_NONE = 3;
	const VERSION_INITIAL = 1;
	const CURRENT_VERSION = 1;
	protected $file;
	protected $align;
	protected $version = self::VERSION_INITIAL;
	protected $buffer = "";
	protected $pointer = 0;
	/** @var array[] */
	protected $table = [];
	protected $maxLens = [];
	public function __construct($file, $align = false){
		$this->file = $file;
		$this->align = $align;
		if(file_exists($this->file)){
			$this->reload();
		}
		else{
			$this->align = ($align === false ? self::ALIGN_CENTER:$align);
		}
	}
	public function reload(){
		$res = fopen($this->file, "rb");
		$meta = explode(";", substr(strstr($this->readLine(), ": "), 2));
		$this->version = (int) $meta[0];
		$this->align = (int) $meta[1];
		$this->buffer = stream_get_contents($res);
		while(is_string($line = $this->readLine())){
			if(substr($line, 0, 1) === "#"){
				continue;
			}
			if(strpos($line, "|") === false){
				continue;
			}
			$items = explode("|", $line);
			$items = array_map(array($this, "readItem"), $items);
			$x = array_shift($items); // I was too used to array_shift($args) :P
			$this->table[$x] = $items;
		}
		fclose($res);
	}
	public function save(){
		$res = fopen($this->file, "wt");
		fwrite($res, "METADATA: ".$this->version.";".$this->align."\n");
		fwrite($res, "# Table generated by LegendOfMCPE.StatsCore.Table at ".date(DATE_ATOM)."\n");
		$this->updateMaxLengths();
		foreach($this->table as $x => $orow){
			$row = $orow;
			array_unshift($row, $x);
			$boxes = [];
			foreach($row as $y => $item){
				$text = $this->writeItem($item);
				$len = strlen($text);
				$diff = $this->maxLens[$y] - $len;
				switch($this->align){
					case self::ALIGN_CENTER:
						$out = str_repeat(" ", (int) floor($diff / 2));
						$out .= $text;
						$out .= str_repeat(" ", (int) ceil($diff / 2));
						break;
					case self::ALIGN_LEFT:
						$out = $text;
						$out .= str_repeat(" ", $diff);
						break;
					case self::ALIGN_RIGHT:
						$out = str_repeat(" ", $diff);
						$out .= $text;
						break;
					default:
						$out = $text;
						break;
				}
				$boxes[] = $out;
			}
			fwrite($res, implode(" | ", $boxes)."\n");
		}
		fclose($res);
	}
	/**
	 * @param string $item
	 * @return mixed
	 */
	public function readItem($item){
		return json_decode($item);
	}
	/**
	 * @param mixed $item
	 * @return string
	 */
	public function writeItem($item){
		return json_encode($item); // still lazy :P
	}
	/**
	 * @param string|int $x
	 * @param int $y
	 * @param bool $default
	 * @return bool
	 */
	public function get($x, $y, $default = false){
		return (isset($this->table[$x][$y]) ? $this->table[$x][$y]:$default);
	}
	public function getRow($x){
		return isset($this->table[$x]) ? $this->table[$x]:null;
	}
	public function getColumn($y){
		$output = [];
		foreach($this->table as $key => $values){
			if(isset($values[$y])){
				$output[$key] = $values[$y];
			}
		}
		return $output;
	}
	/**
	 * @param string|int $x
	 * @param int $y
	 * @param mixed $value
	 */
	public function set($x, $y, $value){
		$this->table[$x][$y] = $value;
	}
	protected function updateMaxLengths(){
		foreach($this->table as $row){
			foreach($row as $y => $item){
				$text = $this->writeItem($item);
				if(isset($this->maxLens[$y])){
					$this->maxLens[$y] = max($this->maxLens[$y], strlen($text));
				}
				else{
					$this->maxLens[$y] = strlen($text);
				}
			}
		}
		$maxX = 0;
		foreach($this->table as $x => $row){
			$maxX = max(strlen($this->writeItem($x)), $maxX);
		}
		array_unshift($this->maxLens, $maxX);
	}
	public function getAll(){
		return $this->table;
	}
	protected function readLine(){
		if($this->feof()){
			return false;
		}
		$output = "";
		while(($char = $this->read()) !== "\n" and $char !== false){
			$output .= $char;
		}
		return trim($output);
	}
	protected function read(){
		if($this->feof()){
			return false;
		}
		return substr($this->buffer, $this->pointer++, 1);
	}
	protected function feof(){
		return $this->pointer >= strlen($this->buffer);
	}
}
