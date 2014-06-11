<?php

namespace legendofmcpe\statscore;

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
}
