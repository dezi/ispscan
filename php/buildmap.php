<?php

	include("../php/util.php");
	include("../php/json.php");
	
	$tobuilds = Array();

	/*
	$isp = "de/kd";
	array_push($tobuilds,"024.134.000.000-024.134.255.255");
	array_push($tobuilds,"031.016.000.000-031.019.255.255");
	array_push($tobuilds,"037.004.000.000-037.005.255.255");
	array_push($tobuilds,"077.020.000.000-077.023.255.255");
	array_push($tobuilds,"088.134.000.000-088.134.191.255");
	array_push($tobuilds,"091.064.000.000-091.067.255.255");
    array_push($tobuilds,"095.088.000.000-095.091.255.255");
	array_push($tobuilds,"146.052.000.000-146.052.255.255");
	array_push($tobuilds,"178.024.000.000-178.027.255.255");
	*/
	
	$isp = "de/tk";
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
	
	//
	// Read manual router locations if required.
	//
	
	$locations = array();
	$locationsfile = "../var/$isp/configs/location.json";
	
	if (file_exists($locationsfile))
	{
		$locations = json_decdat(file_get_contents($locationsfile));
	}
	
	$endpoint = Array();
	$downlink = Array();
	$deadnets = Array();
	$gateways = Array();
	
	$bonuscities = array();
	$bonusnailed = array();
	
	$bonusnailed[ "xxxxx"  				] =  "xxxxxxxxx";
	$bonusnailed[ "xxxxx"  				] =  "xxxxxxxxx";
	$bonusnailed[ "xxxxx"  				] =  "xxxxxxxxx";
	$bonusnailed[ "xxxxx"  				] =  "xxxxxxxxx";
	$bonusnailed[ "xxxxx"  				] =  "xxxxxxxxx";
	$bonusnailed[ "xxxxx"  				] =  "xxxxxxxxx";
	$bonusnailed[ "Cuxhaven"  			] =  "DE,Niedersachsen,Cuxhaven,53.8555,8.6773";
	$bonusnailed[ "Kulmbach"  			] =  "DE,Bayern,Kulmbach,50.1053,11.4426";
	$bonusnailed[ "Altötting"  			] =  "DE,Bayern,Altötting,48.2333,12.6833";
	$bonusnailed[ "Freising"  			] =  "DE,Bayern,Freising,48.4,11.7333";
	$bonusnailed[ "Hachenburg"  		] =  "DE,Rheinland-Pfalz,Hachenburg,50.65,7.8333";
	$bonusnailed[ "Wittlich"  			] =  "DE,Rheinland-Pfalz,Wittlich,49.9833,6.8833";
	$bonusnailed[ "Kaiserslautern" 		] =  "DE,Rheinland-Pfalz,Kaiserslautern,49.4467,7.7609";
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
	$bonusnailed[ "Amberg"  			] =  "DE,Bayern,Amberg,49.4414,11.8622";
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
							if (! isset($gateways[ $dl ])) $gateways[ $dl ] = array();
							$gateways[ $dl ][ $subnet[ "ip" ] ] = $gateloc;
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
						
			file_put_contents($subnetfile,json_encdat($subnet) . "\n");
			
			echo "$subnetfile\n";
			
			if (isset($oldsubnets[ $subnetfile ])) unset($oldsubnets[ $subnetfile ]);
			if (isset($oldendping[ $endnetfile ])) unset($oldendping[ $endnetfile ]);
		}
		
		$final = "../www/$isp/$tobuild.map";
	
		$json = json_encdat($map) . "\n";
		file_put_contents($final . ".json",$json);
	
		$json = "kappa.MapCallback(\n" . $json . ");\n";
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
	// Dump all gateways and locations.
	//
	
	ksort($gateways);
	
	$numcities = array();
	
	foreach ($gateways as $routerip => $subnets)
	{
		$number = count(GetDifferentGetCities($subnets));
		
		foreach ($subnets as $subnetip => $dummy)
		{
			if ((! isset($numcities[ $subnetip ])) || ($numcities[ $subnetip ] > $number))
			{
				$numcities[ $subnetip ] = $number;
			}
		}
	}
	
	foreach ($gateways as $routerip => $subnets)
	{
		if (isset($locations[ $routerip ]))
		{
			//
			// Remove undesired gateways.
			//
			
			unset($gateways[ $routerip ]);
			continue;
		}
		
		ksort($gateways[ $routerip ]);
		
		if (substr($routerip,0,7) == "001.000")
		{
			//
			// Move dummy locations at end.
			//
			
			unset($gateways[ $routerip ]);
			
			$gateways[ $routerip ] = $subnets;
		}
		
		if (count(GetDifferentGetCities($subnets)) == 1)
		{
			//
			// Move good locations at end.
			//
			
			unset($gateways[ $routerip ]);
			
			$gateways[ $routerip ] = $subnets;
		}
		else
		{
			//
			// Gateway with multiple locations. Check if each
			// routed subnet has a single location gateway as well.
			// If so, this is a backbone router and not a gateway.
			//
			
			$allsingle = true;
			
			foreach ($subnets as $subnetip => $dummy)
			{
				if ($numcities[ $subnetip ] > 1) 
				{
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
		
			//
			// Remove all single city endpoints from this gateway.
			//
			
			foreach ($subnets as $subnetip => $dummy)
			{
				if ($numcities[ $subnetip ] <= 1) 
				{
					unset($gateways[ $routerip ][ $subnetip ]);
				}
			}			
		}
	}
	
	$gatewaysfile = "../var/$isp/mapdata/uplinks.json";
	$gatewaysjson = json_encdat($gateways) . "\n";
	file_put_contents($gatewaysfile,$gatewaysjson);
	
	//
	// Make nice uplinks locations map.
	//
	
	$uplinks = array();
	
	foreach ($gateways as $routerip => $subnets)
	{
		$uplink = array();
		
		$uplink[ "ip"  ] = $routerip;
		$uplink[ "loc" ] = null;
		$uplink[ "eps" ] = array();
		
		ksort($subnets);
		
		foreach ($subnets as $subnetip => $location)
		{
			array_push($uplink[ "eps" ],$subnetip);
			if ($uplink[ "loc" ] != null) continue;
			
			$parts = explode(",",$location);
		
			$uplink[ "loc" ] = Array();
		
			$uplink[ "loc" ][ "country" ] = $parts[ 0 ];
			$uplink[ "loc" ][ "region"  ] = Fix_Region($parts[ 1 ]);
			$uplink[ "loc" ][ "city"    ] = Fix_City($parts[ 2 ]);
			$uplink[ "loc" ][ "lat"     ] = floatval($parts[ 3 ]);
			$uplink[ "loc" ][ "lon"     ] = floatval($parts[ 4 ]);
		}
		
		array_push($uplinks,$uplink);
	}
	
	$uplinksfile = "../www/$isp/uplinks.map.js";
	$uplinksjson = "kappa.UplinksCallback(\n" . json_encdat($uplinks) . "\n);\n";
	file_put_contents($uplinksfile,$uplinksjson);

	//
	// Check for obsoleted subnet and endping files.
	//
	
	ksort($oldsubnets);
	
	foreach ($oldsubnets as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		@unlink($file);
	}

	
	foreach ($oldendping as $file => $dummy)
	{
		echo "Obsolete: $file\n";
		@unlink($file);
	}

	/*
	$final = "../www/$isp/masters.map";
	
	$json = json_encdat($masters) . "\n";
	file_put_contents($final . ".json",$json);
	
	$json = "kappa.MastersCallback(\n" . $json . ");\n";
	file_put_contents($final . ".js",$json);
	*/
	
	/*
	ksort($routers);

	$final = "../www/$isp/routers.map";
		
	$json = json_encdat($routers) . "\n";
	file_put_contents($final . ".json",$json);
	
	$json = "kappa.RoutersCallback(\n" . $json . ");\n";
	file_put_contents($final . ".js",$json);
	*/
?>