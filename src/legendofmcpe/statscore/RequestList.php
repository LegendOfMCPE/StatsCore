<?php

namespace legendofmcpe\statscore;

class RequestList{
	private $requests = [];
	public function __construct(StatsCore $core){
		$this->plugin = $core;
	}
	public function add(Request $request){
		$id = $request->getTo()->getRequestableIdentifier();
		if(!isset($this->requests[$id])){
			$this->requests[$id] = [];
		}
		$this->requests[$id][] = $request;
	}
	public function finalize(){
	}
}
