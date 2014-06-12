<?php

namespace legendofmcpe\statscore;

class RequestList{
	/** @var Request[][] */
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
	/**
	 * Call this function when a requestable becomes requestable (again)
	 * @param Requestable $reqable
	 */
	public function onRequestable(Requestable $reqable){
		$this->notifyStrReq($reqable);
	}
	/**
	 * @param Requestable $reqable
	 */
	public function notifyStrReq(Requestable $reqable){
		$requests = $this->getRequests($reqable);
		$this->filterRequests($requests);
		if(count($requests) > 0){
			$reqable->sendMessage("You have ".count($requests)." pending requests.");
			foreach($requests as $id => $request){
				$reqable->sendMessage("#$id: ".$request->getContent());
			}
			$reqable->sendMessage("Use \"/req list [id]\" to view these requests again, use \"/req accept <id>\" to accept and use \"/req reject <id>\" to reject.");
		}
	}
	/**
	 * @param Requestable $reqable
	 * @return Request[]
	 */
	public function getRequests(Requestable $reqable){
		$raid = $reqable->getRequestableIdentifier(); // RequestAble IDentifier
		if(!isset($this->requests[$raid])){
			$this->requests[$raid] = [];
		}
		return $this->requests[$raid];
	}
	// vvv Look at @return /me curses PHPStorm vvv //
	/**
	 * @param $id
	 * @param Requestable $requestable
	 * @return int[]|Request[]|bool
	 */
	public function getRequest($id, Requestable $requestable){
		$reqs = $this->getRequests($requestable);
		$this->filterRequests($reqs);
		if(is_numeric($id) and isset($reqs[$id])){
			return [$id, $reqs[$id]];
		}
		foreach($reqs as $i => $req){
			if(in_array($id, $req->getRequestIDs())){
				return [$i, $req];
			}
		}
		return false;
	}
	/**
	 * @param Request[] &$reqs
	 */
	private function filterRequests(array &$reqs){
		foreach($reqs as $i => $req){
			if($req->getStatus() !== Request::PENDING){
				unset($reqs[$i]);
			}
		}
	}
	public function finalize(){
		$rcnt = 0;
		$cnt = 0;
		foreach($this->requests as $requests){
			$rcnt++;
			/** @var Request $request */
			foreach($requests as $request){
				if($request->getStatus() === Request::PENDING){
					$cnt++;
					$request->setRemoved();
				}
			}
		}
		$this->plugin->getLogger()->info("$cnt requests of $rcnt requestables have been removed due to plugin disabled.");
	}
}
