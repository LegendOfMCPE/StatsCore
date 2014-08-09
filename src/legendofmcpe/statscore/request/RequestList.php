<?php

namespace legendofmcpe\statscore\request;

use legendofmcpe\statscore\StatsCore;

class RequestList{
	private static $id = 0;
	private $main;
	/** @var array[] */
	private $requests = [];
	public function __construct(StatsCore $main){
		$this->main = $main;
	}
	public function add(Request $request){
		if(!isset($this->requests[$iden = $request->getTo()->getRequestableIdentifier()])){
			$this->requests[$iden] = [];
		}
		$this->requests[$iden][$id = self::$id++] = $request;
		$request->validate($id);
	}
	/**
	 * @param Requestable $requestable
	 * @return Request[]
	 */
	public function getRequests(Requestable $requestable){
		if(isset($this->requests[$iden = $requestable->getRequestableIdentifier()])){
			return $this->requests[$iden];
		}
		return [];
	}
	public function finalize(){
		foreach($this->requests as $reqs){
			/** @var Request $req */
			foreach($reqs as $req){
				$req->remove();
			}
		}
	}
	/**
	 * @param $id
	 * @param Requestable $requestable
	 * @return Request|null
	 */
	public function getRequest($id, Requestable $requestable){
		$iden = $requestable->getRequestableIdentifier();
		if(isset($this->requests[$iden]) and isset($this->requests[$iden][$id])){
			return $this->requests[$iden][$id];
		}
		return null;
	}
}
