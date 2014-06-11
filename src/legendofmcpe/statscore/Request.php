<?php

namespace legendofmcpe\statscore;

abstract class Request{
	protected $to;
	public function __construct(Requestable $to){
		$this->to = $to;
	}
	/**
	 * @return string
	 */
	public abstract function getContent();
	public abstract function onAccepted();
	public function getTo(){
		return $this->to;
	}
}
