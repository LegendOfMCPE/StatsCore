<?php

namespace legendofmcpe\statscore;

abstract class ClassLoader{
	public static function load(){
		foreach(
			[
				"legendofmcpe\\statscore\\Table"
			] as $class){
			class_exists($class, true);
		}
	}
}
