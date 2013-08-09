<?php

include("../php/json.php");
include("../php/misc.php");

$GLOBALS[ "server_port"  ] = 11042;
$GLOBALS[ "transactions" ] = array();

function Logflush()
{
	if (isset($GLOBALS[ "logfd" ])) fflush($GLOBALS[ "logfd" ]);
}

function Logdat($message)
{
	$logfile = "../log/taskserver.log";
	
	if (! isset($GLOBALS[ "logfd" ]))
	{
		if (file_exists($logfile))
		{
			$GLOBALS[ "logdt" ] = date("Ymd",filemtime($logfile));
		}
		else
		{
			$GLOBALS[ "logdt" ] = date("Ymd");
		}
		
		$GLOBALS[ "logfd" ] = fopen($logfile,"a");

		if (! $GLOBALS[ "logfd" ])
		{
			echo "Cannot open logfile...\n";
			exit();
		}

		chmod($logfile,0666);
	}
	
	if ($GLOBALS[ "logdt" ] != date("Ymd"))
	{
		//
		// Log file expired, re-open.
		//
		
		fclose($GLOBALS[ "logfd" ]);
		
		rename($logfile,substr($logfile,0,-4) . "." . $GLOBALS[ "logdt" ] . ".log");
		
		$GLOBALS[ "logfd" ] = fopen($logfile,"a");
		$GLOBALS[ "logdt" ] = date("Ymd",filemtime($logfile));
		
		chmod($logfile,0666);
	}
	
	fputs($GLOBALS[ "logfd" ],$message);
}


function EncodeMessage($message)
{
	$json = json_encode($message);
	$jlen = strlen($json);
	
	$packet = chr(($jlen >> 24) & 0xff) . chr(($jlen >> 16) & 0xff)
			. chr(($jlen >>  8) & 0xff) . chr(($jlen >>  0) & 0xff)
			. $json;
			
	return $packet;
}

function ScheduleSorryTask($task,&$request)
{
	if (isset($request[ "what" ])) return;

	$request[ "what" ] = "sorry";
	
	if (IsVersion($task,"1.03"))
	{
		$request[ "text" ] = "no work, please retry...";
	}
	else
	{
		$request[ "text" ] = "version too old, consider update...";
	}
	
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];
	
	$trans[ "consume" ] = "sorry";
}

function ScheduleMtrDomsTask($task,&$request)
{
	$isp = $request[ "isp" ];

	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"mtr")) return;
	if (! is_dir("../var/$isp/domains")) return;

	if (! isset($GLOBALS[ $isp ])) $GLOBALS[ $isp ] = array();
	
	if ((! isset($GLOBALS[ $isp ][ "domains" ]) ||
		  (count($GLOBALS[ $isp ][ "domains" ]) == 0)))
	{
		$gdomainstag  = "xx";
		$gdomainsjson = file_get_contents("../var/xx/manual/topdomains.$gdomainstag.json");
		$gdomains     = json_decdat($gdomainsjson);
		
		$cdomainstag  = substr($isp,0,2);
		$cdomainsjson = file_get_contents("../var/xx/manual/topdomains.$cdomainstag.json");
		$cdomains     = json_decdat($cdomainsjson);
		
		$alldoms = array_merge($gdomains,$cdomains);
		$domains = array();
		
		foreach ($alldoms as $domain => $alexa)
		{
			$domainfile = "../var/$isp/domains/$domain.json";
			
			if (file_exists($domainfile))
			{
				$domains[ $domain ] = filemtime($domainfile);
			}
			else
			{
				$domains[ $domain ] = 0;
			}
		}
		
		asort($domains);
		
		$GLOBALS[ $isp ][ "domains" ] = &$domains;
		$GLOBALS[ $isp ][ "alldoms" ] = &$alldoms;
	}
	
	$domains = &$GLOBALS[ $isp ][ "domains" ];
	$alldoms = &$GLOBALS[ $isp ][ "alldoms" ];
	
	$selected = false;
	
	foreach ($domains as $domain => $time)
	{
		if ((time() - $time) > (86400 * 7))
		{
			$selected = $domain;
			
			unset($domains[ $domain ]);
		}
		
		break;
	}
	
	if ($selected === false) return;
	
	$request[ "what" ] = "mtr";
	
	$request[ "ping" ]   = 0; // Ping before mtr.
	$request[ "mtrc" ]   = 5; // Five mtr rounds per IP.
	
	$request[ "list" ][] = $selected;
	$request[ "list" ][] = "www." . $selected;
	
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];
	
	$trans[ "consume"    ] = "mtrdoms";
	$trans[ "isp" 		 ] = $isp;
	$trans[ "domain"     ] = $selected;
	$trans[ "alexa"      ] = $alldoms[ $selected ];
}

function ConsumeMtrDomsTask($trans,$reply,$remote_host,$remote_port)
{
	$isp    = $trans[ "isp"    ];
	$domain = $trans[ "domain" ];
	
	$domaindata = array();
	
	$domaindata[ "domain"  ] = $domain;
	$domaindata[ "axrank"  ] = intval($trans[ "alexa"  ]);	
	
	if (count($reply[ "list" ][ 0 ]) != 0)
	{
		$domaindata[ "paths" ] = $reply[ "list" ][ 0 ];
	}
	else
	{
		$domaindata[ "paths" ] = $reply[ "list" ][ 1 ];
	}
	
	$domainfile = "../var/$isp/domains/$domain.json";
	$domainjson = json_encdat($domaindata) . "\n";
	
	file_put_contents($domainfile,$domainjson);
	
    Logdat("$remote_host:$remote_port"
    	. " => " 
    	. $trans[ "isp"  ]
    	. " => " 
        . $trans[ "consume" ] 
        . " => "
        . $domainfile 
        . "\n");
}

function ScheduleMtrLogsTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"mtr")) return;

	$isp = GetRandomISP("mtrlogs");

	if (! isset($GLOBALS[ $isp ])) $GLOBALS[ $isp ] = array();
	
	if (isset($GLOBALS[ $isp ][ "mtrlogstime" ]) &&
		((time() - $GLOBALS[ $isp ][ "mtrlogstime" ]) > 600))
	{
		unset($GLOBALS[ $isp ][ "mtrlogs" ]);
	}
	
	if ((! isset($GLOBALS[ $isp ][ "mtrlogs" ]) ||
		  (count($GLOBALS[ $isp ][ "mtrlogs" ]) == 0)))
	{
		$mtrlogs = array();
		
		$dfd = @opendir("../var/$isp/explore");
		
		if ($dfd)
		{
			while (($file = readdir($dfd)) !== false)
			{
				if (substr($file,0,1) == ".") continue;
				
				$subnetip   = substr($file,0,15);
				$mtrlogfile = "../var/$isp/mtrlogs/$subnetip.mtrlog.json";
	
				if (file_exists($mtrlogfile))
				{
					$mtrlogs[ $file ] = filemtime($mtrlogfile);
				}
				else
				{
					$mtrlogs[ $file ] = 0;
				}
			}
		
			closedir($dfd);
		}
		else
		{
			//
			// Retry on subnets only.
			//
			
			$dfd = @opendir("../var/$isp/subnets");
			
			if ($dfd)
			{
				while (($file = readdir($dfd)) !== false)
				{
					if (substr($file,0,1) == ".") continue;
				
					$subnetip   = substr($file,0,15);
					$mtrlogfile = "../var/$isp/mtrlogs/$subnetip.mtrlog.json";
				
					if (file_exists($mtrlogfile))
					{
						$mtrlogs[ $file ] = filemtime($mtrlogfile);
					}
					else
					{
						$mtrlogs[ $file ] = 0;
					}
				}
			
				closedir($dfd);
			}
		}
		
		asort($mtrlogs);
		
		$GLOBALS[ $isp ][ "mtrlogs"     ] = &$mtrlogs;
		$GLOBALS[ $isp ][ "mtrlogstime" ] = time();
	}
	
	$mtrlogs = &$GLOBALS[ $isp ][ "mtrlogs" ];
	
	$subnetdata = false;
	
	foreach ($mtrlogs as $file => $time)
	{
		if ((time() - $time) > (86400 * 7))
		{
			$subnetfile = "../var/$isp/explore/$file";
				
			if (file_exists($subnetfile))
			{
				$subnetjson = file_get_contents($subnetfile);
				$subnetdata = json_decdat($subnetjson);
			}
			else
			{
				$subnetfile = "../var/$isp/subnets/$file";
				
				if (file_exists($subnetfile))
				{
					$subnetjson = file_get_contents($subnetfile);
					$subnetdata = json_decdat($subnetjson);
				}
			}
			
			unset($mtrlogs[ $file ]);
		}
		
		break;
	}
	
	if ($subnetdata === false) return;
	
	$request[ "what" ] = "mtr";
	
	$request[ "ping" ]   = 1; // Ping before mtr.
	$request[ "mtrc" ]   = 1; // One mtr round per IP.
	$request[ "mtrd" ]   = 5; // Maximum different routes needed.
	
	if (isset($subnetdata[ "gw" ]))
	{
		$request[ "mtrd" ]   = 5; // Maximum different routes needed.
		$request[ "list" ][] = Bin2IP(IP2Bin($subnetdata[ "gw" ]) +  0);
	}
	
	$request[ "from" ]   = Bin2IP(IP2Bin($subnetdata[ "ip" ]) +   1);
	$request[ "upto" ]   = Bin2IP(IP2Bin($subnetdata[ "ip" ]) + 128);
	
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];
	
	$trans[ "consume"    ] = "mtrlogs";
	$trans[ "isp" 		 ] = $isp;
	$trans[ "subnetip"   ] = $subnetdata[ "ip" ];
}

function ConsumeMtrLogsTask($trans,$reply,$remote_host,$remote_port)
{
	$isp      = $trans[ "isp" ];
	$subnetip = $trans[ "subnetip" ];

	$mtrlog = array();
	
	$mtrlog[ "ip"    ] = $subnetip;	
	$mtrlog[ "paths" ] = $reply[ "list" ];
	
	$mtrlogfile = "../var/$isp/mtrlogs/$subnetip.mtrlog.json";
	$mtrlogjson = json_encdat($mtrlog) . "\n";
	
	file_put_contents($mtrlogfile,$mtrlogjson);
	//echo $mtrlogjson;
	
    Logdat("$remote_host:$remote_port"
    	. " => " 
    	. $trans[ "isp"  ]
    	. " => " 
        . $trans[ "consume" ] 
        . " => "
        . $mtrlogfile 
        . "\n");
}

function ManagePingresult(&$list)
{
	ksort($list);
	
	$lststate =    -1;
	$oldstate =    -1;
	$kannwech = false;
	$wasalive = false;
	
	foreach ($list as $stamp => $ms)
	{
		$newstate = ($ms == -1) ? 0 : 1;
		
		if ($newstate == 1) $wasalive = true;
		
		if ($newstate == $oldstate)
		{
			if ($kannwech !== false) unset($list[ $kannwech ]);
			
			$kannwech = $stamp;
		}
		else
		{
			$kannwech = false;
		}
		
		$lststate = $oldstate;
		$oldstate = $newstate;
	}
	
	krsort($list);
	
	if (($lststate == -1) || ($lststate == $oldstate) || (! $wasalive)) return false;
	
	if ($oldstate == 0) return "died";
	
	return "live";	
}

function ScheduleEndpingTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"endping")) return;
	if (! IsVersion($task,"1.03")) return;
	
	$isp = GetRandomISP("endping");

	if (! isset($GLOBALS[ $isp ])) $GLOBALS[ $isp ] = array();
	
	if (! is_dir("../var/$isp/results")) mkdir("../var/$isp/results",0777);
	if (! is_dir("../var/$isp/taskmem")) mkdir("../var/$isp/taskmem",0777);

	//
	// Read nopings segments status file.
	//
	
	if (! isset($GLOBALS[ $isp ][ "nopingse" ]))
	{
		$GLOBALS[ $isp ][ "nopingse" ] = array();
		
		$nopingsefile = "../var/$isp/results/nopings.endpoints.json";
	
		if (file_exists($nopingsefile))
		{
			$json = file_get_contents($nopingsefile);
			$data = json_decdat($json);
			$GLOBALS[ $isp ][ "nopingse" ] = $data[ "nopings" ];
		}
	}
	
	//
	// Organize endpings todo lists.
	//
	
	if (isset($GLOBALS[ $isp ][ "endpingstime" ]) &&
		((time() - $GLOBALS[ $isp ][ "endpingstime" ]) > 600))
	{
		unset($GLOBALS[ $isp ][ "endpings" ]);
		unset($GLOBALS[ $isp ][ "deadnets" ]);
	}
	
	if ((! isset($GLOBALS[ $isp ][ "endpings" ]) ||
		  (count($GLOBALS[ $isp ][ "endpings" ]) == 0)))
	{
		//
		// Read the dead nets first.
		//
		
		$deadnetsjson = file_get_contents("../var/$isp/configs/deadnets.json");
		$deadnetsdata = json_decdat($deadnetsjson);
		
		if ($deadnetsdata)
		{
			$deadnets = array();
			
			foreach ($deadnetsdata as $iprange => $what)
			{
				$parts = explode("-",$iprange);
				if (count($parts) != 2) continue;
				
				$from = IP2Bin($parts[ 0 ]);
				$toto = IP2Bin($parts[ 1 ]);
				
				for ($actip = $from; $actip <= $toto; $actip += 256)
				{
					$deadnets[ IPZero($actip) ] = $what;
				}
			}
			
			$GLOBALS[ $isp ][ "deadnets" ] = &$deadnets;
		}
			
		$dfd = opendir("../var/$isp/subnets");
		if (! $dfd) return;
		
		$endpings = array();
		
		while (($file = readdir($dfd)) !== false)
		{
			if (substr($file,0,1) == ".") continue;
			
			$endpingip   = substr($file,0,15);
			$endpingfile = "../var/$isp/endping/$endpingip.ping.json";
			
			if (file_exists($endpingfile))
			{
				$endpings[ $file ] = filemtime($endpingfile);
			}
			else
			{
				$endpings[ $file ] = 0;
			}
		}
		
		closedir($dfd);
		
		asort($endpings);
		
		$GLOBALS[ $isp ][ "endpings"     ] = &$endpings;
		$GLOBALS[ $isp ][ "endpingstime" ] = time();
		
		//
		// Segment best ping ip optimizer.
		//
		
		$endpingsbestfile = "../var/$isp/taskmem/endpingsbest.json";
		
		if (isset($GLOBALS[ $isp ][ "endpingsbest" ]))
		{
			ksort($GLOBALS[ $isp ][ "endpingsbest" ]);
			$json = json_encdat($GLOBALS[ $isp ][ "endpingsbest" ]);
			file_put_contents($endpingsbestfile,$json . "\n");
		}
		else
		{
			if (file_exists($endpingsbestfile))
			{
				$json = file_get_contents($endpingsbestfile);
				$GLOBALS[ $isp ][ "endpingsbest" ] = json_decdat($json);
			}
			
			if ((! isset($GLOBALS[ $isp ][ "endpingsbest" ])) ||
				($GLOBALS[ $isp ][ "endpingsbest" ] === null))
			{
				$GLOBALS[ $isp ][ "endpingsbest" ] = array();
			}
		}
	}
	
	$endpings = &$GLOBALS[ $isp ][ "endpings" ];
	$deadnets = &$GLOBALS[ $isp ][ "deadnets" ];
	
	$subnetdata = false;
	
	foreach ($endpings as $file => $time)
	{
		if ((time() - $time) > 300)
		{
			$subnetdata = json_decdat(file_get_contents("../var/$isp/subnets/$file"));		
			
			unset($endpings[ $file ]);
		}
		
		break;
	}
	
	if ($subnetdata === false) return;
	
	$bestping = $GLOBALS[ $isp ][ "endpingsbest" ];
	
	$pinglist = array();
	$bestlist = array();
	
	$from = IP2Bin($subnetdata[ "ip" ]);
	$upto = IP2Bin($subnetdata[ "bc" ]);
	
	for ($actip = $from; $actip <= $upto; $actip += 256)
	{
		$ping = IPZero($actip);
		
		if (isset($deadnets[ $ping ])) 
		{
			//
			// Do dead nets only once in a while.
			//

			//Logdat("Dead endping: $from\n");
			
			continue;
		}
		
		$best = isset($bestping[ $ping ]) ? $bestping[ $ping ] : false;
		
		array_push($pinglist,$ping);
		array_push($bestlist,$best);
	}
	
	if (count($pinglist) == 0)
	{	
		//
		// All segments dead.
		//
		
		$subnetip    = $subnetdata[ "ip" ];
		$endpingfile = "../var/$isp/endping/$subnetip.ping.json";
			
		touch($endpingfile);
		
		return;
	}	
		
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];

	$request[ "what" ] = "endping";
	
	$request[ "list" ] = &$pinglist;
	$request[ "best" ] = &$bestlist;
	$request[ "maxp" ] = 255;
	
	if (isset($subnetdata[ "gw" ]))
	{
		$request[ "gate" ] = $subnetdata[ "gw" ];
		$trans  [ "gate" ] = $subnetdata[ "gw" ];
	}
	
	$trans[ "consume" ] = "endping";
	$trans[ "isp" 	  ] = $isp;
	$trans[ "subnet"  ] = $subnetdata;
	$trans[ "list"    ] = $request[ "list" ];
}

function ConsumeEndpingTask($trans,$reply,$remote_host,$remote_port)
{
	$isp    = $trans[ "isp"    ];
	$subnet = $trans[ "subnet" ];
	
	$segsc = count($trans[ "list" ]);
	$listc = count($reply[ "list" ]);
	$bestc = count($reply[ "best" ]);
	
	if (($segsc != $listc) || ($segsc != $bestc))
	{
		//
		// Requested pings count not replied pings count.
		//
		
		Logdat("$remote_host:$remote_port"
		   . " => " 
		   . $trans[ "isp"  ]
		   . " => " 
		   . $trans[ "consume" ] 
		   . " => "
		   . $segsc 
		   . " != "
		   . $listc 
		   . "\n");
		   
		return;
	}
	
	$subnetip    = substr($subnet[ "ip" ],0,15);
	$endpingfile = "../var/$isp/endping/$subnetip.ping.json";
	$endpingdata = array();
	
	if (file_exists($endpingfile))
	{
		$endpingdata = json_decdat(file_get_contents($endpingfile));
		if ($endpingdata === false) $endpingdata = array();
	}
	
	$stamp = date("Ymd.His");
	
	$bestping = &$GLOBALS[ $isp ][ "endpingsbest" ];
	
	$dirty = false;
	$alive = false;

	foreach ($trans[ "list" ] as $from)
	{		
		$ms   = array_shift($reply[ "list" ]);
		$best = array_shift($reply[ "best" ]);
		
		$bestping[ $from ] = $best;
		
		$alive = $alive || ($ms != -1);
		
		if (! isset($endpingdata[ $from ])) $endpingdata[ $from ] = array();

		$endpingdata[ $from ][ $stamp ] = $ms;

		$change = ManagePingresult($endpingdata[ $from ]);
		
		if ($change !== false)
		{
			if ($change == "died")
			{
				$GLOBALS[ $isp ][ "nopingse" ][ $from ] = $change;
				$dirty = true;
			}
		
			if ($change == "live")
			{
				unset($GLOBALS[ $isp ][ "nopingse" ][ $from ]);
				$dirty = true;
			}
		
			Logdat("$remote_host:$remote_port"
			   . " => " 
			   . $trans[ "isp"  ]
			   . " => " 
			   . $trans[ "consume" ] 
			   . " => "
			   . $from 
			   . " => "
			   . $change
			   . " => "
			   . $trans[ "host" ]
			   . "\n");
		}
		else
		{
			if (($ms != -1) && isset($GLOBALS[ $isp ][ "nopingse" ][ $from ]))
			{
				//
				// Clean unconsolidated state.
				//
				
				unset($GLOBALS[ $isp ][ "nopingse" ][ $from ]);
				$dirty = true;
			}
		}
	}
	
	if (isset($trans[ "gate" ]))
	{
		$gate = $trans[ "gate" ];
		$ms   = $reply[ "gate" ];
		$myms = "n.a.";
		
		if (! isset($endpingdata[ $gate ])) $endpingdata[ $gate ] = array();

		if ($ms == -1) $ms = $myms = Ping(IP($gate),1000);
		
		$endpingdata[ $gate ][ $stamp ] = $ms;

		$change = ManagePingresult($endpingdata[ $gate ]);
		
		if ($change !== false)
		{
			if ($change == "died")
			{
				$GLOBALS[ $isp ][ "nopingse" ][ $gate ] = $change;
				$dirty = true;
			}
		
			if ($change == "live")
			{
				unset($GLOBALS[ $isp ][ "nopingse" ][ $gate ]);
				$dirty = true;
			}
		
			Logdat("$remote_host:$remote_port"
			   . " => " 
			   . $trans[ "isp"  ]
			   . " => " 
			   . $trans[ "consume" ] 
			   . " => "
			   . $gate 
			   . " => "
			   . $change
			   . " => "
			   . $myms
			   . " => "
			   . $trans[ "host" ]
			   . "\n");
		}
		else
		{
			if (($ms != -1) && isset($GLOBALS[ $isp ][ "nopingse" ][ $gate ]))
			{
				//
				// Clean unconsolidated state.
				//
				
				unset($GLOBALS[ $isp ][ "nopingse" ][ $gate ]);
				$dirty = true;
			}
		}
		
	}
	
	if ($dirty)
	{
		//
		// Check for deadnets in list.
		//
		
		$deadnets = &$GLOBALS[ $isp ][ "deadnets" ];
		$nopingse = &$GLOBALS[ $isp ][ "nopingse" ];

		foreach ($deadnets as $deadip => $dummy)
		{
			if (! isset($nopingse[ $deadip ])) continue;
			unset($nopingse[ $deadip ]);
		}

		$data = array();
		$data[ "stamp"   ] = date("Ymd.His");
		$data[ "nopings" ] = &$GLOBALS[ $isp ][ "nopingse" ];
		
		ksort($data[ "nopings" ]);
		
		$nopingsejson = json_encdat($data) . "\n";	
		$nopingsefile = "../var/$isp/results/nopings.endpoints.json";
		file_put_contents($nopingsefile,$nopingsejson);
		
		$nopingsejson = "kappa.EndpointsNopingsCallback(\n$nopingsejson);\n";	
		$nopingsefile = "../www/$isp/endpoints.nopings.js";
		file_put_contents($nopingsefile,$nopingsejson);
	}
	
	file_put_contents($endpingfile,json_encdat($endpingdata) . "\n");
	
	if (! $alive)
	{
		$line = "\t\"" . $subnet[ "ip" ] . "-" . $subnet[ "bc" ] . "\" : \"noping\",\n";
		AppendFile("../log/deadnets.log",$line);
		echo $line;
	}
	
	Logdat("$remote_host:$remote_port"
	   . " => " 
	   . $trans[ "isp"  ]
	   . " => " 
	   . $trans[ "consume" ] 
	   . " => "
	   . $endpingfile 
	   . "\n");
}

function ScheduleUplpingTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"uplping")) return;
	if (! IsVersion($task,"1.03")) return;
	
	$isp = GetRandomISP("mapdata/uplinks.json");
	$isp = "de/kd";
	
	if (! isset($GLOBALS[ $isp ])) $GLOBALS[ $isp ] = array();
	
	if (! is_dir("../var/$isp/results")) mkdir("../var/$isp/results",0777);
	if (! is_dir("../var/$isp/uplping")) mkdir("../var/$isp/uplping",0777);

	//
	// Read nopings uplinks status file.
	//
	
	if (! isset($GLOBALS[ $isp ][ "nopingsu" ]))
	{
		$GLOBALS[ $isp ][ "nopingsu" ] = array();
		
		$nopingsufile = "../var/$isp/results/nopings.uplinks.json";
	
		if (file_exists($nopingsufile))
		{
			$json = file_get_contents($nopingsufile);
			$data = json_decdat($json);
			$GLOBALS[ $isp ][ "nopingsu" ] = $data[ "nopings" ];
		}
	}
	
	//
	// Organize endpings todo lists.
	//
	
	if (isset($GLOBALS[ $isp ][ "uplpingstime" ]) &&
		((time() - $GLOBALS[ $isp ][ "uplpingstime" ]) > 600))
	{
		unset($GLOBALS[ $isp ][ "uplpings" ]);
	}
	
	if ((! isset($GLOBALS[ $isp ][ "uplpings" ]) ||
		  (count($GLOBALS[ $isp ][ "uplpings" ]) == 0)))
	{			
		$uplpingsfile = "../var/$isp/mapdata/uplinks.json";
		$uplpings = json_decdat(file_get_contents($uplpingsfile));
		
		foreach ($uplpings as $uplinkip => $dummy)
		{
			if (substr($uplinkip,0,9) == "001.000.0")
			{
				//
				// Dummy unpingeable uplinks, remove.
				//
				
				unset($uplpings[ $uplinkip ]);
				continue;
			}
			
			$uplpingfile = "../var/$isp/uplping/$uplinkip.ping.json";
			
			if (file_exists($uplpingfile))
			{
				$uplpings[ $uplinkip ] = filemtime($uplpingfile);
			}
			else
			{
				$uplpings[ $uplinkip ] = 0;
			}
		}
		
		asort($uplpings);
		
		$GLOBALS[ $isp ][ "uplpings"     ] = &$uplpings;
		$GLOBALS[ $isp ][ "uplpingstime" ] = time();
	}
	
	$uplpings = &$GLOBALS[ $isp ][ "uplpings" ];
	
	$pinglist = array();
	
	foreach ($uplpings as $uplinkip => $time)
	{
		if ((time() - $time) > 300)
		{
			array_push($pinglist,$uplinkip);
			unset($uplpings[ $uplinkip ]);
				
			if (count($pinglist) < 64) continue;
		}
		
		break;
	}
	
	if (! count($pinglist)) return;
			
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];

	$request[ "what" ] = "uplping";
	$request[ "list" ] = &$pinglist;

	$trans[ "consume" ] = "uplping";
	$trans[ "isp" 	  ] = $isp;
	$trans[ "list"    ] = &$pinglist;
}

function ConsumeUplpingTask($trans,$reply,$remote_host,$remote_port)
{
	$isp = $trans[ "isp" ];
	
	if (count($trans[ "list" ]) != count($reply[ "list" ]))
	{
		//
		// Requested pings count not replied pings count.
		//
		
		Logdat("$remote_host:$remote_port"
		   . " => " 
		   . $trans[ "isp"  ]
		   . " => " 
		   . $trans[ "consume" ] 
		   . " => "
		   . count($trans[ "list" ])
		   . " != "
		   . count($reply[ "list" ]) 
		   . "\n");
		   
		return;
	}
	
	$dirty = false;
	
	foreach ($trans[ "list" ] as $uplinkip)
	{		
		$ms   = array_shift($reply[ "list" ]);
		$myms = "n.a.";
		
		if ($ms == -1) $ms = $myms = Ping(IP($uplinkip),1000);

		$uplpingfile = "../var/$isp/uplping/$uplinkip.ping.json";
		$uplpingdata = array();
	
		if (file_exists($uplpingfile))
		{
			$uplpingdata = json_decdat(file_get_contents($uplpingfile));
			if ($uplpingdata === false) $uplpingdata = array();
		}
	
		$stamp = date("Ymd.His");

		if (! isset($uplpingdata[ $uplinkip ])) $uplpingdata[ $uplinkip ] = array();

		$uplpingdata[ $uplinkip ][ $stamp ] = $ms;

		$change = ManagePingresult($uplpingdata[ $uplinkip ]);
		
		if ($change !== false)
		{
			if ($change == "died")
			{
				//
				// Re-schedule right away.
				//
				
				$GLOBALS[ $isp ][ "uplpings" ][ $uplinkip ] = 0;
				asort($GLOBALS[ $isp ][ "uplpings" ]);
				
				$GLOBALS[ $isp ][ "nopingsu" ][ $uplinkip ] = $change;
				$dirty = true;
			}
		
			if ($change == "live")
			{
				unset($GLOBALS[ $isp ][ "nopingsu" ][ $uplinkip ]);
				$dirty = true;
			}
		
			Logdat("$remote_host:$remote_port"
			   . " => " 
			   . $trans[ "isp"  ]
			   . " => " 
			   . $trans[ "consume" ] 
			   . " => "
			   . $uplinkip 
			   . " => "
			   . $change
			   . " => "
			   . $myms
			   . " => "
			   . $trans[ "host" ]
			   . "\n");
		}
		else
		{
			if (($ms != -1) && isset($GLOBALS[ $isp ][ "nopingsu" ][ $uplinkip ]))
			{
				//
				// Clean unconsolidated state.
				//
				
				unset($GLOBALS[ $isp ][ "nopingsu" ][ $uplinkip ]);
				$dirty = true;
			}
		}
			
		file_put_contents($uplpingfile,json_encdat($uplpingdata) . "\n");
	}

	if ($dirty)
	{
		$data = array();
		$data[ "stamp"   ] = date("Ymd.His");
		$data[ "nopings" ] = &$GLOBALS[ $isp ][ "nopingsu" ];
		
		ksort($data[ "nopings" ]);
		
		$nopingsujson = json_encdat($data) . "\n";	
		$nopingsufile = "../var/$isp/results/nopings.uplinks.json";
		file_put_contents($nopingsufile,$nopingsujson);
		
		$nopingsujson = "kappa.UplinksNopingsCallback(\n$nopingsujson);\n";	
		$nopingsufile = "../www/$isp/uplinks.nopings.js";
		file_put_contents($nopingsufile,$nopingsujson);
	}
	
	Logdat("$remote_host:$remote_port"
	   . " => " 
	   . $trans[ "isp"  ]
	   . " => " 
	   . $trans[ "consume" ] 
	   . " => "
	   . count($trans[ "list" ])
	   . "\n");
}

function GetISPList($targetdir = null)
{
	$ttag = "isplist" . ($targetdir != null) ? "/$targetdir" : "";
	
	if (isset($GLOBALS[ $ttag ])) return $GLOBALS[ $ttag ];
	
	$isps = array();
	
	if (($cfd = opendir("../var")) !== false)
	{
		while (($country = readdir($cfd)) !== false)
		{
			if (substr($country,0,1) == ".") continue;
			if (substr($country,0,2) == "xx") continue;
			
			if (($ifd = opendir("../var/$country")) !== false)
			{
				while (($isp = readdir($ifd)) !== false)
				{
					if (substr($isp,0,1) == ".") continue;
					if (substr($isp,0,2) == "xx") continue;
				
					if (($targetdir == null) || file_exists("../var/$country/$isp/$targetdir"))
					{
						array_push($isps,"$country/$isp");
					}
				}
			}
		
			closedir($ifd);
		}

		closedir($cfd);
	}
	
	$GLOBALS[ $ttag ] = &$isps;
	
	return $isps;
}

function GetRandomISP($targetdir = null)
{
	$isplist = GetISPList($targetdir);
	
	if (! count($isplist)) return null;
	
	return $isplist[ mt_rand(0,count($isplist) - 1) ];
}

function ScheduleNetpingTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"ping")) return;

	$isp = GetRandomISP("netping");

	if (! isset($GLOBALS[ $isp ])) $GLOBALS[ $isp ] = array();
	
	if (isset($GLOBALS[ $isp ][ "netpingstime" ]) &&
		((time() - $GLOBALS[ $isp ][ "netpingstime" ]) > 600))
	{
		unset($GLOBALS[ $isp ][ "netpings" ]);
	}

	if ((! isset($GLOBALS[ $isp ][ "netpings" ]) ||
		  (count($GLOBALS[ $isp ][ "netpings" ]) == 0)))
	{
		$dfd = opendir("../var/$isp/explore");
		if (! $dfd) return;

		$netpings = array();
		
		while (($file = readdir($dfd)) !== false)
		{
			if (substr($file,0,1) == ".") continue;
			
			$subnetip    = substr($file,0,15);
			$netpingfile = "../var/$isp/netping/$subnetip.ping.json";
			
			if (file_exists($netpingfile))
			{
				$netpings[ $file ] = filemtime($netpingfile);
			}
			else
			{
				$netpings[ $file ] = 0;
			}
		}
		
		closedir($dfd);
		
		asort($netpings);
		
		$GLOBALS[ $isp ][ "netpings"     ] = &$netpings;
		$GLOBALS[ $isp ][ "netpingstime" ] = time();
	}
	
	$netpings = &$GLOBALS[ $isp ][ "netpings" ];
	
	$subnetip = false;
	$subnetbc = false;
	
	foreach ($netpings as $file => $time)
	{
		if ((time() - $time) > (86400 * 1))
		{
			$subnetip = substr($file,0,15);
			$subnetbc = IPZero(IP2Bin($subnetip) + 255);
			
			unset($netpings[ $file ]);
		}
		
		break;
	}
	
	if ($subnetip === false) return;
	
	$request[ "what" ] = "ping";
	
	$request[ "from" ] = $subnetip;
	$request[ "upto" ] = $subnetbc;
	
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];
	
	$trans[ "consume"  ] = "netping";
	$trans[ "isp" 	   ] = $isp;
	$trans[ "subnetip" ] = $subnetip;
	$trans[ "subnetbc" ] = $subnetbc;
}

function ConsumeNetpingTask($trans,$reply,$remote_host,$remote_port)
{
	$isp      = $trans[ "isp" ];
	$subnetip = $trans[ "subnetip" ];
	$subnetbc = $trans[ "subnetbc" ];

	$from  = IP2Bin($subnetip);
	$upto  = IP2Bin($subnetbc);
	
	$nodes = ($upto - $from) + 1;
	$listc = count($reply[ "list" ]);
	
	if ($nodes != $listc)
	{
		//
		// Requested pings count not replied pings count.
		//
		
		Logdat("$remote_host:$remote_port"
		   . " => " 
		   . $trans[ "isp"  ]
		   . " => " 
		   . $trans[ "consume" ] 
		   . " => "
		   . $nodes 
		   . " != "
		   . $listc 
		   . "\n");
		   
		return;
	}
	
	$stamp = date("Ymd.His");
	$list  = $reply[ "list" ];
	
	while ($from < $upto)
	{
		$nodes = 0;
		$avrms = 0;
		$lowms = -1;
		$lowip = "n.a.";
		$pings = "";

		for ($actip = $from; $actip < ($from + 256); $actip++)
		{
			$ms = array_pop($list);
			if ($ms >= 100) $ms = 99;
			$pings .= $ms . ",";
			
			if ($ms >= 0)
			{
				$avrms += $ms;
				$nodes++;
				
				if (($lowms == -1) || ($ms <= $lowms))
				{
					$lowms = $ms;
					$lowip = IPZero($actip);
				}
			}
		}
		
		if ($nodes > 0) $avrms = floor($avrms / $nodes);
		
		$actip = IPZero($from);
		
		$netping     = array();
		$netpingfile = "../var/$isp/netping/$actip.ping.json";
	 
		if (file_exists($netpingfile))
		{
			$netpingjson = file_get_contents($netpingfile);
			$netping     = json_decdat($netpingjson);
		}
		
		$netping[ "cc"    ] = str_pad($nodes,3," ",STR_PAD_LEFT)
							. str_pad($avrms,3," ",STR_PAD_LEFT)
							. str_pad($lowms,3," ",STR_PAD_LEFT)
							. " "
							. $lowip
							;

		$netping[ "stamp" ] = $stamp;
		$netping[ "nodes" ] = $nodes;
		$netping[ "avrms" ] = $avrms;
		$netping[ "lowms" ] = $lowms;
		$netping[ "lowip" ] = $lowip;
							
		if (! isset($netping[ "pings" ])) $netping[ "pings" ] = array();
		$netping[ "pings" ][ $stamp ] = substr($pings,0,-1);
		
		krsort($netping[ "pings" ]);
		
		$netpingjson = json_encdat($netping) . "\n";
		file_put_contents($netpingfile,$netpingjson);

		Logdat("$remote_host:$remote_port"
		   . " => " 
		   . $trans[ "isp"  ]
		   . " => " 
		   . $trans[ "consume" ] 
		   . " => "
		   . $netpingfile 
		   . "\n");
		
		$from += 256;
	}
	
}

function IsVersion($task,$version)
{
	$tversion = floatval($task[ "version" ]);
	$dversion = floatval($version);

	return $tversion >= $dversion;	
}

function HasFeature($task,$feature)
{
	foreach ($task[ "tasks" ] as $what)
	{
		if ($what == $feature) return true;
	}
	
	return false;
}

function ScheduleTask($task,$remote_host,$remote_port)
{
	$request = array();
	
	$request[ "isp"  ] = ResolveISP($remote_host);
	$request[ "guid" ] = CreateGuid();
	
	$trans = array();
	$trans[ "host"    ] = $task[ "host" ];
	$trans[ "stamp"   ] = time();
	$trans[ "request" ] = &$request;
		
	$GLOBALS[ "transactions" ][ $request[ "guid" ] ] = &$trans;
	
	if (! mt_rand(0,8)) ScheduleMtrLogsTask($task,$request);
	if (! mt_rand(0,4)) ScheduleNetpingTask($task,$request);
	if (! mt_rand(0,2)) ScheduleEndpingTask($task,$request);
    if (! mt_rand(0,0)) ScheduleUplpingTask($task,$request);
	if (! mt_rand(0,0)) ScheduleNetpingTask($task,$request);

  //if (! mt_rand(0,0)) ScheduleMtrDomsTask($task,$request);
	
	if (! isset($request[ "what" ]))
	{
		//
		// Schedule no-op task.
		//
		
		ScheduleSorryTask($task,$request); 
		
		//
		// Unregister this transaction again.
		//
		
		unset($GLOBALS[ "transactions" ][ $request[ "guid" ] ]);
	}
	
	/* 
    Logdat("$remote_host:$remote_port"
    	. " => " 
    	. $request[ "isp"  ]
    	. " => " 
        . $task[ "what" ] 
        . " => "
        . $trans[ "consume" ] 
        . "\n");
	*/
	
	return $request;
}

function ConsumeReply($reply,$remote_host,$remote_port)
{
	$guid = $reply[ "guid" ];
	
	if (! isset($GLOBALS[ "transactions" ][ $guid ]))
	{
		//
		// Bogus or outdated reply.
		//
		
		return;
	}
	
	$trans = &$GLOBALS[ "transactions" ][ $guid ];
	
	switch ($trans[ "consume" ])
	{
		case "mtrlogs" : ConsumeMtrLogsTask($trans,$reply,$remote_host,$remote_port); break;
		case "mtrdoms" : ConsumeMtrDomsTask($trans,$reply,$remote_host,$remote_port); break;
		case "netping" : ConsumeNetpingTask($trans,$reply,$remote_host,$remote_port); break;
		case "endping" : ConsumeEndpingTask($trans,$reply,$remote_host,$remote_port); break;
		case "uplping" : ConsumeUplpingTask($trans,$reply,$remote_host,$remote_port); break;
		
		default: var_dump($reply); break;
	}

	unset($GLOBALS[ "transactions" ][ $guid ]);
	
	//
	// Check for expired no reply transactions.
	//
	
	if (count($GLOBALS[ "transactions" ]) > 1000)
	{
		$transactions = &$GLOBALS[ "transactions" ];
		
		$maxage = time() - 600;
		
		foreach ($transactions as $guid => $trans)
		{
			if ($trans[ "stamp" ] > $maxage) continue;
			
			echo "Noreply: " . $trans[ "host" ] . "\n";
			unset($transactions[ $guid ]);
		}
	}
}

function MainLoop($server_port)
{
	date_default_timezone_set("UTC");
	
	if (! is_dir("../run")) mkdir("../run",0755);
	if (! is_dir("../log")) mkdir("../log",0755);
	
	file_put_contents("../run/taskserver.php.pid",getmypid());

	Logdat("Server started...\n");
	
	$socket = socket_create(AF_INET,SOCK_DGRAM,SOL_UDP);
    socket_bind($socket,"0.0.0.0",$server_port);
	
    while (true)
    {
        $xlen = @socket_recvfrom($socket,$xfer,16000,MSG_DONTWAIT,$remote_host,$remote_port);
        
        if ($xlen === false) 
        {   
        	usleep(100);
       	 	continue;
		}
        
        $remote_host = IPZero($remote_host);
        
        $jlen = (ord($xfer[ 0 ]) << 24) + (ord($xfer[ 1 ]) << 16)
        	  + (ord($xfer[ 2 ]) <<  8) + (ord($xfer[ 3 ]) <<  0);
                
        $json = substr($xfer,4);

        $reply = json_decode($json,true);
        if (! $reply) continue;
        if (! isset($reply[ "what" ])) continue;
		
       	if ($reply[ "what" ] == "hello")
       	{
       		//
       		// Someone desires work.
       		//
       		
        	$request = ScheduleTask($reply,$remote_host,$remote_port);
        	$message = EncodeMessage($request);
   	 		socket_sendto($socket,$message,strlen($message),0,IP($remote_host),$remote_port);
		}
		else
		{	
			//
			// Someone has delivered work.
			//
				
        	ConsumeReply($reply,$remote_host,$remote_port);
        }
	}
}

MainLoop($GLOBALS[ "server_port" ]);

?>