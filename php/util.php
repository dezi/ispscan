<?php

$GLOBALS[ "knownisp" ] = array();
$GLOBALS[ "knownisp" ][ "Kabel Deutschland Vertrieb und Service GmbH" ] = "de/kd";
$GLOBALS[ "knownisp" ][ "Deutsche Telekom AG" 						  ] = "de/tk";
$GLOBALS[ "knownisp" ][ "Telekom Deutschland GmbH" 					  ] = "de/tk";
$GLOBALS[ "knownisp" ][ "Telefonica Germany GmbH & Co.OHG" 			  ] = "de/tf";

function ResolveISP($ip)
{
	if (isset($GLOBALS[ "ispcache" ]) &&
		isset($GLOBALS[ "ispcache" ][ $ip ]))
	{
		return $GLOBALS[ "ispcache" ][ $ip ];
	}
		
	if (! isset($GLOBALS[ "isplist" ]))
	{
		$lines = file("../lib/Nirsoft.ISP.de.csv");
		
		$isplist = array();

		foreach ($lines as $line)
		{
			$parts = explode(",",trim($line));
			if (count($parts) != 5) continue;
			
			$entry = array();
			
			$entry[ "from" ] = IP_Bin($parts[ 0 ]);
			$entry[ "upto" ] = IP_Bin($parts[ 1 ]);
			$entry[ "name" ] = $parts[ 4 ];
			
			array_push($isplist,$entry);
		}
		
		$GLOBALS[ "isplist" ] = $isplist;
	}

	$ipbin = IP_Bin($ip);
	$isplist = $GLOBALS[ "isplist" ];
	
	foreach ($isplist as $entry)
	{
		if ($ipbin < $entry[ "from" ]) continue;
		if ($ipbin > $entry[ "upto" ]) continue;

		if (! isset($GLOBALS[ "knownisp" ][ $entry[ "name" ] ])) break;
		
		$isp = $GLOBALS[ "knownisp" ][ $entry[ "name" ] ];
		
		if (! isset($GLOBALS[ "ispcache" ]))
		{
			$GLOBALS[ "ispcache" ] = array();
		}
		
		$GLOBALS[ "ispcache" ][ $ip ] = $isp;
		
		return $isp;
	}
	
	return "xx/xx";
}

function KappaRound($val)
{
	return floor($val * 1000) / 1000.0;
}

function Fix_Region($region)
{
	if ($region == "02") $region = "Bayern";
	if ($region == "03") $region = "Bremen";
	if ($region == "04") $region = "Hamburg";
	if ($region == "06") $region = "Niedersachsen";
	if ($region == "10") $region = "Schleswig-Holstein";
	if ($region == "11") $region = "Brandenburg";
	if ($region == "12") $region = "Mecklenburg-Vorpommern";
	if ($region == "16") $region = "Berlin";
	
	return $region;
}

function Fix_City($city)
{
	$city = str_replace("-a","-A",$city);
	$city = str_replace("-b","-B",$city);
	$city = str_replace("-c","-C",$city);
	$city = str_replace("-d","-D",$city);
	$city = str_replace("-e","-E",$city);
	$city = str_replace("-f","-F",$city);
	$city = str_replace("-g","-G",$city);
	$city = str_replace("-h","-H",$city);
	$city = str_replace("-i","-I",$city);
	$city = str_replace("-j","-J",$city);
	$city = str_replace("-k","-K",$city);
	$city = str_replace("-l","-L",$city);
	$city = str_replace("-m","-M",$city);
	$city = str_replace("-n","-N",$city);
	$city = str_replace("-o","-O",$city);
	$city = str_replace("-p","-P",$city);
	$city = str_replace("-q","-Q",$city);
	$city = str_replace("-r","-R",$city);
	$city = str_replace("-s","-S",$city);
	$city = str_replace("-t","-T",$city);
	$city = str_replace("-u","-U",$city);
	$city = str_replace("-v","-V",$city);
	$city = str_replace("-w","-W",$city);
	$city = str_replace("-x","-X",$city);
	$city = str_replace("-y","-Y",$city);
	$city = str_replace("-z","-Z",$city);
	$city = str_replace("-ä","-Ä",$city);
	$city = str_replace("-ö","-Ö",$city);
	$city = str_replace("-ü","-Ü",$city);

	return $city;
}

function GetDifferentCities($subnets)
{
	$difflocs = array();
	
	foreach ($subnets as $ip => $loc)
	{
		if ((substr($loc,0,3) == "ep:") || 
			(substr($loc,0,3) == "gw:") || 
			(substr($loc,0,3) == "bb:")) 
		{
			$loc = substr($loc,3);
		}
		
		$parts = explode(",",$loc);
		array_pop($parts);
		array_pop($parts);
		$loc = implode(",",$parts);
		
		$difflocs[ $loc ] = true;
	}
	
	$difflist = array();
	
	foreach ($difflocs as $loc => $dummy)
	{
		array_push($difflist,$loc);
	}
	
	return $difflist;
}

function GetDifferentLocations($subnets)
{
	$difflocs = array();
	
	foreach ($subnets as $ip => $loc)
	{
		if ((substr($loc,0,3) == "ep:") || 
			(substr($loc,0,3) == "gw:") || 
			(substr($loc,0,3) == "bb:")) 
		{
			$loc = substr($loc,3);
		}
		
		if ($loc == "n.n.") continue;
		
		$difflocs[ $loc ] = true;
	}
	
	$difflist = array();
	
	foreach ($difflocs as $loc => $dummy)
	{
		array_push($difflist,$loc);
	}
	
	return $difflist;
}

function IP_Bin($ip)
{
	$parts = explode(".",$ip);
	if (count($parts) != 4) return 0;
	
	$bin = (intval($parts[ 0 ]) << 24)
		 + (intval($parts[ 1 ]) << 16)
		 + (intval($parts[ 2 ]) <<  8)
		 + (intval($parts[ 3 ]) <<  0)
		 ;
		 
	return $bin;
}

function Bin_IP($bin)
{
	$ip = (($bin >> 24) & 0xff)
		. "."
		. (($bin >> 16) & 0xff)
		. "."
		. (($bin >>  8) & 0xff)
		. "."
		. (($bin >>  0) & 0xff)
		; 

	return $ip;
}

function IPZero($ip)
{
	$bin = strpos($ip,".") ? IP_Bin($ip) : $ip;
	
	$ip = str_pad((($bin >> 24) & 0xff),3,"0",STR_PAD_LEFT)
		. "."
		. str_pad((($bin >> 16) & 0xff),3,"0",STR_PAD_LEFT)
		. "."
		. str_pad((($bin >>  8) & 0xff),3,"0",STR_PAD_LEFT)
		. "."
		. str_pad((($bin >>  0) & 0xff),3,"0",STR_PAD_LEFT)
		; 

	return $ip;
}

function IP($ip)
{
	return Bin_IP(IP_Bin($ip));
}

function Bin_IPZero($bin)
{
	$ip = str_pad((($bin >> 24) & 0xff),3,"0",STR_PAD_LEFT)
		. "."
		. str_pad((($bin >> 16) & 0xff),3,"0",STR_PAD_LEFT)
		. "."
		. str_pad((($bin >>  8) & 0xff),3,"0",STR_PAD_LEFT)
		. "."
		. str_pad((($bin >>  0) & 0xff),3,"0",STR_PAD_LEFT)
		; 

	return $ip;
}

function To_Int($str)
{
	if (substr($str,0,1) == "\"") $str = substr($str,1);
	if (substr($str,-1)  == "\"") $str = substr($str,0,-1);
	
	return intval($str);
}

function CheckOnline($host)
{
	if (Ping($host,100) > 0) return true;
	if (Ping($host,250) > 0) return true;
	if (Ping($host,500) > 0) return true;
	
	echo "Offline";
	
	while (true)
	{
		if (Ping($host,250) > 0) break;
		
		echo ".";			
		sleep(10);
	}
	
	echo "\n";

	return false;
}

function WriteEvent($event,$type,$ip)
{
	date_default_timezone_set("UTC");

	$newevent = array();

	$newevent[ "stamp" ] = date("Ymd.His");
	$newevent[ "event" ] = $event;
	$newevent[ "type"  ] = $type;
	$newevent[ "ip"    ] = $ip;
	
	//
	// Write to recent events files.
	//
	
	$eventsfile = "../www/kd.events";

	$events = array();
	
	if (file_exists($eventsfile . ".json"))
	{
		$json = file_get_contents($eventsfile . ".json");
		$events = json_decdat($json);
	}
	
	array_unshift($events,$newevent);
	
	$maxstamp = date("Ymd.His",time() - (48 * 3600));
	
	while (count($events) > 0)
	{
		if ($events[ count($events) - 1 ][ "stamp" ] >= $maxstamp) break;
		
		array_pop($events);
	}
	
	$json = json_encdat($events);
	file_put_contents($eventsfile . ".json",$json);
	chmod($eventsfile . ".json",0666);
	
	$json = "kappa.EventsCallback(\n" . $json . ");\n";
	file_put_contents($eventsfile . ".js",$json);
	chmod($eventsfile . ".js",0666);
	
	//
	// Write to archive events files.
	//
	
	$eventsfile = "../var/kd/events/kd." . date("Ymd") . ".events";
	
	$events = array();
	
	if (file_exists($eventsfile . ".json"))
	{
		$json = file_get_contents($eventsfile . ".json");
		$events = json_decdat($json);
	}
	
	array_unshift($events,$newevent);
	
	$json = json_encdat($events);
	file_put_contents($eventsfile . ".json",$json);
	chmod($eventsfile . ".json",0666);
}

function Ping($host,$timeout = 100,$quiet = false) 
{	
	$time = -1;

	//
    // Create the socket, the last '1' denotes ICMP
    //
	
	$socket = @socket_create(AF_INET,SOCK_RAW,1);
	
    if ($socket === false)
    {
    	echo "Use SUUUUUUUUDOOOOOOOO Spacko...\n";
    	exit(0);
    }

	$sec  = floor($timeout / 1000);
	$usec = ($timeout % 1000) * 1000;
	
	socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,array("sec" => $sec, "usec" => $usec));
	
	if (@socket_connect($socket,$host,null) === false)
	{
		if (! $quiet) echo "Cannot resolve '$host'.\n";
	}
	else
	{
		list($start_usec,$start_sec) = explode(" ",microtime());
		$start_time = ((float) $start_usec + (float) $start_sec);

		$package = "\x08\x00\x19\x2f\x00\x00\x00\x00\x70\x69\x6e\x67";
		socket_send($socket,$package,strlen($package),0);

		if (@socket_read($socket,255)) 
		{
			list($end_usec,$end_sec) = explode(" ",microtime());
			$end_time = ((float) $end_usec + (float) $end_sec);

			$total_time = $end_time - $start_time;

			$time = floor($total_time * 1000);
			if ($time <= 1) $time = -1;
		} 
	}
	
	socket_close($socket);
   
	return $time;
}

function GetAddrByHost($host,$timeout = 2) 
{	
	$query = `nslookup -timeout=$timeout -retry=1 $host`;
   
	if (preg_match('/\nAddress: (.*)\n/',$query,$matches))
	{
		$res = trim($matches[ 1 ]);
		
		echo "GetAddrByHost: $host => $res\n";

		return $res;
	}
	
	echo "GetAddrByHost: $host => nix\n";

	return false;
}

function PingHost($host,$timeout = 100,$quiet = false)
{
	//echo "PING $host\n";

	$time = -1;
	
	for ($inx = 0; $inx < 2; $inx++)
	{
		if (isset($GLOBALS[ "hostip" ]))
		{
			if (! isset($GLOBALS[ "hostip" ][ $host ]))
			{
				$GLOBALS[ "hostip" ][ $host ] = GetAddrByHost($host);
			}
		
			$hostip = $GLOBALS[ "hostip" ][ $host ];
		}
		else
		{
			$hostip = GetAddrByHost($host);
		}

		if ($hostip !== false)
		{		
			$time = Ping($hostip,$timeout,$quiet);
			
			if ($time != -1) break;
		}
		
		if (! isset($GLOBALS[ "hostip" ])) break;
		
		unset($GLOBALS[ "hostip" ][ $host ]);
	}
	
	return $time;	
}

function PingHTTP($host,$timeout = 1000,$quiet = false) 
{ 
	$time = -1;
	
	//echo "HTTP $host\n";
	
	for ($inx = 0; $inx < 2; $inx++)
	{
		if (isset($GLOBALS[ "hostip" ]))
		{
			if (! isset($GLOBALS[ "hostip" ][ $host ]))
			{
				$GLOBALS[ "hostip" ][ $host ] = GetAddrByHost($host);
			}
		
			$hostip = $GLOBALS[ "hostip" ][ $host ];
		}
		else
		{
			$hostip = GetAddrByHost($host);
		}

		if ($hostip !== false)
		{
			list($start_usec,$start_sec) = explode(" ",microtime());
			$start_time = ((float) $start_usec + (float) $start_sec);
  
			$socket = @fsockopen($hostip,80,$errno,$errstr,1); //$timeout / 1000); 
	
			if (! $socket)
			{
				if (isset($GLOBALS[ "hostip" ]) && isset($GLOBALS[ "hostip" ][ $host ]))
				{
					unset($GLOBALS[ "hostip" ][ $host ]);
				}
			
				continue;
			}
	
			fclose($socket);
	
			list($end_usec,$end_sec) = explode(" ",microtime());
			$end_time = ((float) $end_usec + (float) $end_sec);

			$total_time = $end_time - $start_time;

			$time = floor($total_time * 1000);
			if ($time <= 1) $time = -1;
		
			if ($time != -1) break;
		}
		
		if (! isset($GLOBALS[ "hostip" ])) break;
		
		unset($GLOBALS[ "hostip" ][ $host ]);
	}
	
	return $time;
}

function SCPToRemote($file)
{
	if (($host = gethostname()) != "DeziPi")
	{
		if ($host == "Debian-60-squeeze-64-LAMP")
		{
			return;
		}
		
		echo "Wrong SCP host: $host\n";
		exit();
	}
	
	if (substr($file,0,3) != "../")
	{
		echo "Wrong SCP path: $file\n";
		exit();
	}
	
	$cmd = "sudo -u pi scp $file icy@xberry.org:ispscan/" . substr($file,3);
	
	//echo "$cmd\n";
	
	system($cmd);
}

function Is_Netping_Lowping($ip)
{
	$netip = ((IP_bin($ip) >> 8) << 8);
	$file  = "../var/kd/netping/" . Bin_IPZero($netip) . ".ping.json";
	$json  = file_get_contents($file);
	$data  = json_decdat($json);
	
	if (! isset($data[ "lowip" ])) return false;
	
	return ($data[ "lowip" ] == $ip);
}

function Read_ISP_IP_Blocks($locations = null,$ranges = null)
{
	$blocks = Array();
	
	$fd = fopen("../lib/MaxMind.GeoLiteCity.Blocks.csv","r");
	
	fgets($fd);
	fgets($fd);
	
	while (($line = fgets($fd)) != null)
	{
		$parts = explode(",",trim($line));
		if (count($parts) != 3) continue;
		
		$block = Array();
		
		$block[ "from" ] = To_Int($parts[ 0 ]);
		$block[ "last" ] = To_Int($parts[ 1 ]);
		$block[ "lid"  ] = To_Int($parts[ 2 ]);

		$found = false;
		
		foreach ($ranges as $range)
		{
			if (($block[ "from" ] >= $range[ "from" ]) &&
				($block[ "from" ] <= $range[ "last" ]))
			{
				$found = true;
				break;
			}
		}

		if (! $found) continue;
		
		if ($locations != null)
		{
			if (isset($locations[ $block[ "lid" ] ]))
			{
				$block[ "loc" ] = $locations[ $block[ "lid" ] ];
			}
		}
		
		array_push($blocks,$block);
	}
	
	fclose($fd);
	
	return $blocks;
}

function Read_IP_Locations($country = null)
{
	$locations = Array();
	
	$fd = fopen("../lib/MaxMind.GeoLiteCity.Location.csv","r");
	
	fgets($fd);
	fgets($fd);
	
	while (($line = fgets($fd)) != null)
	{
		$line = utf8_encode($line);
		
		$parts = explode(",",trim($line));
		if (count($parts) != 9) continue;
		
		if (($country != null) && ($parts[ 1 ] != "\"$country\"")) continue;
		
		$location = Array();
		
		$location[ "id"      ] = intval($parts[ 0 ]);
		$location[ "country" ] = substr($parts[ 1 ],1,-1);
		$location[ "region"  ] = substr($parts[ 2 ],1,-1);
		$location[ "city"    ] = substr($parts[ 3 ],1,-1);
		$location[ "lat"     ] = floatval($parts[ 5 ]);
		$location[ "lon"     ] = floatval($parts[ 6 ]);
		
		$locations[ $location[ "id" ] ] = $location;
	}
	
	fclose($fd);
	
	return $locations;
}

function Read_ISP_IP_Ranges($isp = null)
{
	$ranges = Array();
	
	$fd = fopen("../lib/Nirsoft.ISP.de.csv","r");
	
	while (($line = fgets($fd)) != null)
	{
		$parts = explode(",",trim($line));
		if (count($parts) != 5) continue;
		
		if (($isp != null) && ($parts[ 4 ] != $isp)) continue;
		
		$range = Array();
		
		$range[ "from" ] = IP_Bin($parts[ 0 ]);
		$range[ "last" ] = IP_Bin($parts[ 1 ]);
		
		array_push($ranges,$range);
	}
	
	fclose($fd);
	
	return $ranges;
}

?>