<?php

include("../php/util.php");
include("../php/json.php");

	$toping = Array();
	
	for ($argc = 1; $argc < count($_SERVER[ "argv" ]); $argc++)
	{
		array_push($toping,Bin_IPZero(IP_Bin($_SERVER[ "argv" ][ $argc ])));
	}

	/*
	array_push($toping,"088.134.096.000");
	array_push($toping,"088.134.097.000");
	array_push($toping,"088.134.098.000");
	array_push($toping,"088.134.099.000");
	*/

function Ping_Block($from,$toto)
{
	$total = $toto - $from;
	$alive = 0;
	
	for ($host = $from; $host <= $toto; $host++)
	{
		$time = Ping($host);
		
		if ($time >= 0) $alive++;
		
		echo Bin_IPZero($host) . " => " . $time . "\n";
	}
	
	return floor(($alive * 100) / $total);
}

	
	while (true)
	{
		foreach ($toping as $dummy => $ping)
		{
			Ping_Block(IP_Bin($ping),IP_Bin($ping) + 255);
		}
		
		break;
	}
?>