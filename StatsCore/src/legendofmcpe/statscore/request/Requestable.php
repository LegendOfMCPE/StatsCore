<?php

namespace legendofmcpe\statscore\request;

interface Requestable{
	/**
	 * @return bool
	 */
	public function isAvailable();
	/**
	 * @param string $message
	 */
	public function sendMessage($message);
	public function getRequestableIdentifier();
	public function getName();
}
