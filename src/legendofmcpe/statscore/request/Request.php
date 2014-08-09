<?php

namespace legendofmcpe\statscore\request;

abstract class Request implements IRequest{
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
	/** @var int */
	protected $id;
	/**
	 * @var int A value between Request::PENDING, Request::ACCEPTED and Request::REJECTED
	 */
	private $status = self::PENDING;
	public function __construct(Requestable $to){
		$this->to = $to;
	}
	public function validate($id){
		$this->id = $id;
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
	public final function remove(){
		$this->status = self::REMOVED;
	}
	/**
	 * @return int
	 */
	public function getID(){
		return $this->id;
	}
	protected abstract function onRemoved();
	/**
	 * @return Requestable
	 */
	public function getTo(){
		return $this->to;
	}
	/**
	 * @return int
	 */
	public function getStatus(){
		return $this->status;
	}
	/**
	 * @return string
	 */
	public function getStrStatus(){
		switch($this->status){
			case self::PENDING:
				return "pending";
			case self::ACCEPTED:
				return "accepted";
			case self::REJECTED:
				return "rejected";
		}
		return "removed";
	}
}
