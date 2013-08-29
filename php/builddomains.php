<?php

include("../php/misc.php");
include("../php/json.php");

function MaxMindSearchGeo($ip)
{
	$geofile = "../lib/MaxMind.GeoIPCity.csv";
	
	$geofd = fopen($geofile,"r");
	
	$bin = IP2Bin($ip);
	$min = 0;
	$max = filesize($geofile);
	$geo = null;
	
	while ($max != $min)
	{	
		$act = floor(($min + $max) / 2);
		fseek($geofd,$act);
		
		$line = fgets($geofd);
		
		$act = ftell($geofd);
		$line = fgets($geofd);
		
		//echo "$ip => $line";
		
		$parts = explode(",",trim($line));
		
		if (count($parts) != 10)
		{
			echo "Geofucked...\n";
		}
		
		$ipfrom = IP2Bin($parts[ 0 ]);
		$iptoto = IP2Bin($parts[ 1 ]);
		
		if (($ipfrom <= $bin) && ($bin <= $iptoto))
		{		
			$geo = substr($parts[ 2 ],1,-1)
				 . ","
				 . Fix_Region(utf8_encode(substr($parts[ 3 ],1,-1)))
				 . ","
				 . Fix_City  (utf8_encode(substr($parts[ 4 ],1,-1)))
				 . ","
				 . $parts[ 6 ]
				 . ","
				 . $parts[ 7 ]
				 ;
				 
			break;
		}
		
		if ($bin < $ipfrom) { if ($max == $act) break; else $max = $act; }
		if ($bin > $iptoto) { if ($min == $act) break; else $min = $act; }
	}
	
	fclose($geofd);
	
	return $geo;
}

function GetHostByAddress($ip,$isp = "xx",$cache = "global")
{
	$cachefile = "../var/$isp/tmpcach/hostsbyaddr.$cache.json";
	
	if (! isset($GLOBALS[ "gethostcache" ])) 
	{
		$GLOBALS[ "gethostcache" ] = array();
		
		if (file_exists($cachefile))
		{
			$GLOBALS[ "gethostcache" ] = json_decdat(file_get_contents($cachefile));
		}
	}
	
	if (! isset($GLOBALS[ "gethostcache" ][ IPZero($ip) ]))
	{
		$GLOBALS[ "gethostcache" ][ IPZero($ip) ] = gethostbyaddr(IP($ip));
	}
	
	return $GLOBALS[ "gethostcache" ][ IPZero($ip) ];
}

function GetHostByAddressSave($isp = "xx",$cache = "global")
{
	$cachefile = "../var/$isp/tmpcach";
	if (! is_dir($cachefile)) mkdir($cachefile,0777);
	$cachefile .= "/hostsbyaddr.$cache.json";
	
	ksort($GLOBALS[ "gethostcache" ]);
	file_put_contents($cachefile,json_encdat($GLOBALS[ "gethostcache" ]) . "\n");
}

function BuildDomains($isp)
{
	$locations = array();
	$locationsfile = "../var/$isp/configs/location.json";
	
	if (file_exists($locationsfile))
	{
		$locations = json_decdat(file_get_contents($locationsfile));

		if ($locations === null)
		{
			echo "$locationsfile fucked up, exit...\n";
			exit();
		}
	}

	$backbones = array();
	$backbonesfile = "../var/$isp/mapdata/backbones.json";
	
	if (file_exists($backbonesfile))
	{
		$backbones = json_decdat(file_get_contents($backbonesfile));

		if ($backbones === null)
		{
			echo "$backbonesfile fucked up, exit...\n";
			exit();
		}
	}

	$inter = "xx";
	$local = substr($isp,0,2);
	
	$topinter = json_decdat(file_get_contents("../var/xx/manual/topdomains.$inter.json"));
	$toplocal = json_decdat(file_get_contents("../var/xx/manual/topdomains.$local.json"));
	
	$olddomains = array();
	
	if (file_exists("../var/$isp/domains"))
	{
		$dfd = opendir("../var/$isp/domains");
		
		while (($file = readdir($dfd)) !== false)
		{
			if (substr($file,0,1) == ".") continue;
			
			$olddomains[ "../var/$isp/domains/$file" ] = true;
		}
		
		closedir($dfd);
	}
	
	$oldwebpings = array();
	
	if (file_exists("../var/$isp/webping"))
	{
		$dfd = opendir("../var/$isp/webping");
		
		while (($file = readdir($dfd)) !== false)
		{
			if (substr($file,0,1) == ".") continue;
			
			$oldwebpings[ "../var/$isp/webping/$file" ] = true;
		}
		
		closedir($dfd);
	}
	
	$oldgwypings = array();
	
	if (file_exists("../var/$isp/gwyping"))
	{
		$dfd = opendir("../var/$isp/gwyping");
		
		while (($file = readdir($dfd)) !== false)
		{
			if (substr($file,0,1) == ".") continue;
			
			$oldgwypings[ "../var/$isp/gwyping/$file" ] = true;
		}
		
		closedir($dfd);
	}
	
	$domains  = array();
	
	foreach ($topinter as $domain => $dummy) $domains[ $domain ] = true;
	foreach ($toplocal as $domain => $dummy) $domains[ $domain ] = true;
	
	$gateways = array();
	$domgates = array();
	
	foreach ($domains as $domain => $dummy)
	{
		$webpingsfile = "../var/$isp/webping/$domain.ping.json";
		if (isset($oldwebpings[ $webpingsfile ])) unset($oldwebpings[ $webpingsfile ]);
		
		$domainsfile = "../var/$isp/domains/$domain.json";
		if (isset($olddomains[ $domainsfile ])) unset($olddomains[ $domainsfile ]);
		
		$domdata = json_decdat(file_get_contents($domainsfile));
		
		if (! isset($domdata[ "paths" ])) continue;
		
		$alexarank = $domdata[ "axrank" ];
		
		foreach ($domdata[ "paths" ] as $path)
		{
			$last2ip = false;
			$last1ip = false;
			$next1ip = false;
			$back1ip = false;
			
			foreach ($path as $hop)
			{
				if (IPZero($hop) == "000.000.000.000") continue;
				
				$hopisp = ResolveISP($hop);
				
				if (isset($backbones[ IPZero($hop) ])) $back1ip = $hop;
				
				if ($hopisp == $isp) 
				{
					$last2ip = $last1ip;
					$last1ip = $hop;
					
					$host = GetHostByAddress($last1ip,$isp,"builddomains");
					
					if (($isp == "de/tk") && preg_match('/^[a-z]+-e[a-z]*[0-9]+-/',$host)) break;
				}
				else
				{
					if ($last1ip !== false) 
					{
						$next1ip = $hop;
						break;
					}
				}
			}
			
			if ($last1ip === false) continue;
			
			$gateway = IPZero($last1ip);
			
			if (! isset($gateways[ $gateway ])) 
			{
				$gateways[ $gateway ] = array();
				$gateways[ $gateway ][ "png"     ] = 0;
				$gateways[ $gateway ][ "loc"     ] = isset($locations[ $gateway ]) ? $locations[ $gateway ] : "n.n.";
				$gateways[ $gateway ][ "name"    ] = GetHostByAddress(IP($gateway));
				
				/*
				if ($last2ip !== false) 
				{
					$gateways[ $gateway ][ "lip"  ] = IPZero($last2ip);
					$gateways[ $gateway ][ "last" ] = GetHostByAddress($last2ip,$isp,"builddomains");
				}
				
				if ($next1ip !== false) 
				{
					$gateways[ $gateway ][ "nip"  ] = IPZero($next1ip);
					$gateways[ $gateway ][ "next" ] = GetHostByAddress($next1ip,$isp,"builddomains");
				}
				
				if ($back1ip !== false) 
				{
					$gateways[ $gateway ][ "bip"  ] = IPZero($back1ip);
					$gateways[ $gateway ][ "bloc" ] = $backbones[ IPZero($back1ip) ][ "loc" ];
				}
				*/
				
				$gateways[ $gateway ][ "domains" ] = array();
				$gateways[ $gateway ][ "routes" ] = array();
				
				if ($gateways[ $gateway ][ "loc" ] == "n.n.") echo "\t\"$gateway\" : \"" . $gateways[ $gateway ][ "name" ] . "\",\n";
			}
			
			$domgates[ $domain ][ $gateway ] = true;
			
			$gateways[ $gateway ][ "domains" ][ $domain ] = $alexarank;
			
			if ($gateways[ $gateway ][ "loc" ] == "n.n.")
			{
				$route = array();
				
				foreach ($path as $hop)
				{
					$val = isset($locations[ IPZero($hop) ]) ? $locations[ IPZero($hop) ] : null;	
					$val = ($val == null) ? GetHostByAddress($hop,$isp,"builddomains") : $val;
					$loc = MaxMindSearchGeo($hop);
					$val = $val . (($loc !== null) ? " => $loc" : "");
					
					$route[ IPZero($hop) ] = $val;
				}
				
				array_push($gateways[ $gateway ][ "routes" ],$route);
			}
			
			if ($gateways[ $gateway ][ "loc" ] != "n.n.")
			{
				//unset($gateways[ $gateway ]);
			}
		}
	}
	
	//
	// Post processing.
	//
	
	foreach ($gateways as $gateway => $gwdata)
	{	
		//
		// Remove from white-list.
		//
		
		$gwypingfile = "../var/$isp/gwyping/$gateway.ping.json";
		if (isset($oldgwypings[ $gwypingfile ])) unset($oldgwypings[ $gwypingfile ]);

		//
		// Aquire icmp status if possible.
		//
		
		$icmp = 0;
		
		if (file_exists($gwypingfile))
		{
			$icmp = 1;
			
			$pingdata = json_decdat(file_get_contents($gwypingfile));
			
			foreach ($pingdata[ $gateway ] as $stamp => $ms)
			{
				if ($ms != -1) $icmp = 2;
			}
			
			$mtrdoms = array();
			
			if (count($mtrdoms) < 4)
			{
				//
				// Try un-ambigous domains.
				//
			
				foreach ($gwdata[ "domains" ] as $domain => $dummy)
				{
					if (count($domgates[ $domain ]) == 1) $mtrdoms[] = $domain;
				
					if (count($mtrdoms) >= 4) break;
				}
			}
			
			if (count($mtrdoms) < 4)
			{
				//
				// Try ambigous domains.
				//
			
				foreach ($gwdata[ "domains" ] as $domain => $dummy)
				{
					if (count($domgates[ $domain ]) > 1) $mtrdoms[] = $domain;
				
					if (count($mtrdoms) >= 4) break;
				}
			}
			
			$gateways[ $gateway ][ "mtrdoms" ] = implode(",",$mtrdoms);
		}
		
		$gateways[ $gateway ][ "png" ] = $icmp;
		
		if (count($gateways[ $gateway ][ "routes" ]) == 0) 
		{
			unset($gateways[ $gateway ][ "routes" ]);
		}
	}
	
	//
	// List all routers with locations.
	//
	
	/*
	$alllocs = array();
	
	foreach ($backbones as $ip => $data)
	{
		$alllocs[ $ip ] = $data[ "loc" ];
	}
	
	foreach ($gateways as $ip => $data)
	{
		$alllocs[ $ip ] = $data[ "loc" ];
	}
	
	ksort($alllocs);
	
	foreach ($alllocs as $ip => $loc)
	{
		echo "$ip => $loc\n";
	}
	*/
	
	//
	// Dump result files.
	//
	
	ksort($gateways);
	$gatewaysfile = "../var/$isp/mapdata/gateways.json";
	$gatewaysjson = json_encdat($gateways) . "\n";
	file_put_contents($gatewaysfile,$gatewaysjson);
	
	ksort($olddomains);
	foreach ($olddomains as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		@unlink($file);
	}
	
	ksort($oldwebpings);
	foreach ($oldwebpings as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		@unlink($file);
	}
	
	ksort($oldgwypings);
	foreach ($oldgwypings as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		@unlink($file);
	}

	//
	// Re-arrange gateways for map.
	//
	
	$gatemap = array();
	
	foreach ($gateways as $gateip => $gwdata)
	{
		if ($gwdata[ "loc" ] == "n.n.") continue;
		
		$gate = array();
		
		$parts = explode(",",$gwdata[ "loc" ]);
		$gate[ "lat"     ] = floatval($parts[ 3 ]);
		$gate[ "lon"     ] = floatval($parts[ 4 ]);
		$gate[ "loc"     ] = $gwdata[ "loc"     ];
		$gate[ "name"    ] = $gwdata[ "name"    ];
		$gate[ "domains" ] = $gwdata[ "domains" ];
		
		$gatemap[ $gateip ] = $gate;
	}
	
	$gatemapfile = "../www/$isp/gateways.map.js";
	$gatemapjson = json_encdat($gatemap) . "\n";
	$gatemapjson = "kappa.GatewaysCallback(\n" . $gatemapjson . ");\n";
	file_put_contents($gatemapfile,$gatemapjson);

	GetHostByAddressSave($isp,"builddomains");
}

BuildDomains("de/tk");
BuildDomains("de/kd");


?>