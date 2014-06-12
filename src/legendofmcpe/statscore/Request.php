<?php

namespace legendofmcpe\statscore;

abstract class Request{
	/**
	 * Waiting for the requestable to decide what to do with this request
	 */
	const PENDING = 0;
	/**
	 * The requestable accepted this request
	 */
	const ACCEPTED = 1;
	/**
	 * The requestable rejected this request
	 */
	const REJECTED = 2;
	/**
	 * The requester, the server owner (via commands) or the plugin removed this request
	 */
	const REMOVED = 3;
	/**
	 * @var Requestable
	 */
	protected $to;
	/**
	 * @var int A value between Request::PENDING, Request::ACCEPTED and Request::REJECTED
	 */
	private $status = self::PENDING;
	public function __construct(Requestable $to){
		$this->to = $to;
	}
	/**
	 * @return string
	 */
	public abstract function getContent();
	public final function accept(){
		$this->status = self::ACCEPTED;
		$this->onAccepted();
	}
	protected abstract function onAccepted();
	public final function reject(){
		$this->status = self::REJECTED;
		$this->onRejected();
	}
	protected abstract function onRejected();
	/**
	 * @return Requestable
	 */
	public function getTo(){
		return $this->to;
	}
	/**
	 * @return string[]
	 */
	public function getRequestIDs(){
		$results = $this->getInternalRequestIDs();
		$output = [];
		foreach($results as $str){
			if(!is_string($str) or is_numeric($str) and strpos($str, ".") === false){
				trigger_error("Illegal return type from ".get_class($this)."::getInternalRequestIDs(): $str", E_USER_WARNING);
				continue;
			}
			$output[] = $str;
		}
		return $output;
	}
	/**
	 * @return int
	 */
	public function getStatus(){
		return $this->status;
	}
	public function setRemoved(){
		$this->status = self::REMOVED;
	}
	/**
	 * @return string[] this method MUST be non-numeric
	 */
	protected function getInternalRequestIDs(){
		return [];
	}
}
