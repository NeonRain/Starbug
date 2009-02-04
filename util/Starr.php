<?php
/**
* string to array converter utility
*
* This file is part of StarbugPHP
*
* StarbugPHP - web service development kit
* Copyright (C) 2008-2009 Ali Gangji
*
* StarbugPHP is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* StarbugPHP is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with StarbugPHP.  If not, see <http://www.gnu.org/licenses/>.
*/
class Starr {

	function star($str="") {
		$arr = array();
		$keypairs = split(",", $str);
		foreach($keypairs as $keypair) {
			if (ereg("=", $keypair)) {
				$keypair = split("=", $keypair);
				$arr[$keypair[0]] = $keypair[1];
			} else if ($keypair!="") $arr[] = $keypair;
		}
		return $arr;
	}

	function closer_pos($str, $offset=0) {
		$close = strpos($str, "\t");
		$nextopen = strpos($str, "\n");
		if (($nextopen === false) || ($nextopen > $close)) return $offset+$close; else return $offset+Starr::closer_pos(substr($str, $close+1), $close+1);
	}

	function rstar($str="") {
		$arr = array(); $open = strpos($str, "\n"); $next = $open-1;
		if ($open===false) return Starr::star($str);//no nesting, return flat array
		else $close = Starr::closer_pos(substr($str, $open+1), $open+1);//find the close of the first open
		if ($open != 0) {//something is before the first open
			while (($next > 0) && ($str{$next} != ",")) $next--;
			if ($next != 0) $arr = Starr::star(substr($str, 0, $next));
		}
		if ($next != 0) $next++;
		$arr = (($str{$open-1} == "=") ? array_merge($arr, array(substr($str, $next, ($open-1)-$next) => Starr::rstar(substr($str, $open+1, $close-($open+1))))) : array_merge($arr, Starr::rstar(substr($str, $open+1, $close-($open+1)))));
		if ((strlen($str) > ($close+1)) && ($str{$close+1} == ",")) $arr = array_merge($arr, Starr::rstar(substr($str, $close+2)));
		return $arr;
	}

}
?>