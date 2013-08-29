<?php

	include("../php/util.php");
	include("../php/json.php");
	
	if (count($_SERVER[ "argv" ]) < 2)
	{
		echo "Which ISP?\n";
		exit();
	}
	
	$isp = $_SERVER[ "argv" ][ 1 ];
	echo "ISP=$isp\n";
	
	$tobuilds = Array();
	
	if ($isp == "de/tf")
	{
		array_push($tobuilds,"002.240.000.000-002.247.255.255");
		array_push($tobuilds,"077.000.000.000-077.015.255.255");
		array_push($tobuilds,"077.176.000.000-077.191.255.255");
		array_push($tobuilds,"078.048.000.000-078.055.255.255"); // work
		array_push($tobuilds,"085.176.000.000-085.183.255.255"); // work
		array_push($tobuilds,"089.012.000.000-089.013.255.255");
		array_push($tobuilds,"089.014.000.000-089.015.255.255");
		array_push($tobuilds,"092.224.000.000-092.231.255.255"); // work
		array_push($tobuilds,"093.128.000.000-093.135.255.255");
		array_push($tobuilds,"095.112.000.000-095.119.255.255");
		array_push($tobuilds,"217.048.000.000-217.051.255.255");
		array_push($tobuilds,"217.184.000.000-217.191.255.255");
		
	}
	
	if ($isp == "de/kd")
	{
		
		array_push($tobuilds,"024.134.000.000-024.134.255.255");
		array_push($tobuilds,"031.016.000.000-031.019.255.255");
		array_push($tobuilds,"037.004.000.000-037.005.255.255");
		array_push($tobuilds,"077.020.000.000-077.023.255.255");
		array_push($tobuilds,"088.134.000.000-088.134.191.255");
		array_push($tobuilds,"091.064.000.000-091.067.255.255");
		array_push($tobuilds,"095.088.000.000-095.091.255.255");
		array_push($tobuilds,"146.052.000.000-146.052.255.255");
		array_push($tobuilds,"178.024.000.000-178.027.255.255");
		array_push($tobuilds,"188.192.000.000-188.195.255.255");
	}

	if ($isp == "de/tk")
	{
		array_push($tobuilds,"046.080.000.000-046.095.255.255");
		array_push($tobuilds,"079.192.000.000-079.255.255.255");
		array_push($tobuilds,"080.128.000.000-080.159.255.255");
		array_push($tobuilds,"084.128.000.000-084.191.255.255");
		array_push($tobuilds,"087.128.000.000-087.159.255.255");
		array_push($tobuilds,"087.160.000.000-087.191.255.255");
		array_push($tobuilds,"091.000.000.000-091.063.255.255");
		array_push($tobuilds,"093.192.000.000-093.255.255.255");
		array_push($tobuilds,"217.000.000.000-217.007.255.255");
		array_push($tobuilds,"217.080.000.000-217.095.255.255");
		array_push($tobuilds,"217.224.000.000-217.255.255.255");
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
	if (! isset($GLOBALS[ "gethostcache" ])) return;
	
	$cachefile = "../var/$isp/tmpcach";
	if (! is_dir($cachefile)) mkdir($cachefile,0777);
	$cachefile .= "/hostsbyaddr.$cache.json";
	
	ksort($GLOBALS[ "gethostcache" ]);
	file_put_contents($cachefile,json_encdat($GLOBALS[ "gethostcache" ]) . "\n");
}

function CheckGateways($isp,&$uplinks)
{
	$gateways = $uplinks;
	
	$notraces = array();
	
	ksort($gateways);
	
	$numcities = array();
	
	foreach ($gateways as $routerip => $gwdata)
	{
		$number = count(GetDifferentCities($gwdata[ "epls" ]));
		
		foreach ($gwdata[ "epls" ] as $subnetip => $dummy)
		{
			if ((! isset($numcities[ $subnetip ])) || ($numcities[ $subnetip ] > $number))
			{
				$numcities[ $subnetip ] = $number;
			}
		}
	}
	
	foreach ($gateways as $routerip => $gwdata)
	{
		if (count(GetDifferentCities($gwdata[ "epls" ])) == 1)
		{
			//
			// Move good locations at end.
			//
			
			unset($gateways[ $routerip ]);
			$gateways[ $routerip ] = $gwdata;
		}
		else
		{
			//
			// Gateway with multiple locations. Check if each
			// routed subnet has a single location gateway as well.
			// If so, this is a backbone router and not a gateway.
			//
			
			$allsingle = true;
			
			foreach ($gwdata[ "epls" ] as $subnetip => $dummy)
			{
				if ($numcities[ $subnetip ] > 1) 
				{
					$gateways[ $routerip ][ "epls" ][ $subnetip ] .= "!!!";
					
					$notraces[ $subnetip ] = true;
					
					$allsingle = false;
				}
			}
			
			if ($allsingle)
			{
				//
				// All subnets have an individual gateway, so this is
				// a backbone router and not a gateway.
				//
				
				unset($gateways[ $routerip ]);
				continue;
			}
		}
	}
	
	//
	// Rule based cleanup.
	//
	
	foreach ($gateways as $routerip => $gwdata)
	{
		$bb = false;
		
		$bb = $bb || (substr($routerip,0,11) == "083.169.128");
		$bb = $bb || (substr($routerip,0,11) == "083.169.129");
		$bb = $bb || (substr($routerip,0,11) == "088.134.237");
		$bb = $bb || (substr($routerip,0,11) == "088.134.238");
		$bb = $bb || (substr($routerip,0,11) == "088.134.201");
		$bb = $bb || (substr($routerip,0,11) == "088.134.202");
		$bb = $bb || (substr($routerip,0,11) == "088.134.203");
		$bb = $bb || (substr($routerip,0,11) == "088.134.204");
		$bb = $bb || (substr($routerip,0,11) == "088.134.205");
		
		if ($bb) unset($gateways[ $routerip ]);
	}
	
	foreach ($gateways as $routerip => $gwdata)
	{
		if (substr($routerip,0,7) == "001.000")
		{
			//
			// Move dummy locations at end.
			//
			
			unset($gateways[ $routerip ]);
			
			$gateways[ $routerip ] = $gwdata;
		}
	}
	
	foreach ($gateways as $routerip => $gwdata)
	{
		ksort($gateways[ $routerip ][ "epls" ]);
	}

	$uplinks = $gateways;
		
	ksort($notraces);

	return $notraces;
}

function BuildBackbones($isp,&$endpoint,&$uplinks,&$allbones,$stage)
{
	//
	// Reorganize endpoints array and build c-net reference.
	//
	
	$endpoints = array();
	$cnets     = array();
	
	foreach ($endpoint as $index => $subnet)
	{
		$endpoints[ $subnet[ "ip" ] ] = &$endpoint[ $index ];
		
		$netfrom = IP_Bin($subnet[ "ip" ]);
		$nettoto = IP_Bin($subnet[ "bc" ]);
			
		while ($netfrom < $nettoto)
		{
			$cnets[ IPZero($netfrom) ] = &$endpoint[ $index ];
			$netfrom += 256;
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
		$notracesip   = IP_Bin("001.000.000.000");
		
		unset($notracesdata[ "255.255.255.255" ]);
		
		foreach ($notracesdata as $noip => $dummy)
		{
			$notracesdata[ $noip ] = IPZero(++$notracesip);
		}

		foreach ($notracesdata as $noip => $dummyip)
		{
			$ipbin = IP_Bin($noip);

			if (! isset($endpoints[ $noip ]))
			{
				echo "UNRESOLVED: $noip\n";
				exit();
			}
			
			$netfrom = IP_Bin($endpoints[ $noip ][ "ip" ]);
			$nettoto = IP_Bin($endpoints[ $noip ][ "bc" ]);
			
			while ($netfrom < $nettoto)
			{
				$notraces[ IPZero($netfrom) ] = $dummyip;
				$netfrom += 128;
			}
		}
	}

	//
	// Read and inspect all mtrlogs files.
	//
	
	$backbones = array();

	$dfd = opendir("../var/$isp/mtrlogs");
	
	while (($file = readdir($dfd)) !== false)
	{
		if (substr($file,0,1) == ".") continue;
		
		$mtrlog = json_decdat(file_get_contents("../var/$isp/mtrlogs/$file"));
		
		foreach ($mtrlog[ "paths" ] as $path)
		{
			if (count($path) < 3) continue;
		
			$cnetip = substr(IPZero(array_pop($path)),0,11) . ".000";
			if (! isset($cnets[ $cnetip ])) continue;
			
			$subnetip = $cnets[ $cnetip ][ "ip" ];
			
			$lasthop = IPZero(array_pop($path));
			
			if ($lasthop == "000.000.000.000")
			{
				if (isset($notraces[ $cnetip ]))
				{
					//
					// Gateway is a configured notraceroute router. 
					//
				
					$lasthop = $notraces[ $cnetip ];
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
				if (isset($notraces[ $cnetip ]))
				{
					//
					// Gateway is a configured phantom router.
					//

					array_push($path,$lasthop);
					
					$lasthop = $notraces[ $cnetip ];
				}
			}

			if ($lasthop == "000.000.000.000") continue;
			if ((substr($lasthop,0,8) != "001.000.") && (ResolveISP($lasthop) != $isp)) continue;		

			if ($stage == 0)
			{
				if (! isset($uplinks[ $lasthop ])) 
				{
					echo "New uplink $lasthop from $file\n";
					
					$uplinks[ $lasthop ] = array();
					$uplinks[ $lasthop ][ "epls" ] = array();
				}
				
				$uplinks[ $lasthop ][ "epls" ][ $subnetip ] = $cnets[ $cnetip ][ "loc" ];
				
				continue;
			}

			$trace = ($lasthop == "088.134.220.049xxx");
			
			if ($trace) echo "TRACE: lasthop $lasthop\n";
			
			if ($stage == 1)
			{
				while (count($path))
				{
					$backbonesip = IPZero(array_pop($path));
					
					if ($trace) echo "TRACE: backboneip $backbonesip\n";

					if ($backbonesip == "000.000.000.000") break;
					if ($backbonesip == $lasthop) break;
					if (ResolveISP($backbonesip) != $isp) break;		
					
					if ($trace) echo "TRACE: set $backbonesip => $lasthop\n";

					if (isset($uplinks[ $backbonesip ]))
					{
						if (! isset($uplinks[ $backbonesip ][ "upls" ])) $uplinks[ $backbonesip ][ "upls" ] = array();
						$uplinks[ $backbonesip ][ "upls" ][ $lasthop ] = $cnets[ $cnetip ][ "loc" ];
					}
					else
					{
						if (! isset($backbones[ $backbonesip ])) $backbones[ $backbonesip ] = array();
						$backbones[ $backbonesip ][ $lasthop ] = $cnets[ $cnetip ][ "loc" ];
					}
					
					break;
				}
				
				continue;
			}
			
			if ($stage == 2)
			{
				$lastbone = null;
				$lastloc  = null;

				while (count($path))
				{
					$backbonesip = IPZero(array_pop($path));

					if (isset($uplinks[ $backbonesip ]))
					{
						if (($lastbone != null) && isset($uplinks[ $lastbone ]))
						{
							if (! isset($uplinks[ $backbonesip ][ "upls" ])) $uplinks[ $backbonesip ][ "upls" ] = array();
							$uplinks[ $backbonesip ][ "upls" ][ $lastbone ] = $lastloc;
						}
						
						foreach ($uplinks[ $backbonesip ][ "epls" ] as $dummy => $rloc) 
						{
				 			$lastbone = $backbonesip;
							$lastloc  = $rloc;
							
							break;
						}
						
						continue;
					}
					
				 	if (isset($allbones[ $backbonesip ]))
				 	{
						if ($lastbone != null)
						{
							$backbones[ $backbonesip ][ $lastbone ] = $lastloc;
						}
						
				 		$lastbone = $backbonesip;
						$lastloc  = $allbones[ $backbonesip ][ "loc" ];
						
						continue;
				 	}
				 	
					if ($backbonesip == $lastbone) continue;		
					if (ResolveISP($backbonesip) != $isp) break;		
				
					if ($lastbone != null)
					{
						if (! isset($backbones[ $backbonesip ])) $backbones[ $backbonesip ] = array();
						$backbones[ $backbonesip ][ $lastbone ] = $lastloc;
					}
				
					$lastbone = $backbonesip;
					$lastloc  = isset($GLOBALS[ "locations" ][ $backbonesip ]) ? $GLOBALS[ "locations" ][ $backbonesip ] : "n.n.";
				}
				
				continue;
			}
		}
	}
	
	closedir($dfd);

	ksort($backbones);
	
	foreach ($backbones as $bbip => $dummy)
	{
		ksort($backbones[ $bbip ]);
	}
	
	if ($stage == 0) return $backbones;
	
	foreach ($backbones as $bbip => $bblist)
	{
		$thisloc = GetDifferentLocations($bblist);
		$thisloc = (count($thisloc) != 1) ? "n.n." : $thisloc[ 0 ];

		if (isset($allbones[ $bbip ]))
		{
			$backbone = $allbones[ $bbip ];
		}
		else
		{
			$backbone = array();
			$backbone[ "lev"  ] = $stage;
			$backbone[ "typ"  ] = 1;
			$backbone[ "loc"  ] = $thisloc;
			$backbone[ "name" ] = GetHostByAddress($bbip,$isp,"buildmap");
		}
		
		if (! isset($backbone[ "upls" ])) $backbone[ "upls" ] = array();
		if (! isset($backbone[ "bbls" ])) $backbone[ "bbls" ] = array();
		
		foreach ($bblist as $rip => $rloc)
		{
			if (isset($uplinks[ $rip ]))
			{
				$backbone[ "upls" ][ $rip ] = $rloc;
			}
			else
			{
				$backbone[ "bbls" ][ $rip ] = $rloc;
			}
		}
	
		if (count($backbone[ "bbls" ]) &&   count($backbone[ "upls" ])) $backbone[ "typ"  ] = 2;
		if (count($backbone[ "bbls" ]) && ! count($backbone[ "upls" ])) $backbone[ "typ"  ] = 3;
	
		if (! count($backbone[ "upls" ])) unset($backbone[ "upls" ]);
		if (! count($backbone[ "bbls" ])) unset($backbone[ "bbls" ]);
	
		$allbones[ $bbip ] = $backbone;
	}
			
	ksort($allbones);
	ksort($backbones);

	return $backbones;
}

	//
	// Get a list of existent subnets.
	//
	
	$oldsubnets = array();
	
	$dfd = opendir("../var/$isp/subnets");
	
	while (($file = readdir($dfd)) !== false)
	{
		if (substr($file,0,1) == ".") continue;
		
		$oldsubnets[ "../var/$isp/subnets/$file" ] = true;
	}
	
	closedir($dfd);
	
	$oldendping = array();
	
	$dfd = opendir("../var/$isp/endping");
	
	while (($file = readdir($dfd)) !== false)
	{
		if (substr($file,0,1) == ".") continue;
		
		$oldendping[ "../var/$isp/endping/$file" ] = true;
	}
	
	closedir($dfd);
	
	$oldeplping = array();
	
	if (file_exists("../var/$isp/eplping"))
	{
		$dfd = opendir("../var/$isp/eplping");
	
		while (($file = readdir($dfd)) !== false)
		{
			if (substr($file,0,1) == ".") continue;
		
			$oldeplping[ "../var/$isp/eplping/$file" ] = true;
		}
	
		closedir($dfd);
	}
	
	$olduplping = array();
	
	if (file_exists("../var/$isp/uplping"))
	{
		$dfd = opendir("../var/$isp/uplping");
	
		while (($file = readdir($dfd)) !== false)
		{
			if (substr($file,0,1) == ".") continue;
		
			$olduplping[ "../var/$isp/uplping/$file" ] = true;
		}
	
		closedir($dfd);
	}
	
	$oldbblping = array();
	
	if (file_exists("../var/$isp/bblping"))
	{
		$dfd = opendir("../var/$isp/bblping");
	
		while (($file = readdir($dfd)) !== false)
		{
			if (substr($file,0,1) == ".") continue;
		
			$oldbblping[ "../var/$isp/bblping/$file" ] = true;
		}
	
		closedir($dfd);
	}
	
	//
	// Read manual router locations if required.
	//
	
	$locations = array();
	$locationsfile = "../var/$isp/configs/location.json";
	
	if (file_exists($locationsfile))
	{
		$locations = json_decdat(file_get_contents($locationsfile));
		
		if ($locations === false)
		{
			echo "$locations fucked up, exit...\n";
			exit();
		}
	}
	
	$endpoint = Array();
	$downlink = Array();
	$deadnets = Array();
	$gateways = Array();
	$eplinks  = Array();
	
	$bonuscities = array();
	$bonusnailed = array();
	
	$bonusnailed[ "xxxxx"  				] =  "xxxxxxxxx";
	$bonusnailed[ "xxxxx"  				] =  "xxxxxxxxx";
	$bonusnailed[ "xxxxx"  				] =  "xxxxxxxxx";
	$bonusnailed[ "Stuttgart"  			] =  "DE,Baden-Württemberg,Stuttgart,48.7667,9.1833";
	$bonusnailed[ "Gütersloh"  			] =  "DE,Nordrhein-Westfalen,Gütersloh,51.9,8.3833";
	$bonusnailed[ "Jena"  				] =  "DE,Thüringen,Jena,50.9333,11.5833";
	$bonusnailed[ "Günzburg"  			] =  "DE,Bayern,Günzburg,48.45,10.2667";
	$bonusnailed[ "Amberg"  			] =  "DE,Bayern,Amberg,49.4502,11.848";
	$bonusnailed[ "Weiden"  			] =  "DE,Bayern,Weiden,49.7,12.2333";
	$bonusnailed[ "Bautzen"  			] =  "DE,Sachsen,Bautzen,51.1833,14.4333";
	$bonusnailed[ "Rennerod"  			] =  "DE,Rheinland-Pfalz,Rennerod,50.6167,8.0667";
	$bonusnailed[ "Lindau"  			] =  "DE,Bayern,Lindau,47.55,9.6833";
	$bonusnailed[ "Marktredwitz" 		] =  "DE,Bayern,Marktredwitz,50.0103,12.1008";
	$bonusnailed[ "Rosenheim"  			] =  "DE,Bayern,Rosenheim,47.85,12.1333";
	$bonusnailed[ "Memmingen"  			] =  "DE,Bayern,Memmingen,47.9826,10.1725";
	$bonusnailed[ "Dachau" 				] =  "DE,Bayern,Dachau,48.2667,11.4333";
	$bonusnailed[ "Erding"  			] =  "DE,Bayern,Erding,48.3001,11.9082";
	$bonusnailed[ "Mayen"  				] =  "DE,Rheinland-Pfalz,Mayen,50.3333,7.2167";
	$bonusnailed[ "Diez"  				] =  "DE,Rheinland-Pfalz,Diez,50.3667,8.0167";
	$bonusnailed[ "Blieskastel" 		] =  "DE,Saarland,Blieskastel,49.2333,7.25";
	$bonusnailed[ "Aschaffenburg"  		] =  "DE,Bayern,Aschaffenburg,49.9739,9.1492";
	$bonusnailed[ "Kempten"  			] =  "DE,Bayern,Kempten,47.7167,10.3167";
	$bonusnailed[ "Landshut"  			] =  "DE,Bayern,Landshut,48.5333,12.15";
	$bonusnailed[ "Dingolfing"  		] =  "DE,Bayern,Dingolfing,48.6381,12.4907";
	$bonusnailed[ "Miesbach"  			] =  "DE,Bayern,Miesbach,47.7833,11.8333";
	$bonusnailed[ "Bad Reichenhall" 	] =  "DE,Bayern,Bad Reichenhall,47.7333,12.8833";
	$bonusnailed[ "Mühldorf"  			] =  "DE,Bayern,Mühldorf,48.25,12.5333";
	$bonusnailed[ "Deggendorf" 			] =  "DE,Bayern,Deggendorf,48.8333,12.9667";
	$bonusnailed[ "Regensburg" 			] =  "DE,Bayern,Regensburg,49.015,12.0956";
	$bonusnailed[ "Ingolstadt"  		] =  "DE,Bayern,Ingolstadt,48.7667,11.4333";
	$bonusnailed[ "Donauwörth" 			] =  "DE,Bayern,Donauwörth,48.7291,10.7687";
	$bonusnailed[ "Erlangen"  			] =  "DE,Bayern,Erlangen,49.5897,11.0039";
	$bonusnailed[ "Nürnberg"  			] =  "DE,Bayern,Nürnberg,49.4478,11.0683";
	$bonusnailed[ "Hof"  				] =  "DE,Bayern,Hof,50.3167,11.9167";
	$bonusnailed[ "Kronach"  			] =  "DE,Bayern,Kronach,50.2333,11.3167";
	$bonusnailed[ "Bamberg"  			] =  "DE,Bayern,Bamberg,49.91,10.89";
	$bonusnailed[ "Schweinfurt"  		] =  "DE,Bayern,Schweinfurt,50.05,10.2333";
	$bonusnailed[ "Bad Kissingen"  		] =  "DE,Bayern,Bad Kissingen,50.2,10.0833";
	$bonusnailed[ "Cuxhaven"  			] =  "DE,Niedersachsen,Cuxhaven,53.8555,8.6773";
	$bonusnailed[ "Kulmbach"  			] =  "DE,Bayern,Kulmbach,50.1053,11.4426";
	$bonusnailed[ "Altötting"  			] =  "DE,Bayern,Altötting,48.2333,12.6833";
	$bonusnailed[ "Freising"  			] =  "DE,Bayern,Freising,48.4,11.7333";
	$bonusnailed[ "Hachenburg"  		] =  "DE,Rheinland-Pfalz,Hachenburg,50.65,7.8333";
	$bonusnailed[ "Wittlich"  			] =  "DE,Rheinland-Pfalz,Wittlich,49.9833,6.8833";
	$bonusnailed[ "Kaiserslautern" 		] =  "DE,Rheinland-Pfalz,Kaiserslautern,49.45,7.75";
	$bonusnailed[ "Coburg"  			] =  "DE,Bayern,Coburg,50.25,10.9667";
	$bonusnailed[ "Husum"  				] =  "DE,Schleswig-Holstein,Husum,54.4667,9.05";
	$bonusnailed[ "Kaufbeuren" 			] =  "DE,Bayern,Kaufbeuren,47.8943,10.6319";
	$bonusnailed[ "Leipzig"  			] =  "DE,Sachsen,Leipzig,51.3,12.3333";
	$bonusnailed[ "Ingelheim"  			] =  "DE,Rheinland-Pfalz,Ingelheim,49.974,8.051";
	$bonusnailed[ "Speyer"  			] =  "DE,Rheinland-Pfalz,Speyer,49.3297,8.4278";
	$bonusnailed[ "Germersheim"  		] =  "DE,Rheinland-Pfalz,Germersheim,49.2231,8.3639";
	$bonusnailed[ "Alzey"  				] =  "DE,Rheinland-Pfalz,Alzey,49.7517,8.1161";
	$bonusnailed[ "Mainz"  				] =  "DE,Rheinland-Pfalz,Mainz,50,8.2711";
	$bonusnailed[ "Bingen"  			] =  "DE,Rheinland-Pfalz,Bingen,49.9596,7.9194";
	$bonusnailed[ "Simmern"  			] =  "DE,Rheinland-Pfalz,Simmern,49.9833,7.5167";
	$bonusnailed[ "Pirmasens"  			] =  "DE,Rheinland-Pfalz,Pirmasens,49.2,7.6";
	$bonusnailed[ "Saarlouis"  			] =  "DE,Saarland,Saarlouis,49.3167,6.75";
	$bonusnailed[ "Landstuhl"  			] =  "DE,Rheinland-Pfalz,Landstuhl,49.4167,7.5667";
	$bonusnailed[ "Landau"  			] =  "DE,Rheinland-Pfalz,Landau,49.1958,8.0286";
	$bonusnailed[ "Ludwigshafen"		] =  "DE,Rheinland-Pfalz,Ludwigshafen,49.4811,8.4353";
	$bonusnailed[ "Worms"  				] =  "DE,Rheinland-Pfalz,Worms,49.6356,8.3597";
	$bonusnailed[ "Dresden"  			] =  "DE,Sachsen,Dresden,51.05,13.75";
	$bonusnailed[ "Gera"  				] =  "DE,Thüringen,Gera,50.8667,12.0833";
	$bonusnailed[ "Rückeroth"  			] =  "DE,Rheinland-Pfalz,Rückeroth,50.5667,7.75";
	$bonusnailed[ "Neuwied"  			] =  "DE,Rheinland-Pfalz,Neuwied,50.4333,7.4667";
	$bonusnailed[ "Sinzig"  			] =  "DE,Rheinland-Pfalz,Sinzig,50.55,7.25";
	$bonusnailed[ "Saarbrücken"			] =  "DE,Saarland,Saarbrücken,49.2333,7";
	$bonusnailed[ "Neunkirchen"  		] =  "DE,Saarland,Neunkirchen,49.35,7.1833";
	$bonusnailed[ "Norden"  			] =  "DE,Niedersachsen,Norden,53.6,7.2";
	$bonusnailed[ "Salzgitter"  		] =  "DE,Niedersachsen,Salzgitter,52.0833,10.3333";
	$bonusnailed[ "Holzminden"  		] =  "DE,Niedersachsen,Holzminden,51.8197,9.4646";
	$bonusnailed[ "Herzberg Am Harz"  	] =  "DE,Niedersachsen,Herzberg Am Harz,51.65,10.3333";
	$bonusnailed[ "Göttingen"  			] =  "DE,Niedersachsen,Göttingen,51.5333,9.9333";
	$bonusnailed[ "Wunstorf"  			] =  "DE,Niedersachsen,Wunstorf,52.4333,9.4167";
	$bonusnailed[ "Bückeburg"  			] =  "DE,Niedersachsen,Bückeburg,52.2667,9.05";
	$bonusnailed[ "Osnabrück"  			] =  "DE,Niedersachsen,Osnabrück,52.2667,8.05";
	$bonusnailed[ "Cloppenburg" 		] =  "DE,Niedersachsen,Cloppenburg,52.8475,8.0462";
	$bonusnailed[ "Leer"  				] =  "DE,Niedersachsen,Leer,53.2333,7.4333";
	$bonusnailed[ "Bremerhaven"  		] =  "DE,Bremen,Bremerhaven,53.55,8.5833";
	$bonusnailed[ "Bremen"  			] =  "DE,Bremen,Bremen,53.0833,8.8";
	$bonusnailed[ "Hannover"  			] =  "DE,Niedersachsen,Hannover,52.3667,9.7167";
	$bonusnailed[ "Hildesheim"  		] =  "DE,Niedersachsen,Hildesheim,52.15,9.9667";
	$bonusnailed[ "Peine"  				] =  "DE,Niedersachsen,Peine,52.3167,10.2333";
	$bonusnailed[ "Wolfsburg"  			] =  "DE,Niedersachsen,Wolfsburg,52.4333,10.8";
	$bonusnailed[ "Helmstedt"  			] =  "DE,Niedersachsen,Helmstedt,52.2316,11.0018";
	$bonusnailed[ "Weilheim"  			] =  "DE,Bayern,Weilheim,47.8376,11.1489";
	$bonusnailed[ "München"  			] =  "DE,Bayern,München,48.15,11.5833";
	$bonusnailed[ "Heide"  				] =  "DE,Schleswig-Holstein,Heide,54.2,9.1";
	$bonusnailed[ "Einbeck"  			] =  "DE,Niedersachsen,Einbeck,51.8167,9.8667";
	$bonusnailed[ "Berlin"  			] =  "DE,Berlin,Berlin,52.5167,13.4";
	$bonusnailed[ "Hagenow"  			] =  "DE,Mecklenburg-Vorpommern,Hagenow,53.4333,11.1833";
	$bonusnailed[ "Mölln"  				] =  "DE,Schleswig-Holstein,Mölln,53.6202,10.6773";
	$bonusnailed[ "Geesthacht"  		] =  "DE,Schleswig-Holstein,Geesthacht,53.4333,10.3667";
	$bonusnailed[ "Rostock"  			] =  "DE,Mecklenburg-Vorpommern,Rostock,54.0833,12.1333";
	$bonusnailed[ "Seevetal"  			] =  "DE,Niedersachsen,Seevetal,53.4,9.9667";
	$bonusnailed[ "Buxtehude"  			] =  "DE,Niedersachsen,Buxtehude,53.466,9.6912";
	$bonusnailed[ "Flensburg"  			] =  "DE,Schleswig-Holstein,Flensburg,54.7833,9.4333";
	$bonusnailed[ "Rendsburg"  			] =  "DE,Schleswig-Holstein,Rendsburg,54.3024,9.6625";
	$bonusnailed[ "Kiel"  				] =  "DE,Schleswig-Holstein,Kiel,54.3333,10.1333";
	$bonusnailed[ "Itzehoe"  			] =  "DE,Schleswig-Holstein,Itzehoe,53.9167,9.5167";
	$bonusnailed[ "Neumünster"  		] =  "DE,Schleswig-Holstein,Neumünster,54.0667,9.9833";
	$bonusnailed[ "Hamburg"  			] =  "DE,Hamburg,Hamburg,53.55,10";
	$bonusnailed[ "Ahrensburg"  		] =  "DE,Schleswig-Holstein,Ahrensburg,53.6833,10.25";
	$bonusnailed[ "Pinneberg"  			] =  "DE,Schleswig-Holstein,Pinneberg,53.65,9.8";
	$bonusnailed[ "Norderstedt" 		] =  "DE,Schleswig-Holstein,Norderstedt,53.7,10.0167";
	$bonusnailed[ "Lüneburg" 			] =  "DE,Niedersachsen,Lüneburg,53.25,10.4";
	$bonusnailed[ "Soltau" 				] =  "DE,Niedersachsen,Soltau,52.9833,9.8333";
	$bonusnailed[ "Verden" 				] =  "DE,Niedersachsen,Verden,52.9167,9.2333";
	$bonusnailed[ "Walsrode" 			] =  "DE,Niedersachsen,Walsrode,52.8592,9.5852";
	$bonusnailed[ "Hameln" 			 	] =  "DE,Niedersachsen,Hameln,52.1,9.35";
	$bonusnailed[ "Pfaffenhofen an der Ilm" 	] =  "DE,Bayern,Pfaffenhofen an der Ilm,48.525,11.491";
	$bonusnailed[ "Neustadt An Der Weinstraße"	] =  "DE,Rheinland-Pfalz,Neustadt An Der Weinstraße,49.3567,8.1378";
	
	$bonuscities[ "xxxxxx" 			 	] = 20;
	$bonuscities[ "xxxxxx" 			 	] = 20;
	$bonuscities[ "xxxxxx" 			 	] = 20;
	$bonuscities[ "xxxxxx" 			 	] = 20;
	$bonuscities[ "xxxxxx" 			 	] = 20;
	$bonuscities[ "xxxxxx" 			 	] = 20;
	$bonuscities[ "Aachen" 			 	] = 20;
	$bonuscities[ "Neuss" 			 	] = 20;
	$bonuscities[ "Lingen" 			 	] = 20;
	$bonuscities[ "Karlsruhe" 		 	] = 20;
	$bonuscities[ "Meschede" 		 	] = 20;
	$bonuscities[ "Halle" 			 	] = 20;
	$bonuscities[ "Greifswald" 		 	] = 20;
	$bonuscities[ "Rostock" 		 	] = 20;
	$bonuscities[ "Bochum" 			 	] = 20;
	$bonuscities[ "Göttingen" 		 	] = 20;
	$bonuscities[ "Krefeld" 		 	] = 20;
	$bonuscities[ "Duisburg" 		 	] = 20;
	$bonuscities[ "Bonn" 		 	 	] = 20;
	$bonuscities[ "Wesel" 		 	 	] = 20;
	$bonuscities[ "Düsseldorf" 	 	 	] = 20;
	$bonuscities[ "Koblenz" 	 	 	] = 99;
	$bonuscities[ "Wuppertal" 	 	 	] = 20;
	$bonuscities[ "Köln" 	 		 	] = 20;
	$bonuscities[ "Trier" 	 		 	] = 20;
	$bonuscities[ "Erfurt"  		 	] = 20;
	$bonuscities[ "Cottbus"  		 	] = 20;
	$bonuscities[ "Saarbrücken"  	 	] = 20;
	$bonuscities[ "Paderborn"  	 		] = 20;
	$bonuscities[ "Traunstein"  	 	] = 20;
	$bonuscities[ "Kempten"  	 		] = 20;
	$bonuscities[ "Rottweil"  	 		] = 20;
	$bonuscities[ "Offenburg"  	 		] = 20;
	$bonuscities[ "Konstanz"  	 		] = 20;
	$bonuscities[ "Passau"  	 		] = 20;
	$bonuscities[ "Oldenburg"   		] = 20;
	$bonuscities[ "Fulda"   			] = 20;
	$bonuscities[ "Schwerin"   			] = 20;
	$bonuscities[ "Mannheim"   			] = 20;
	$bonuscities[ "Regensburg"   		] = 20;
	$bonuscities[ "Chemnitz"   			] = 20;
	$bonuscities[ "Ulm"   				] = 20;
	$bonuscities[ "Bautzen"   			] = 20;
	$bonuscities[ "Leer"   				] = 20;
	$bonuscities[ "Hamburg"   			] = 20;
	$bonuscities[ "Osnabrück" 			] = 20;
	$bonuscities[ "Stuttgart"    		] = 20;
	$bonuscities[ "München"  	  		] = 20;
	$bonuscities[ "Kiel"  	 	 		] = 20;
	$bonuscities[ "Flensburg"  	  		] = 20;
	$bonuscities[ "Berlin"  	  		] = 20;
	$bonuscities[ "Dresden"  	  		] = 20;
	$bonuscities[ "Lübeck"    			] = 20;
	$bonuscities[ "Neubrandenburg"		] = 20;
	$bonuscities[ "Brandenburg"			] = 20;
	$bonuscities[ "Saabrücken"			] = 20;
	$bonuscities[ "Siegen"				] = 20;
	$bonuscities[ "Kaiserslautern"		] = 20;
	$bonuscities[ "Essen"				] = 20;
	$bonuscities[ "Dortmund"			] = 20;
	$bonuscities[ "Bremen"				] = 20;
	$bonuscities[ "Hannover"			] = 20;
	$bonuscities[ "Freiburg"			] = 20;
	$bonuscities[ "Nürnberg"			] = 20;
	$bonuscities[ "Augsburg"			] = 20;
	$bonuscities[ "Leipzig"				] = 20;
	$bonuscities[ "Bayreuth"			] = 20;
	$bonuscities[ "Erfurt"				] = 20;
	$bonuscities[ "Bremerhaven"			] = 20;
	$bonuscities[ "Bielefeld"			] = 20;
	$bonuscities[ "Braunschweig"		] = 20;
	$bonuscities[ "Magdeburg"			] = 20;
	$bonuscities[ "Frankfurt Am Main"	] = 20;
	$bonuscities[ "Darmstadt"			] = 20;
	$bonuscities[ "Gießen"				] = 20;
	$bonuscities[ "Mainz"				] = 20;
	$bonuscities[ "Würzburg"			] = 20;
	$bonuscities[ "Münster"				] = 20;
	$bonuscities[ "Heilbronn"			] = 20;
	$bonuscities[ "Frankfurt An Der Oder" 	] = 20;
	
	foreach ($tobuilds as $dummy => $tobuild)
	{
		$lines = false;
		
		if ($lines === false) $lines = @file("../var/$isp/netmaps/$tobuild.tune.log");
		if ($lines === false) $lines = @file("../var/$isp/netmaps/$tobuild.orig.log");
	
		$map     = Array();
		$subnet  = Array();
		$lastip  = null;
		$lastloc = null;
		$lastvia = null;
	
		for ($inx = 0; $inx < count($lines); $inx++)
		{
			$line = trim($lines[ $inx ]);
		
			if (! strlen($line)) continue;
		
			if (substr($line,0,1) == "#") continue;
		
			if ((substr($line,0,4) == "----") ||
				(substr($line,0,4) == "===="))
			{
				if ((isset($subnet[ "gw" ]) && ($subnet[ "gw" ] !== null)) ||
					(isset($subnet[ "bc" ]) && ($subnet[ "bc" ] !== null)))
				{	
					if (isset($subnet[ "ip" ])) $subnet[ "ip" ] = Bin_IPZero($subnet[ "ip" ]);
					if (isset($subnet[ "bc" ])) $subnet[ "bc" ] = Bin_IPZero($subnet[ "bc" ]);
					if (isset($subnet[ "gw" ])) $subnet[ "gw" ] = Bin_IPZero($subnet[ "gw" ]);

					if (isset($subnet[ "gw" ]) && ($subnet[ "gw" ] === null))
					{
						unset($subnet[ "gw" ]);
					}
					
					$seg = Array();
	
					$seg[ "from" ] = Bin_IPZero($lastip);
					$seg[ "last" ] = $subnet[ "bc" ];
					$seg[ "pc"   ] = 0;
					$seg[ "loc"  ] = $loc;
					
					if ($lastvia != null) $seg[ "via"  ] = $lastvia;
					
					array_push($subnet[ "segs" ],$seg);
									
					$dls = Array();
					$bestlocalpos = Array();
					
					foreach ($subnet[ "dls" ] as $dl)
					{
						$routerip = Bin_IPZero($dl);
						array_push($dls,$routerip);
						
						if (! isset($downlink[ $routerip ])) $downlink[ $routerip ] = array();
						
						foreach ($subnet[ "segs" ] as $seg)
						{
							$segloc = $seg[ "loc" ][ "country" ]
								 	. ","
								 	. $seg[ "loc" ][ "region" ]
								 	. ","
								 	. $seg[ "loc" ][ "city" ]
								 	. ","
								 	. $seg[ "loc" ][ "lat" ]
								 	. ","
								 	. $seg[ "loc" ][ "lon" ]
								 	;
														
							//
							// Compute net segment locations.
							//
							
							if (! isset($bestlocalpos[ $segloc ]))
							{
								$bestlocalpos[ $segloc ] = 0;
							}
							
							if (isset($bonuscities[ $seg[ "loc" ][ "city" ] ]))
							{
								$bestlocalpos[ $segloc ] += $bonuscities[ $seg[ "loc" ][ "city" ] ];
							}
							else
							{
								if (isset($bonusnailed[ $seg[ "loc" ][ "city" ] ]))
								{
									$bestlocalpos[ $segloc ] += 5;
								}
								else
								{
									$bestlocalpos[ $segloc ]++;
								}
							}
							
							//
							// Store global router locations.
							//
							
							$downlink[ $routerip ][ $seg[ "from" ] . ":" . $segloc ] = 0;

							if (! isset($downlink[ $routerip ][ $segloc ]))
							{
								$downlink[ $routerip ][ $segloc ] = 0;
							}
							
							if (isset($bonuscities[ $seg[ "loc" ][ "city" ] ]))
							{
								$downlink[ $routerip ][ $segloc ] += $bonuscities[ $seg[ "loc" ][ "city" ] ];
							}
							else
							{
								$downlink[ $routerip ][ $segloc ]++;
							}
				 		}
					}
										
					$subnet[ "dls" ] = $dls;

					if ($subnet[ "loc" ])
					{
						//
						// Via location...
						//
					}
					else
					{
						arsort($bestlocalpos);
							
						foreach ($bestlocalpos as $rloc => $dummy)
						{
							$parts = explode(",",$rloc);
		
							$bestloc = Array();
		
							$bestloc[ "country" ] = $parts[ 0 ];
							$bestloc[ "region"  ] = Fix_Region($parts[ 1 ]);
							$bestloc[ "city"    ] = $parts[ 2 ];
							$bestloc[ "lat"     ] = floatval($parts[ 3 ]);
							$bestloc[ "lon"     ] = floatval($parts[ 4 ]);
						
							//
							// Re-check for nailed cities.
							//
							
							if (isset($bonusnailed[ $bestloc[ "city" ] ]))
							{
								$parts = explode(",",$bonusnailed[ $bestloc[ "city" ] ]);
		
								$bestloc[ "country" ] = $parts[ 0 ];
								$bestloc[ "region"  ] = Fix_Region($parts[ 1 ]);
								$bestloc[ "city"    ] = $parts[ 2 ];
								$bestloc[ "lat"     ] = floatval($parts[ 3 ]);
								$bestloc[ "lon"     ] = floatval($parts[ 4 ]);
							}
						
							$subnet[ "loc" ] = $bestloc;
						
							break;
						}
					}

					//
					// We have a new subnet identified.
					//
					
					if ($subnet[ "gw" ] == null) unset($subnet[ "gw" ]);
						
					if (count($subnet[ "dls" ]) && ! $bogus) 
					{
						//
						// Update gateway locations
						//
				
						$gateloc = $subnet[ "loc" ][ "country" ]
								 . ","
								 . $subnet[ "loc" ][ "region" ]
								 . ","
								 . $subnet[ "loc" ][ "city" ]
								 . ","
								 . $subnet[ "loc" ][ "lat" ]
								 . ","
								 . $subnet[ "loc" ][ "lon" ]
								;

						foreach ($subnet[ "dls" ] as $dl)
						{
							if (! isset($gateways[ $dl ])) 
							{
								$gateways[ $dl ] = array();
								$gateways[ $dl ][ "epls" ] = array();
							}
							
							$gateways[ $dl ][ "epls" ][ $subnet[ "ip" ] ] = $gateloc;
						}
				
						//
						// Derive count of pings in segments from netpings.
						//
						
						foreach ($subnet[ "segs" ] as $sinx => $seg)
						{
							$from = IP_Bin($seg[ "from" ]);
							$last = IP_Bin($seg[ "last" ]);
						
							for ($netip = $from; $netip < $last; $netip += 256)
							{
								$netpingsfile = "../var/$isp/netping/" . Bin_IPZero($netip) . ".ping.json";
								$netpingsjson = @file_get_contents($netpingsfile);
								if ($netpingsjson === false) continue;
								$netpingsdata = json_decdat($netpingsjson);
							
								$subnet[ "segs" ][ $sinx ][ "pc" ] += $netpingsdata[ "nodes" ];
							}
							
							$subnet[ "pc" ] += $subnet[ "segs" ][ $sinx ][ "pc" ];							
						}
						
						if (isset($subnet[ "gw" ])) $eplinks[ $subnet[ "gw" ] ] = true;
						
						//
						// Push into routed networks.
						//
						
						array_push($map,$subnet);
					}
					else
					{
						//
						// Push into unrouted networks.
						//
						
						$deadnets[ $subnet[ "ip" ] . "-" . $subnet[ "bc" ] ] = "unrouted";
					}
					
					$subnet  = Array();
					$lastip  = null;
					$lastloc = null;
					$lastvia = null;
				}
			
				continue;
			}
		
			if (substr($line,15,1) == "~") continue;
			if (substr($line,15,1) == "#") continue;
			if (substr($line,15,1) == "!") continue;
			
			if (substr($line,15,1) == "@") 
			{
				$newdl = IP_Bin(substr($line,0,-1));
			
				foreach ($subnet[ "dls" ] as $dl)
				{
					if ($dl == $newdl)
					{
						$newdl = -1;
						break;
					}
				}
			
				if ($newdl != -1) 
				{
					array_push($subnet[ "dls" ],$newdl);
					asort($subnet[ "dls" ]);
				}
			
				continue;
			}
		
			if (strpos($line,"=") === false) continue;
		
			$parts  = explode("=",$line);
			$actip  = IP_Bin($parts[ 0 ]);
			$rtype  = substr($parts[ 1 ],0,1);
			$rest   = trim(substr($parts[ 1 ],1));
			
			$router = ((strlen($rest) > 0) && (substr($rest,0,1) == "+"));
			$bogus  = ((strlen($rest) > 0) && (substr($rest,0,1) == "^"));
			
			$bcast  = ((strlen($rest) > 0) && 
					   ((substr($rest,0,1) == "*") || 
						(substr($rest,0,1) == "?") || 
						(substr($rest,0,1) == "^")));
			
			if (! isset($subnet[ "ip" ]))
			{
				$subnet[ "ip"   ] = $actip;
				$subnet[ "bc"   ] = null;
				$subnet[ "gw"   ] = null;
				$subnet[ "nc"   ] = 0;
				$subnet[ "pc"   ] = 0;
				$subnet[ "loc"  ] = null;
				$subnet[ "dls"  ] = Array();
				$subnet[ "segs" ] = Array();
			}
		
			if (($actip % 256) == 0) $subnet[ "nc" ]++;
		
			if (strpos($rest,",") > 0)
			{
				$ismaster = false;
			
				if (substr($rest,-1) == "+")
				{
					$ismaster = true;
					$rest = substr($rest,0,-1);
				}
			
				if ($lastip != null)
				{	
					if ($rest != $lastloc)
					{
						$seg = Array();
		
						$seg[ "from" ] = Bin_IPZero($lastip);
						$seg[ "last" ] = ($actip == $lastip) ? Bin_IPZero($actip) : Bin_IPZero($actip - 1);
						$seg[ "pc"   ] = 0;
						
						if ($seg[ "from" ] == $seg[ "last" ])
						{
							$subnet[ "loc" ] = $lastvia = $loc;
						}
						else
						{
							$seg[ "loc" ] = $loc;

							if ($lastvia != null)
							{
								if (($lastvia[ "lat" ] != $subnet[ "loc" ][ "lat" ]) ||
									($lastvia[ "lon" ] != $subnet[ "loc" ][ "lon" ]))
								{
									$seg[ "via" ] = $lastvia;
								}
														
								$lastvia = null;
							}

							array_push($subnet[ "segs" ],$seg);
						}
					}
				}
		
				if ($lastloc != $rest)
				{
					$lastloc = $rest;
					$lastip  = $actip;
				}
			
				$parts = explode(",",$rest);
			
				$loc = Array();
			
				$loc[ "country" ] = $parts[ 0 ];
				$loc[ "region"  ] = Fix_Region($parts[ 1 ]);
				$loc[ "city"    ] = Fix_City($parts[ 2 ]);
				$loc[ "lat"     ] = floatval($parts[ 3 ]);
				$loc[ "lon"     ] = floatval($parts[ 4 ]);
			
				if ($ismaster || ! isset($subnet[ "loc" ]))
				{
					//$subnet[ "loc" ] = $loc;
				}
			}
		
			if ($router)
			{
				$subnet[ "bc"  ] = $actip + 1;
				$subnet[ "gw"  ] = $actip;
			}
			
			if ($bcast)
			{
				$subnet[ "bc"  ] = $actip;
			}
		}			
	
		foreach ($map as $minx => $subnet)
		{	
			$gate = Array();
			
			$gate[ "ip" ] = $subnet[ "ip" ];
			$gate[ "bc" ] = $subnet[ "bc" ];
			
			if (isset($subnet[ "gw" ])) $gate[ "gw" ] = $subnet[ "gw" ];
			
			$loc = $subnet[ "loc" ][ "country" ]
				 . ","
				 . $subnet[ "loc" ][ "region" ]
				 . ","
				 . $subnet[ "loc" ][ "city" ]
				 . ","
				 . $subnet[ "loc" ][ "lat" ]
				 . ","
				 . $subnet[ "loc" ][ "lon" ]
				 ;
				 
			$gate[ "loc" ] = $loc;
			
			$gate[ "segs" ] = Array();
			
			$lastfrom = -1;
			
			foreach ($subnet[ "segs" ] as $seg)
			{
				//
				// Check for duplicate segments from
				// route via xyz entries.
				//
				
				if ($seg[ "from" ] == $lastfrom) continue;
				
				array_push($gate[ "segs" ],$seg[ "from" ]);				
				$lastfrom = $seg[ "from" ];
			}
			
			array_push($endpoint,$gate);
			
			$subnetfile = "../var/$isp/subnets/" 
						. $subnet[ "ip" ] 
						. "-" 
						. $subnet[ "bc" ] 
						. ".subnet.json"
						;
						
			$endnetfile = "../var/$isp/endping/" 
						. $subnet[ "ip" ] 
						. ".ping.json"
						;
						
			$eplnetfile = "../var/$isp/eplping/" 
						. (isset($subnet[ "gw" ]) ? $subnet[ "gw" ] : "")
						. ".ping.json"
						;
						
			file_put_contents($subnetfile,json_encdat($subnet) . "\n");
			
			echo "$subnetfile\n";
			
			if (isset($oldsubnets[ $subnetfile ])) unset($oldsubnets[ $subnetfile ]);
			if (isset($oldendping[ $endnetfile ])) unset($oldendping[ $endnetfile ]);
			if (isset($oldeplping[ $eplnetfile ])) unset($oldeplping[ $eplnetfile ]);
		}
		
		$final = "../www/$isp/$tobuild.map";
	
		$json = json_encdat($map) . "\n";
		//file_put_contents($final . ".json",$json);
	
		$json = "kappa.EndpointsCallback(\n" . $json . ");\n";
		file_put_contents($final . ".js",$json);
	}
	
	//
	// Dump all endpoints.
	//
	
	$endpointsfile = "../var/$isp/mapdata/endpoints.json";
	file_put_contents($endpointsfile,json_encdat($endpoint) . "\n");
	
	$deadnetsfile = "../var/$isp/mapdata/deadnets.json";
	file_put_contents($deadnetsfile,json_encdat($deadnets) . "\n");

	//
	// Dump all downlinks and locations.
	//
	
	ksort($downlink);
	
	foreach ($downlink as $routerip => $locs)
	{
		arsort($downlink[ $routerip ]);
	}
	 
	$routerlocfile = "../var/$isp/mapdata/routerloc.json";
	file_put_contents($routerlocfile,json_encdat($downlink) . "\n");
	
	//
	// Generate MTR router information.
	//
	
	$notraces  = array();
	$backbones = array();
	$gateways  = array();
	
	BuildBackbones($isp,$endpoint,$gateways,$backbones,0);
	$notraces = CheckGateways($isp,$gateways);
	
	if (false)
	{	
		echo "BuildBackbones stage 1\n";
		BuildBackbones($isp,$endpoint,$gateways,$backbones,1);
		
		echo "BuildBackbones stage 2\n";
		BuildBackbones($isp,$endpoint,$gateways,$backbones,2);
		
		//
		// Relocated all simple locations recursivly.
		//
		
		echo "Relocate locations stage=1\n";
		
		while (true)
		{
			$modified = false;
			
			foreach ($backbones as $bbip => $bbdata)
			{
				if (isset($bbdata[ "bbls" ]))
				{
					foreach ($bbdata[ "bbls" ] as $bbip2 => $dummy)
					{
						if ($backbones[ $bbip ][ "bbls" ][ $bbip2 ] != "n.n.") continue;
						if ($backbones[ $bbip2 ][ "loc" ] == "n.n.") continue;
					
						$backbones[ $bbip ][ "bbls" ][ $bbip2 ] = $backbones[ $bbip2 ][ "loc" ];
						$modified = true;
					}
				}
				
				if ($backbones[ $bbip ][ "loc" ] != "n.n.") continue;
				
				if (isset($locations[ $bbip ]))
				{
					$backbones[ $bbip ][ "loc" ] = $locations[ $bbip ];
					$modified = true;
					continue;
				}
				
				if (isset($bbdata[ "upls" ]))
				{
					$thisloc = GetDifferentLocations($bbdata[ "upls" ]);
				
					if (count($thisloc) == 1)
					{
						$backbones[ $bbip ][ "loc" ] = $thisloc[ 0 ];
						$modified = true;
						continue;
					}
				}

				if (isset($bbdata[ "bbls" ]))
				{
					$thisloc = GetDifferentLocations($bbdata[ "bbls" ]);
			
					if (count($thisloc) == 1)
					{
						$backbones[ $bbip ][ "loc" ] = $thisloc[ 0 ];
						$modified = true;
						continue;
					}
				}
			}
		
			if (! $modified) break;
		}
		
		//
		// Relocate all unknown locations manually.
		//
		
		echo "Relocate locations stage=2\n";

		while (true)
		{
			$modified = false;
			
			foreach ($backbones as $bbip => $bbdata)
			{
				if (isset($bbdata[ "bbls" ]))
				{
					foreach ($bbdata[ "bbls" ] as $bbip2 => $dummy)
					{
						if ($backbones[ $bbip ][ "bbls" ][ $bbip2 ] != $backbones[ $bbip2 ][ "loc" ])
						{
							$backbones[ $bbip ][ "bbls" ][ $bbip2 ] = $backbones[ $bbip2 ][ "loc" ];
							$modified = true;
						}
					}
				}

				if (! isset($locations[ $bbip ])) continue;	
							
				if ($backbones[ $bbip ][ "loc" ] != "n.n.") 
				{
					if ($backbones[ $bbip ][ "loc" ] != $locations[ $bbip ])
					{
						//echo "Conflict: $bbip " . $backbones[ $bbip ][ "loc" ] . " != " . $locations[ $bbip ] . "\n";
						
						$backbones[ $bbip ][ "loc" ] = $locations[ $bbip ];
						$modified = true;
					}
					
					continue;
				}
				
				//echo "\t\"$bbip\" : \"" . $locations[ $bbip ] . "\",\n";
						
				$backbones[ $bbip ][ "loc" ] = $locations[ $bbip ];
				$modified = true;
			}
		
			if (! $modified) break;
		}
		
		$backbonesfile = "../var/$isp/mapdata/backbones.json";
		$backbonesjson = json_encdat($backbones) . "\n";
		file_put_contents($backbonesfile,$backbonesjson);
	}
	
	$notracesfile = "../var/$isp/mapdata/notraces.json";
	$notracesjson = json_encdat($notraces) . "\n";
	file_put_contents($notracesfile,$notracesjson);
		
	//
	// Make a consistency check if every uplink is
	// routed by a backbone.
	//
	
	$routedgw = array();
	
	foreach ($backbones as $bbip => $bbdata)
	{
		if (! isset($bbdata[ "upls" ])) continue;
		
		foreach ($bbdata[ "upls" ] as $upip => $dummy)
		{
			$routedgw[ $upip ] = true;
		}
	}
	
	foreach ($gateways as $upip => $gwdata)
	{
		if (! isset($gwdata[ "upls" ])) continue;
		
		foreach ($gwdata[ "upls" ] as $upip => $dummy)
		{
			$routedgw[ $upip ] = true;
		}
	}

	foreach ($gateways as $upip => $dummy)
	{
		if ((substr($upip,0,8) != "001.000.") && (ResolveISP($upip) != $isp))
		{
			unset($gateways[ $upip ]);
			continue;
		}
	 
		if (! isset($routedgw[ $upip ])) echo "Unrouted uplink $upip\n";

		$gateways[ $upip ][ "name" ] = GetHostByAddress($upip,$isp,"buildmap");
		
		if (isset($gateways[ $upip ][ "epls" ]))
		{
			$temp = $gateways[ $upip ][ "epls" ];
			unset($gateways[ $upip ][ "epls" ]);
			$gateways[ $upip ][ "epls" ] = $temp;
		}
		
		if (isset($gateways[ $upip ][ "upls" ]))
		{
			$temp = $gateways[ $upip ][ "upls" ];
			unset($gateways[ $upip ][ "upls" ]);
			$gateways[ $upip ][ "upls" ] = $temp;
		}
	}

	$gatewaysfile = "../var/$isp/mapdata/uplinks.json";
	$gatewaysjson = json_encdat($gateways) . "\n";
	file_put_contents($gatewaysfile,$gatewaysjson);
	
	//
	// Make nice uplinks locations map.
	//
	
	echo "Build uplinks map\n";
	
	$uplinksmap = array();
	
	foreach ($gateways as $routerip => $gwdata)
	{
		//
		// Remove from white-list.
		//
		
		$uplpingfile = "../var/$isp/uplping/$routerip.ping.json";
		if (isset($olduplping[ $uplpingfile ])) unset($olduplping[ $uplpingfile ]);

		//
		// Aquire icmp status.
		//
		
		$icmp = 0;
		
		if (file_exists($uplpingfile))
		{
			$icmp = 1;
			
			$pingdata = json_decdat(file_get_contents($uplpingfile));
			
			foreach ($pingdata[ $routerip ] as $stamp => $ms)
			{
				if ($ms != -1) $icmp = 2;
			}
		}
		
		$uplink = array();
		
		$uplink[ "ip"   ] = $routerip;
		$uplink[ "name" ] = GetHostByAddress($routerip,$isp,"buildmap");
		$uplink[ "png"  ] = $icmp;
		$uplink[ "loc"  ] = null;
		$uplink[ "epls" ] = array();
		$uplink[ "upls" ] = array();
		
		//echo "$routerip => " . $uplink[ "name" ] . "\n";
		
		ksort($gwdata[ "epls" ]);
		
		foreach ($gwdata[ "epls" ] as $subnetip => $location)
		{
			array_push($uplink[ "epls" ],$subnetip . ":" . $location);
			if ($uplink[ "loc" ] != null) continue;
			
			$parts = explode(",",$location);
		
			$uplink[ "loc" ] = Array();
		
			$uplink[ "loc" ][ "country" ] = $parts[ 0 ];
			$uplink[ "loc" ][ "region"  ] = Fix_Region($parts[ 1 ]);
			$uplink[ "loc" ][ "city"    ] = Fix_City($parts[ 2 ]);
			$uplink[ "loc" ][ "lat"     ] = floatval($parts[ 3 ]);
			$uplink[ "loc" ][ "lon"     ] = floatval($parts[ 4 ]);
		}
		
		if (isset($gwdata[ "upls" ]))
		{
			foreach ($gwdata[ "upls" ] as $subnetip => $location)
			{
				array_push($uplink[ "upls" ],$subnetip . ":" . $location);
			}
		}
		
		if (count($uplink[ "epls" ]) == 0) unset($uplink[ "epls" ]);
		if (count($uplink[ "upls" ]) == 0) unset($uplink[ "upls" ]);
		
		array_push($uplinksmap,$uplink);
	}
	
	$uplinksmapfile = "../www/$isp/uplinks.map.js";
	$uplinksmapjson = "kappa.UplinksCallback(\n" . json_encdat($uplinksmap) . "\n);\n";
	file_put_contents($uplinksmapfile,$uplinksmapjson);
	
	//
	// Make nice backbones locations map.
	//
	
	echo "Build backbones map\n";
	
	$bbdumps = array();
	
	foreach ($backbones as $bbip => $bbdata)
	{	
		//
		// Remove from white-list.
		//
		
		$bblpingfile = "../var/$isp/bblping/$bbip.ping.json";
		if (isset($oldbblping[ $bblpingfile ])) unset($oldbblping[ $bblpingfile ]);

		//
		// Aquire icmp status.
		//
		
		$icmp = 0;
		
		if (file_exists($bblpingfile))
		{
			$icmp = 1;
			
			$pingdata = json_decdat(file_get_contents($bblpingfile));
			
			foreach ($pingdata[ $bbip ] as $stamp => $ms)
			{
				if ($ms != -1) $icmp = 2;
			}
		}
		
		$bbdump = array();
		
		$bbdump[ "ip"   ] = $bbip;
		$bbdump[ "name" ] = $bbdata[ "name" ];
		$bbdump[ "typ"  ] = $bbdata[ "typ"  ];
		$bbdump[ "png"  ] = $icmp;
		$bbdump[ "loc"  ] = $bbdata[ "loc"  ];
		
		if (isset($bbdata[ "upls" ])) $bbdump[ "upls" ] = $bbdata[ "upls" ];
		if (isset($bbdata[ "bbls" ])) $bbdump[ "bbls" ] = $bbdata[ "bbls" ];
		
		$parts = explode(",",$bbdump[ "loc" ]);
		if (count($parts) != 5) continue;
		
		$bbdump[ "loc" ] = Array();
		$bbdump[ "loc" ][ "country" ] = $parts[ 0 ];
		$bbdump[ "loc" ][ "region"  ] = Fix_Region($parts[ 1 ]);
		$bbdump[ "loc" ][ "city"    ] = Fix_City($parts[ 2 ]);
		$bbdump[ "loc" ][ "lat"     ] = floatval($parts[ 3 ]);
		$bbdump[ "loc" ][ "lon"     ] = floatval($parts[ 4 ]);
		
		array_push($bbdumps,$bbdump);
		
		if (substr($bbip,0,7) == "088.134") 
		{
			//echo "\t\"$bbip\" : \"" . $bbdata[ "loc"  ] . "\",\n";
		}
	}
	
	$backbonesfile = "../www/$isp/backbones.map.js";
	$backbonesjson = "kappa.BackbonesCallback(\n" . json_encdat($bbdumps) . "\n);\n";
	file_put_contents($backbonesfile,$backbonesjson);
	
	//
	// Dump endpoint links.
	//
	
	$eplinksfile = "../var/$isp/mapdata/eplinks.json";
	$eplinksjson = json_encdat($eplinks) . "\n";
	file_put_contents($eplinksfile,$eplinksjson);

	//
	// Check for obsoleted subnet and endping files.
	//
	
	$unlink = true;
	
	ksort($oldsubnets);
	foreach ($oldsubnets as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		if ($unlink) @unlink($file);
	}
	
	ksort($oldendping);
	foreach ($oldendping as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		if ($unlink) @unlink($file);
	}
	
	ksort($oldeplping);
	foreach ($oldeplping as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		if ($unlink) @unlink($file);
	}

	ksort($olduplping);
	foreach ($olduplping as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		if ($unlink) @unlink($file);
	}

	ksort($oldbblping);
	foreach ($oldbblping as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		if ($unlink) @unlink($file);
	}
	
	GetHostByAddressSave($isp,"buildmap");
?>