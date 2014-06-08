<?php

namespace legendofmcpe\statscore;

class RequestList{
	public function __construct(StatsCore $core){
		$this->plugin = $core;
	}
	public function finalize(){
	}
}
