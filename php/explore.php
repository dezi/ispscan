<?php

//
// Dezi iPhone...
//
// 80.187.100.191
//

include("../php/misc.php");
include("../php/json.php");

function Geoip_Get($ip)
{
	$isp = $GLOBALS[ "isp" ];
	
	$jsonfile = "../var/xx/geoip/" . IPZero($ip) . ".geoip.json";
	$htmlfile = "../var/xx/geoip/" . IPZero($ip) . ".geoip.html";
	
	$json = @file_get_contents($jsonfile);
	
	if ($json !== false)
	{
		$block = json_decdat($json);
		
		if ($block[ "loc" ][ "city" ] == "Munich"   ) $block[ "loc" ][ "city" ] = "München";
		if ($block[ "loc" ][ "city" ] == "Nuremberg") $block[ "loc" ][ "city" ] = "Nürnberg";
		
		return $block;
	}
	
	$html = @file_get_contents($htmlfile);
	
	if ($html === false)
	{
		$html = file_get_contents("http://www.geoiptool.com/de/?IP=" . Bin2IP($ip));
		file_put_contents($htmlfile,$html);
	}

	$block = Array();
	
	$block[ "from" ] = Bin2IP($ip);
	$block[ "last" ] = Bin2IP($ip + 127);
	$block[ "loc"  ] = Array();
	
	if (preg_match('/Landesk&uuml;rzel:\<\/span\>.+?arial_bold\"\>([^ ]*)/s',$html,$result))
	{
		$block[ "loc"  ][ "country" ] = $result[ 1 ];
	}
	
	if (preg_match('/Region:\<\/span\>.+?_blank\"\>([^<]*)/s',$html,$result))
	{
		$block[ "loc"  ][ "region" ] = utf8_encode($result[ 1 ]);
	}
	
	if (preg_match('/Stadt:\<\/span\>.+?arial_bold\"\>([^<]*)/s',$html,$result))
	{
		$city = utf8_encode($result[ 1 ]);
		
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
		
		$block[ "loc"  ][ "city" ] = $city;
	}
	
	if (preg_match('/L&auml;ngengrad:\<\/span\>.+?arial_bold\"\>([^<]*)/s',$html,$result))
	{
		$block[ "loc"  ][ "lon" ] = floatval($result[ 1 ]);
	}
	
	if (preg_match('/Breitengrad:\<\/span\>.+?arial_bold\"\>([^<]*)/s',$html,$result))
	{
		$block[ "loc"  ][ "lat" ] = floatval($result[ 1 ]);
	}
	
	file_put_contents($jsonfile,json_encdat($block) . "\n");
	
	if ($block[ "loc" ][ "city" ] == "Munich"   ) $block[ "loc" ][ "city" ] = "München";
	if ($block[ "loc" ][ "city" ] == "Nuremberg") $block[ "loc" ][ "city" ] = "Nürnberg";
		
	return $block;
}

function InspectRange($ipfrom,$iptoto,$netbound = 1,$nowrite = false)
{	
	$GLOBALS[ "isp" ] = $isp = ResolveISP($ipfrom);
	if ($GLOBALS[ "isp" ] == "xx/xx") return;
	
	$ipfrom = IP($ipfrom);
	$iptoto = IP($iptoto);
	
	//
	// Read geo data.
	//
	
	$geodatafile = "../lib/MaxMind.GeoIPCity.csv";
	
	if (! file_exists($geodatafile))
	{
		echo "No geodata...\n";
		exit();
	}
	
	$geocachefile = "../var/$isp/tmpcach/" 
				  . IPZero($ipfrom) 
				  . "-" 
				  . IPZero($iptoto) 
				  . ".geoip.log"
				  ;

	$geofd = null;
	$cacfd = null;
	
	if (file_exists($geocachefile))
	{
		$geofd = fopen($geocachefile,"r");
	}
	else
	{
		$cacfd = fopen($geocachefile,"w");
		$geofd = fopen($geodatafile,"r");
		fgets($geofd);
	}
	
	$from = IPZero(IP2Bin($ipfrom));
	$toto = IPZero(IP2Bin($iptoto));
	
	$geodata = array();
	
	while (($line = fgets($geofd)) != null)
	{
		$parts = explode(",",trim($line));
		
		$geofrom = IPZero(IP2Bin($parts[ 0 ]));
		$geototo = IPZero(IP2Bin($parts[ 1 ]));
		
		if ($geofrom < $from) continue;
		if ($geofrom > $toto) continue;
		
		if ($cacfd) fputs($cacfd,$line);
		
		$country = substr($parts[ 2 ],1,-1);
		$region  = Fix_Region(utf8_encode(substr($parts[ 3 ],1,-1)));
		$city    = Fix_City  (utf8_encode(substr($parts[ 4 ],1,-1)));
		$lat     = floatval($parts[ 6 ]);
		$lon     = floatval($parts[ 7 ]);
		$loc     = "$country,$region,$city,$lat,$lon";
		
		$geofrombin = IP2Bin($geofrom);
		$geototobin = IP2Bin($geototo);
		
		for ($dumpip = $geofrombin; $dumpip < $geototobin; $dumpip += 128)
		{
			$geodata[ IPZero($dumpip) ] = $loc;
			
			echo IPZero($dumpip) . " $loc\n";
		}
	}
	
	if ($geofd) fclose($geofd);
	if ($cacfd) fclose($cacfd);
	
	//
	// Read old version of data for
	// maintaining manual fixups.
	//
	
	$from = IP2Bin($ipfrom);
	$toto = IP2Bin($iptoto);
	
	$logfile = "../var/$isp/netmaps/" 
			. IPZero($ipfrom) 
			. "-" 
			. IPZero($iptoto) 
			. ".orig.log"
			;
	
	$bakfile = "../var/$isp/backups/" 
			 . IPZero($ipfrom) 
			 . "-" 
			 . IPZero($iptoto) 
			 . ".orig."
			 . date("Ymd.His")
			 . ".log"
			 ;
	
	$tuneups = array();
	$tunenet = array();
	$tuneips = array();
	$netfrom = IPZero($ipfrom);
	
	if (file_exists($logfile))
	{
		$oldfd = fopen($logfile,"r");
		
		while (($line = fgets($oldfd)) != null)
		{
			$line = trim($line);
			
			if ($line == "<")
			{
				$ipzero = IPZero(IP2Bin($ipzero) + 255);
				$netcnt = ((IP2Bin($ipzero) + 1) - IP2Bin($netfrom)) / 256;
				$tunenet[ $ipzero  ] = "=0*" . $netcnt;
				
				$tuneips[ $netfrom ] = IPZero(IP2Bin($ipzero) + 1);
				
				$netfrom = IPZero(IP2Bin($ipzero) + 1);
				
				continue;
			}
			
			if ((substr($line,17,1) == "^") || 
				(substr($line,17,1) == "*") || 
				(substr($line,17,1) == "?"))
			{
				//
				// Network delimiter.
				//
				
				$ipzero = substr($line,0,15);
				$netcnt = ((IP2Bin($ipzero) + 1) - IP2Bin($netfrom)) / 256;
				$rest   = substr($line,15,3) . $netcnt;
				
				$tunenet[ $ipzero  ] = $rest;
				$tuneips[ $netfrom ] = $ipzero;
				
				$netfrom = IPZero(IP2Bin($ipzero) + 1);
			}
			
			if ((substr($line,15,1) == "=") && (substr($line,17,1) == "+"))
			{
				$ipzero = substr($line,0,15);
				$rest   = substr($line,15);
			
				$tunenet[ $ipzero  ] = $rest;
				$tuneips[ $netfrom ] = $ipzero;

				$netfrom = IPZero(((IP2Bin($ipzero) >> 8) << 8) + 256);
			}
			
			if ((substr($line,15,1) == "=") && (substr($line,17,1) == " "))
			{
				$parts = explode("=",$line);
				if (count($parts) != 2) continue;
			
				$ipzero = $parts[ 0 ];
				$rest   = $parts[ 1 ];
			
				//
				// Fixup unconsolidated region numbers.
				//
				
				$parts = explode(",",$rest);
				if (strlen($parts[ 1 ]) == 2) $parts[ 1 ] = Fix_Region($parts[ 1 ]);
				$rest = implode(",",$parts);
				
				if (! isset($tuneups[ $ipzero ])) $tuneups[ $ipzero ] = array();
			
				array_push($tuneups[ $ipzero ],$rest);
			}
		}
		
		fclose($oldfd);
	}

	if ($isp == "de/vfxxxxx")
	{
		//
		// Experimental force exactly 4 cnets.
		//
		
		$tunenet  = array();
		$manbound = 4;
		
		for ($ip = $from; $ip < $toto; $ip += ($manbound * 256))
		{
			$tunenet[ IPZero($ip + ($manbound * 256) - 1) ] = "=0*" . $manbound;
		}
	}
	
	//
	// Read no traceroute routers from config if required
	// and merge with existing network ranges.
	//
	
	$notraces = array();
	$notracesfile = "../var/$isp/configs/notraces.json";

	if (file_exists($notracesfile))
	{
		$notracesdata = json_decdat(file_get_contents($notracesfile));
		$notracesip   = IP2Bin("001.000.000.000");
		
		foreach ($notracesdata as $noip => $dummy)
		{
			$notracesdata[ $noip ] = IPZero(++$notracesip);
		}
		
		$binfrom = IP2Bin($ipfrom);
		$bintoto = IP2Bin($iptoto);

		foreach ($notracesdata as $noip => $dummyip)
		{
			$ipbin = IP2Bin($noip);
			
			if ($ipbin < $binfrom) continue;
			if ($ipbin > $bintoto) continue;
			
			if (! isset($tuneips[ $noip ]))
			{
				var_dump($tuneips);
				echo "UNRESOLVED: $noip\n";
				exit();
			}
			
			$netfrom = IP2Bin($noip);
			$nettoto = IP2Bin($tuneips[ $noip ]);
			
			while ($netfrom < $nettoto)
			{
				$notraces[ IPZero($netfrom) ] = $dummyip;
				$netfrom += 128;
			}
		}
	}
	
	//
	// Read manual router locations if required.
	//
	
	$locations = array();
	$locationsfile = "../var/$isp/configs/location.json";
	
	if (file_exists($locationsfile))
	{
		$locations = json_decdat(file_get_contents($locationsfile));
	}
	
	$logfd = fopen($logfile . ".tmp","w");
	
	$hostnames = array();
	
	$usedgates = array();
	$gwgroup   = array();
	$gwcount   = 0;
	
	$start = IP2Bin($ipfrom);
	
	while ($from <= ($toto + 1))
	{	
		$isp = $GLOBALS[ "isp" ];

		$explorefile = "../var/$isp/explore/" . IPZero($from) . ".explore.json";
		$mtrlogsfile = "../var/$isp/mtrlogs/" . IPZero($from) . ".mtrlog.json";
		
		if (($from <= $toto) && file_exists($explorefile) && ! file_exists($mtrlogsfile)) 
		{
			echo "NOMTR:" . IPZero($from) . "\n";
			fputs($logfd,"NOMTR:" . IPZero($from) . "\n");
		}
		
		$ingroup   = true;
		$lasthops  = array();
		$lastgates = array();
		
		if (file_exists($mtrlogsfile))
		{
			$mtrs = json_decdat(file_get_contents($mtrlogsfile));
			
			foreach ($mtrs[ "paths" ] as $path)
			{
				if (count($path) < 3) continue;
				
				$lasthop = IPZero(array_pop($path));
				if (substr($lasthop,0,11) != substr(IPZero($from),0,11)) continue;
				
				$lasthop = IPZero(array_pop($path));
				if ($lasthop == "000.000.000.000") continue;
				
				$lastgate = IPZero(array_pop($path));
				if ($lastgate == "000.000.000.000") continue;
				
				$lasthops [ $lasthop  ] = true;
				$lastgates[ $lastgate ] = true;
			}
			
			if ((count($lasthops) == 0) || isset($notraces[ IPZero($from) ]))
			{
				$lasthops  = array();
				$lastgates = array();
				
				//
				// We have a network with probably non tracing
				// routers. Retry with manual config list.
				//
				
				foreach ($mtrs[ "paths" ] as $path)
				{
					if (count($path) < 3) continue;
				
					$lasthop = IPZero(array_pop($path));
					if (substr($lasthop,0,11) != substr(IPZero($from),0,11)) continue;
				
					$lasthop = IPZero(array_pop($path));
					
					if ($lasthop == "000.000.000.000")
					{
						if (isset($notraces[ IPZero($from) ]))
						{
							//
							// Gateway is a configured notraceroute router. 
							//
						
							$lasthop = $notraces[ IPZero($from) ];
						}
						else
						{
							//
							// Gateway is a spacko gateway. just pop another hop.
							//
							
							$lasthop = IPZero(array_pop($path));
						}
					}
					else
					{
						if (isset($notraces[ IPZero($from) ]))
						{
							//
							// Gateway is a configured phantom router.
							//

							array_push($path,$lasthop);
							
							echo "NOVISIBLE:" . IPZero($from) . "\n";
							fputs($logfd,"NOVISIBLE:" . IPZero($from) . "\n");

							$lasthop = $notraces[ IPZero($from) ];
						}
					}
					
					if ($lasthop == "000.000.000.000") continue;

					$lastgate = IPZero(array_pop($path));
					if ($lastgate == "000.000.000.000") continue;
				
					$lasthops [ $lasthop  ] = true;
					$lastgates[ $lastgate ] = true;
				}
			}
			
			if (count($lasthops) == 0)
			{
				//@unlink($mtrlogsfile);
			}
		}
		
		if (count($lasthops) == 0)
		{
			//
			// Unrouted network. Set dummy router.
			//
			
			$lasthops[ "000.000.000.000" ] = true;
		}
			
		$netdiv = NetDiv($from);

		$ingroup = false;
		
		foreach ($lasthops as $gwip => $dummy)
		{
			$ingroup = $ingroup || isset($gwgroup[ $gwip ]);
		}
	
		if (count($gwgroup) && ! count($lasthops)) $ingroup = false;
			
		$wantline = false;
		$havegate = false;
	
		if (($from % (256 * $netbound)) == 0)
		{
			$newnet = ((count($gwgroup) || ($netdiv >= 64)) && ((! $ingroup) || ($gwcount >= 64)));
			$newnet = (! $ingroup) || ($gwcount >= 64) || ($netdiv >= 64);
			$newnet = $newnet || isset($tunenet[ IPZero($from - 1) ]);
			$newnet = $newnet || isset($lasthops[ "000.000.000.000" ]);
			
			if ($newnet)
			{
				if ($from != $start)
				{
					if ($usedgates !== null)
					{
						foreach ($usedgates as $usedgate => $dummy)
						{
							if (! isset($hostnames[ $usedgate ]))
							{
								if (isset($locations[ $usedgate ]))
								{
									$hostnames[ $usedgate ] = $locations[ $usedgate ];
								}
								else
								{
									$hostnames[ $usedgate ] = gethostbyaddr(IP($usedgate));
								}
							}
			
							$host = $hostnames[ $usedgate ];
					
							echo "$usedgate~$host\n";
							fputs($logfd,"$usedgate~$host\n");
						}
						
						$usedgates = array();
						$havegate = true;
					}
					
					if (count($tunenet) == 0)
					{
						if ($isp == "de/kd")
						{
							echo IPZero($from - 2) . "=0+\n";
							fputs($logfd,IPZero($from - 2) . "=0+\n");
						}
						
						$gwtag = "*";
				
						if ($gwcount > $netdiv)   $gwtag = "?";
						if (! IsPower2($gwcount)) $gwtag = "?";
				
						echo IPZero($from - 1) . "=0$gwtag$gwcount\n";
						fputs($logfd,IPZero($from - 1) . "=0$gwtag$gwcount\n");

						$gwgroup   = array();
						$gwcount   = 0;
					}
				}
			}
		}
		
		if (isset($tunenet[ IPZero($from - 2) ]))
		{
			if (($usedgates !== null) && ! $havegate)
			{
				foreach ($usedgates as $usedgate => $dummy)
				{
					if (! isset($hostnames[ $usedgate ]))
					{
						if (isset($locations[ $usedgate ]))
						{
							$hostnames[ $usedgate ] = $locations[ $usedgate ];
						}
						else
						{
							$hostnames[ $usedgate ] = gethostbyaddr(IP($usedgate));
						}
					}

					$host = $hostnames[ $usedgate ];
		
					echo "$usedgate~$host\n";
					fputs($logfd,"$usedgate~$host\n");
				}
						
				$usedgates = array();
				$havegate = true;
			}
					
			echo IPZero($from - 2) . $tunenet[ IPZero($from - 2) ]. "\n";
			fputs($logfd,IPZero($from - 2) . $tunenet[ IPZero($from - 2) ]. "\n");
			
			$gwtag = isset($tunenet[ IPZero($from - 1) ]) ? substr($tunenet[ IPZero($from - 1) ],2,1) : "*";
				
			if ($gwcount > $netdiv)   $gwtag = "?";
			if (! IsPower2($gwcount)) $gwtag = "?";
				
			echo IPZero($from - 1) . "=0$gwtag$gwcount\n";
			fputs($logfd,IPZero($from - 1) . "=0$gwtag$gwcount\n");
			
			$wantline  = true;
			$gwgroup   = array();
			$gwcount   = 0;
		}
		else
		{
			if (isset($tunenet[ IPZero($from - 1) ]))
			{
				if (($usedgates !== null) && ! $havegate)
				{
					foreach ($usedgates as $usedgate => $dummy)
					{
						if (! isset($hostnames[ $usedgate ]))
						{
							if (isset($locations[ $usedgate ]))
							{
								$hostnames[ $usedgate ] = $locations[ $usedgate ];
							}
							else
							{
								$hostnames[ $usedgate ] = gethostbyaddr(IP($usedgate));
							}
						}

						$host = $hostnames[ $usedgate ];
		
						echo "$usedgate~$host\n";
						fputs($logfd,"$usedgate~$host\n");
					}
						
					$usedgates = array();
					$havegate = true;
					$wantline = true;
				}
					
				echo IPZero($from - 1) . $tunenet[ IPZero($from - 1) ]. "\n";
				fputs($logfd,IPZero($from - 1) . $tunenet[ IPZero($from - 1) ]. "\n");
				$wantline = true;
			}
		}
				
		if ($from >= $toto) break;

		if (($from % (256 * 8)) == 0)
		{
			if ($from != $start)
			{
				echo "=======$netdiv\n";
				fputs($logfd,"=======$netdiv\n");
			}
		}
		else
		{
			if ($wantline)
			{
				echo "-------\n";
				fputs($logfd,"-------\n");
			}
		}
		
		foreach ($lasthops as $gwip => $dummy)
		{
			$gwgroup[ $gwip ] = true;
		}
		
		foreach ($lastgates as $gwip => $dummy)
		{
			$usedgates[ $gwip ] = true;
		}
		
		$gwcount += 1;
		
		if (! isset($geodata[ IPZero($from) ]))
		{
			$text1 = "DE,,,51,9";
		}
		else
		{
			$text1 = $geodata[ IPZero($from) ];
		}
		
		$ipzero = IPZero($from);
		$rest   = "0 $text1";
		
		//
		// Replace similar geo positions.
		//
		
		if (isset($tuneups[ $ipzero ]) &&
			(count($tuneups[ $ipzero ]) == 1) &&
			(substr($tuneups[ $ipzero ][ 0 ],2) != substr($rest,2)))
		{
			$tunep = explode(",",$tuneups[ $ipzero ][ 0 ]);
			$restp = explode(",",$rest);
			
			$tunelat = floatval($tunep[ 3 ]);
			$tunelon = floatval($tunep[ 4 ]);
			$restlat = floatval($restp[ 3 ]);
			$restlon = floatval($restp[ 4 ]);
			
			if ((abs($tunelat - $restlat) < 0.8) &&
				(abs($tunelon - $restlon) < 0.8))
			{
				$tuneups[ $ipzero ][ 0 ] = $rest;
			}
		}
		
		if (isset($tuneups[ $ipzero ]))
		{
			if (count($tuneups[ $ipzero ]) > 2)
			{
				foreach ($tuneups[ $ipzero ] as $rest)
				{
					echo "$ipzero=$rest\n";
					fputs($logfd,"$ipzero=$rest\n");
				}
			}
			else
			{
				if (count($tuneups[ $ipzero ]) == 2)
				{
					echo "$ipzero=" . $tuneups[ $ipzero ][ 0 ] . "\n";
					fputs($logfd,"$ipzero=" . $tuneups[ $ipzero ][ 0 ] . "\n");
					array_shift($tuneups[ $ipzero ]);
				}
				
				if (count($tuneups[ $ipzero ]) == 1)
				{
					if ((substr($tuneups[ $ipzero ][ 0 ],2) == "DE,,,51,9") &&
						(substr($rest,2) != "DE,,,51,9"))
					{
						$tuneups[ $ipzero ][ 0 ] = $rest;
					}
					
					echo "$ipzero=" . $tuneups[ $ipzero ][ 0 ] . "\n";
					fputs($logfd,"$ipzero=" . $tuneups[ $ipzero ][ 0 ] . "\n");
					
					if ((substr($rest,2) != substr($tuneups[ $ipzero ][ 0 ],2)) && 
						(substr($rest,2) != "DE,,,51,9"))
					{
						echo "$ipzero!$rest\n";
						fputs($logfd,"$ipzero!$rest\n");
					}
				}
			}
		}
		else
		{
			echo "$ipzero=$rest\n";
			fputs($logfd,"$ipzero=$rest\n");
		}
		
		if ((($GLOBALS[ "isp" ] == "de/vf") && (count($tunenet) == 0)) ||
			(($GLOBALS[ "isp" ] == "de/kd") && (count($tunenet) == 0)))
		{
			if (isset($geodata[ IPZero($from + 128) ]))
			{
				$text2 = $geodata[ IPZero($from + 128) ];
		
				if (($text1 != $text2) && ($text2 != "DE,,,51,9"))
				{
					$ipzero = IPZero($from + 128);
					$rest   = "0 $text2";

					if (! $tuneups[ $ipzero ])
					{
						echo "$ipzero=$rest\n";
						fputs($logfd,"$ipzero=$rest\n");
					}
				}
			}
		}
		
		foreach ($lasthops as $gwip => $dummy)
		{
			if (IPZero($gwip) == "000.000.000.000") continue;
			
			echo IPZero($gwip) . "@\n";
			fputs($logfd,IPZero($gwip) . "@\n");
		}
		
		if (! $nowrite) 
		{
			$explore = array();
		
			$explore[ "ip" ] = IPZero($from);
		
			$explorejson = json_encdat($explore) . "\n";
			file_put_contents($explorefile,$explorejson);
		}
		
		$from += 256;
	}
	
	if (($from % (256 * 8)) == 0)
	{
		$netdiv = NetDiv($from);
		echo "=======$netdiv\n";
		fputs($logfd,"=======$netdiv\n");
	}

	fclose($logfd);
	
	if (file_exists($logfile)) rename($logfile,$bakfile);
	rename($logfile . ".tmp",$logfile);
}

function Explore($isp,$minsize,$netbound)
{
	$lines = file("../lib/Nirsoft.ISP.de.csv");

	foreach ($lines as $line)
	{
		$parts = explode(",",trim($line));
		if (count($parts) != 5) continue;
		
		$entry = array();
		
		$from = $parts[ 0 ];
		$upto = $parts[ 1 ];
		$size = intval($parts[ 2 ]);

		if (ResolveISP($from) != $isp) continue;
		
		echo "$isp: " . IPZero($from) . "-" . IPZero($upto) . " => $size\n";
		
		if ($size < $minsize) continue;
		
		InspectRange($from,$upto,$netbound);
	}
}

	if (count($_SERVER[ "argv" ]) < 2)
	{
		echo "Which ISP?\n";
		exit();
	}
	
	$isp = $_SERVER[ "argv" ][ 1 ];
	echo "ISP=$isp\n";
	
	if ($isp == "de/vf")
	{
		InspectRange("002.200.000.000","002.207.255.255",8);
		InspectRange("077.024.000.000","077.025.255.255",8);
		InspectRange("082.082.000.000","082.083.255.255",8);
		InspectRange("084.056.000.000","084.063.255.255",8);
		InspectRange("088.064.000.000","088.079.255.255",8);
		InspectRange("090.186.000.000","090.187.255.255",8);
		InspectRange("092.072.000.000","092.079.255.255",8);
		InspectRange("094.216.000.000","094.223.255.255",8);
		
		InspectRange("109.040.000.000","109.047.255.255",8);
		InspectRange("109.084.000.000","109.085.255.255",8);
		InspectRange("176.094.000.000","176.095.255.255",8);
		InspectRange("178.000.000.000","178.015.255.255",8);
		InspectRange("188.096.000.000","188.111.255.255",8);

		//Explore("de/vf",130000,8);
	}

	if ($isp == "de/tf")
	{
		InspectRange("002.240.000.000","002.247.255.255",8);
		InspectRange("077.000.000.000","077.015.255.255",8);
		InspectRange("077.176.000.000","077.191.255.255",8);
		InspectRange("078.048.000.000","078.055.255.255",8);
		InspectRange("085.176.000.000","085.183.255.255",8);
		InspectRange("089.012.000.000","089.013.255.255",8);
		InspectRange("089.014.000.000","089.015.255.255",8);
		InspectRange("092.224.000.000","092.231.255.255",8);
		InspectRange("093.128.000.000","093.135.255.255",8);
		InspectRange("095.112.000.000","095.119.255.255",8);
		InspectRange("195.071.000.000","195.071.255.255",8);
		InspectRange("213.039.128.000","213.039.255.255",8);
		InspectRange("217.048.000.000","217.051.255.255",8);
		InspectRange("217.184.000.000","217.191.255.255",8);
		
		//Explore("de/tf",130000,8);
	}
	
	if ($isp == "de/tk")
	{  
		//
		// TK
		//

		InspectRange("046.080.000.000","046.095.255.255",8);	
		InspectRange("079.192.000.000","079.255.255.255",8);
		InspectRange("084.128.000.000","084.191.255.255",8);
		InspectRange("091.000.000.000","091.063.255.255",8);
		InspectRange("093.192.000.000","093.255.255.255",8);
		InspectRange("080.128.000.000","080.159.255.255",8);
		InspectRange("080.187.000.000","080.187.255.255",8);
		InspectRange("087.128.000.000","087.159.255.255",8);
		InspectRange("087.160.000.000","087.191.255.255",8);
		InspectRange("217.000.000.000","217.007.255.255",8);
		InspectRange("217.080.000.000","217.095.255.255",8);
		InspectRange("217.224.000.000","217.255.255.255",8);	
	
		//Explore("de/tk",500000,8);
	}
	
	if ($isp == "de/kd")
	{
		//
		// KD
		//

		InspectRange("024.134.000.000","024.134.255.255",8);
		InspectRange("031.016.000.000","031.019.255.255",8);
		InspectRange("037.004.000.000","037.005.255.255",8);
		InspectRange("077.020.000.000","077.023.255.255",8);
		InspectRange("083.169.128.000","083.169.191.255",8); // internal
		InspectRange("088.134.000.000","088.134.191.255",8);
		InspectRange("088.134.192.000","088.134.255.255",8); // internal
		InspectRange("091.064.000.000","091.067.255.255",8);
		InspectRange("095.088.000.000","095.091.255.255",8);
		InspectRange("146.052.000.000","146.052.255.255",8);
		InspectRange("178.024.000.000","178.027.255.255",8);
		InspectRange("188.192.000.000","188.195.255.255",8);
	}
?>