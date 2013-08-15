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

	exit();
		
	$json = json_decdat(file_get_contents("../var/de/kd/mapdata/eplinks.json"));
	
	foreach ($json as $host => $dummy)
	{
		$time = -1;
		
		if ($time == -1) $time = Ping(Bin_IP(IP_Bin($host)), 500);
		if ($time == -1) $time = Ping(Bin_IP(IP_Bin($host)),1000);
		if ($time == -1) $time = Ping(Bin_IP(IP_Bin($host)),2000);
		
		$time = ($time == -1) ? "nix" : "dox";
		echo $host . " => " . $time . "\n";
	}
?>