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

	$isp = GetRandomISP("subnets");
	
	if (! is_dir("../var/$isp/domains")) mkdir("../var/$isp/domains",0777);

	if ($request[ "isp" ] != $isp) return;

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
		if ((time() - $time) > (86400 * 1))
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
	if (! IsFriendISP($isp,"mtrlogs",$request[ "isp" ])) return;
	
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
	$request[ "test" ] = GetCheckISP($isp,"mtrlogs");
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

function CorrectPingresult(&$list,$stamp,$ms)
{
	foreach($list as $oldstamp => $oldms) break;
	unset($list[ $oldstamp ]);
	foreach($list as $oldstamp => $oldms) break;
	unset($list[ $oldstamp ]);
	$list[ $stamp ] = $ms;
	krsort($list);
}

function ManagePingresult(&$list,$stamp,$ms)
{
	$change   = false;
	
	$oldstamp = null;
	$oldms    = null;
	
	$bfostamp = null;
	$bfoms    = null;
	
	reset($list);
	
	if (count($list) > 0) list($oldstamp,$oldms) = each($list);
	if (count($list) > 1) list($bfostamp,$bfoms) = each($list);
	
	if ($oldms !== null)
	{
		if (($oldms == -1) && ($ms != -1))
		{
			$change = "live";
		}
		else
		if (($oldms != -1) && ($ms == -1))
		{
			$change = "died";
		}
		else
		{
			if ($bfoms != null)
			{
				if (($ms == -1) && ($oldms == -1) && ($bfoms == -1))
				{
					unset($list[ $oldstamp ]);
				}
				else
				{
					if (($ms != -1) && ($oldms != -1) && ($bfoms != -1))
					{ 
						$nowslow = ($ms    > 1000);
						$oldslow = ($oldms > 1000);
						$bfoslow = ($bfoms > 1000);
					
						if ((($nowslow ==  true) && ($oldslow ==  true) && ($bfoslow ==  true)) ||
							(($nowslow == false) && ($oldslow == false) && ($bfoslow == false)))
						{
							unset($list[ $oldstamp ]);
						}
					}
				}
			}
		}
	}
	
	$list[ $stamp ] = $ms;
	krsort($list);

	return $change;
}

function ScheduleEndpingTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"endping")) return;
	if (! IsVersion($task,"1.03")) return;
	
	$isp = GetRandomISP("subnets");
	if (! IsFriendISP($isp,"endping",$request[ "isp" ])) return;

	if (! is_dir("../var/$isp/taskmem")) mkdir("../var/$isp/taskmem",0777);
	if (! is_dir("../var/$isp/endping")) mkdir("../var/$isp/endping",0777);

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
				if ($what != "noping") continue;
				
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
		if ((time() - $time) > 600)
		{
			$subnetdata = json_decdat(file_get_contents("../var/$isp/subnets/$file"));		
			
			unset($endpings[ $file ]);
			
			if (! $subnetdata) continue;
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
			
		if (file_exists($endpingfile)) touch($endpingfile);
		
		return;
	}	
		
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];

	$request[ "what" ] = "endping";
	$request[ "test" ] = GetCheckISP($isp,"endping");
	$request[ "list" ] = &$pinglist;
	$request[ "best" ] = &$bestlist;
	$request[ "maxp" ] = 255;
	
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

		$change = ManagePingresult($endpingdata[ $from ],$stamp,$ms);
		
		if ($change !== false)
		{
			if ($change == "died")
			{
				$dirty = true;
			}
		
			if ($change == "live")
			{
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
	}
			
	file_put_contents($endpingfile,json_encdat($endpingdata) . "\n");
	
	Logdat("$remote_host:$remote_port"
	   . " => " 
	   . $trans[ "isp"  ]
	   . " => " 
	   . $trans[ "consume" ] 
	   . " => "
	   . $endpingfile 
	   . "\n");
}

function ScheduleBblpingTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"bblping")) return;
	if (! IsVersion($task,"1.03")) return;
	
	$isp = GetRandomISP("mapdata/backbones.json");
	if (! IsFriendISP($isp,"bblping",$request[ "isp" ])) return;

	if (! is_dir("../var/$isp/bblping")) mkdir("../var/$isp/bblping",0777);
	
	//
	// Organize todo lists.
	//
	
	if (isset($GLOBALS[ $isp ][ "bblpingstime" ]) &&
		((time() - $GLOBALS[ $isp ][ "bblpingstime" ]) > 600))
	{
		unset($GLOBALS[ $isp ][ "bblpings" ]);
	}
	
	if ((! isset($GLOBALS[ $isp ][ "bblpings" ]) ||
		  (count($GLOBALS[ $isp ][ "bblpings" ]) == 0)))
	{			
		$bblpingsfile = "../var/$isp/mapdata/backbones.json";
		$bblpings = json_decdat(file_get_contents($bblpingsfile));
		
		foreach ($bblpings as $bblinkip => $bbdata)
		{
			if ($bbdata[ "loc" ] == "n.n.") 
			{
				unset($bblpings[ $bblinkip ]);
				continue;
			}
			
			$bblpingfile = "../var/$isp/bblping/$bblinkip.ping.json";
			
			if (file_exists($bblpingfile))
			{
				$bblpings[ $bblinkip ] = filemtime($bblpingfile);
			}
			else
			{
				$bblpings[ $bblinkip ] = 0;
			}
		}
		
		asort($bblpings);
		
		$GLOBALS[ $isp ][ "bblpings"     ] = &$bblpings;
		$GLOBALS[ $isp ][ "bblpingstime" ] = time();
	}
	
	$bblpings = &$GLOBALS[ $isp ][ "bblpings" ];
	
	$pinglist = array();
	
	foreach ($bblpings as $bblinkip => $time)
	{
		if ((time() - $time) > 300)
		{
			array_push($pinglist,$bblinkip);
			unset($bblpings[ $bblinkip ]);
				
			if (count($pinglist) < 64) continue;
		}
		
		break;
	}
	
	if (! count($pinglist)) return;
			
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];

	$request[ "what" ] = "bblping";
	$request[ "test" ] = GetCheckISP($isp,"bblping");
	$request[ "list" ] = &$pinglist;

	$trans[ "consume" ] = "bblping";
	$trans[ "isp" 	  ] = $isp;
	$trans[ "list"    ] = &$pinglist;
}

function ConsumeBblpingTask($trans,$reply,$remote_host,$remote_port)
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
	
	foreach ($trans[ "list" ] as $bblinkip)
	{		
		$ms   = array_shift($reply[ "list" ]);
		$myms = "n.a.";

		$bblpingfile = "../var/$isp/bblping/$bblinkip.ping.json";
		$bblpingdata = array();
	
		if (file_exists($bblpingfile))
		{
			$bblpingdata = json_decdat(file_get_contents($bblpingfile));
			if ($bblpingdata === false) $bblpingdata = array();
		}
	
		$stamp = date("Ymd.His");

		if (! isset($bblpingdata[ $bblinkip ])) $bblpingdata[ $bblinkip ] = array();

		$change = ManagePingresult($bblpingdata[ $bblinkip ],$stamp,$ms);
		
		if ($change !== false)
		{
			if ($change == "died")
			{
				$myms = Ping(IP($bblinkip),1000);
				
				if ($myms != -1)
				{
					CorrectPingresult($bblpingdata[ $bblinkip ],$stamp,$myms);
					$change = "noop";
				}
				else
				{
					//
					// Re-schedule right away.
					//
					
					$GLOBALS[ $isp ][ "bblpings" ][ $bblinkip ] = 0;
					asort($GLOBALS[ $isp ][ "bblpings" ]);
					
					$dirty = true;
				}
			}
		
			if ($change == "live")
			{
				$dirty = true;
			}
		
			Logdat("$remote_host:$remote_port"
			   . " => " 
			   . $trans[ "isp"  ]
			   . " => " 
			   . $trans[ "consume" ] 
			   . " => "
			   . $bblinkip 
			   . " => "
			   . $change
			   . " => "
			   . $myms
			   . " => "
			   . $trans[ "host" ]
			   . "\n");
		}
			
		file_put_contents($bblpingfile,json_encdat($bblpingdata) . "\n");
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

function ScheduleUplpingTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"uplping")) return;
	if (! IsVersion($task,"1.03")) return;
	
	$isp = GetRandomISP("mapdata/uplinks.json");
	if (! IsFriendISP($isp,"uplping",$request[ "isp" ])) return;

	if (! is_dir("../var/$isp/uplping")) mkdir("../var/$isp/uplping",0777);
		
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
	$request[ "test" ] = GetCheckISP($isp,"uplping");
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

		$uplpingfile = "../var/$isp/uplping/$uplinkip.ping.json";
		$uplpingdata = array();
	
		if (file_exists($uplpingfile))
		{
			$uplpingdata = json_decdat(file_get_contents($uplpingfile));
			if ($uplpingdata === false) $uplpingdata = array();
		}
	
		$stamp = date("Ymd.His");

		if (! isset($uplpingdata[ $uplinkip ])) $uplpingdata[ $uplinkip ] = array();

		$change = ManagePingresult($uplpingdata[ $uplinkip ],$stamp,$ms);
		
		if ($change !== false)
		{
			if ($change == "died")
			{
				$myms = Ping(IP($uplinkip),1000);
				
				if ($myms != -1)
				{
					CorrectPingresult($uplpingdata[ $uplinkip ],$stamp,$myms);
					$change = "noop";
				}
				else
				{
					//
					// Re-schedule right away.
					//
					
					$GLOBALS[ $isp ][ "uplpings" ][ $uplinkip ] = 0;
					asort($GLOBALS[ $isp ][ "uplpings" ]);
					
					$dirty = true;
				}
			}
		
			if ($change == "live")
			{
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
			
		file_put_contents($uplpingfile,json_encdat($uplpingdata) . "\n");
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

function LoadNetcheck($isp)
{
	if (isset($GLOBALS[ $isp ][ "netcheck" ]) &&
		((time() - $GLOBALS[ $isp ][ "netchecktime" ]) > 600))
	{
		unset($GLOBALS[ $isp ][ "netcheck" ]);
	}

	if (! isset($GLOBALS[ $isp ][ "netcheck" ]))
	{
		$netcheckfile = "../var/$isp/configs/netcheck.json";
		
		if (file_exists($netcheckfile))
		{
			$netjson  = file_get_contents($netcheckfile);
			$netcheck = json_decdat($netjson);

			$GLOBALS[ $isp ][ "netcheck"     ] = &$netcheck;
			$GLOBALS[ $isp ][ "netchecktime" ] = time();
		}
	}
}

function IsFriendISP($isp,$taskname,$workerisp)
{
	LoadNetcheck($isp);

	if ((! isset($GLOBALS[ $isp ][ "netcheck" ])) ||
		(! isset($GLOBALS[ $isp ][ "netcheck" ][ $taskname ])) ||
		(! isset($GLOBALS[ $isp ][ "netcheck" ][ $taskname ][ "friends" ])))
	{
		return ($isp == $workerisp);
	}
	
	if (isset($GLOBALS[ $isp ][ "netcheck" ][ $taskname ][ "friends" ][ $workerisp ]) &&
		     ($GLOBALS[ $isp ][ "netcheck" ][ $taskname ][ "friends" ][ $workerisp ] == true))
	{
		return true;
	}
	
	if (isset($GLOBALS[ $isp ][ "netcheck" ][ $taskname ][ "friends" ][ "*" ]) &&
		     ($GLOBALS[ $isp ][ "netcheck" ][ $taskname ][ "friends" ][ "*" ] == true))
	{
		return true;
	}
	
	return false;
}

function GetCheckISP($isp,$taskname)
{
	LoadNetcheck($isp);
	
	if (isset($GLOBALS[ $isp ][ "netcheck" ]) &&
		isset($GLOBALS[ $isp ][ "netcheck" ][ $taskname ]) &&
		isset($GLOBALS[ $isp ][ "netcheck" ][ $taskname ][ "checks" ]))
	{
		return $GLOBALS[ $isp ][ "netcheck" ][ $taskname ][ "checks" ];
	}
	
	return null;	
}

function ScheduleEplpingTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"eplping")) return;
	if (! IsVersion($task,"1.03")) return;
	
	$isp = GetRandomISP("mapdata/eplinks.json");
	if (! IsFriendISP($isp,"eplping",$request[ "isp" ])) return;
	
	if (! is_dir("../var/$isp/eplping")) mkdir("../var/$isp/eplping",0777);
			
	//
	// Organize endpings todo lists.
	//
	
	if (isset($GLOBALS[ $isp ][ "eplpingstime" ]) &&
		((time() - $GLOBALS[ $isp ][ "eplpingstime" ]) > 600))
	{
		unset($GLOBALS[ $isp ][ "eplpings" ]);
	}
	
	if ((! isset($GLOBALS[ $isp ][ "eplpings" ]) ||
		  (count($GLOBALS[ $isp ][ "eplpings" ]) == 0)))
	{			
		$eplpingsfile = "../var/$isp/mapdata/eplinks.json";
		$eplpings = json_decdat(file_get_contents($eplpingsfile));
		
		foreach ($eplpings as $eplinkip => $dummy)
		{
			if (substr($eplinkip,0,9) == "001.000.0")
			{
				//
				// Dummy unpingeable eplinks, remove.
				//
				
				unset($eplpings[ $eplinkip ]);
				continue;
			}
			
			$eplpingfile = "../var/$isp/eplping/$eplinkip.ping.json";
			
			if (file_exists($eplpingfile))
			{
				$eplpings[ $eplinkip ] = filemtime($eplpingfile);
			}
			else
			{
				$eplpings[ $eplinkip ] = 0;
			}
		}
		
		asort($eplpings);
		
		$GLOBALS[ $isp ][ "eplpings"     ] = &$eplpings;
		$GLOBALS[ $isp ][ "eplpingstime" ] = time();
	}
	
	$eplpings = &$GLOBALS[ $isp ][ "eplpings" ];
	
	$pinglist = array();
	
	foreach ($eplpings as $eplinkip => $time)
	{
		if ((time() - $time) > 120)
		{
			array_push($pinglist,$eplinkip);
			unset($eplpings[ $eplinkip ]);
				
			if (count($pinglist) < 64) continue;
		}
		
		break;
	}
	
	if (! count($pinglist)) return;
			
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];

	$request[ "what" ] = "eplping";
	$request[ "test" ] = GetCheckISP($isp,"eplping");
	$request[ "list" ] = &$pinglist;

	$trans[ "consume" ] = "eplping";
	$trans[ "isp" 	  ] = $isp;
	$trans[ "list"    ] = &$pinglist;
}

function ConsumeEplpingTask($trans,$reply,$remote_host,$remote_port)
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
	
	foreach ($trans[ "list" ] as $eplinkip)
	{		
		$ms   = array_shift($reply[ "list" ]);
		$myms = "n.a.";

		$eplpingfile = "../var/$isp/eplping/$eplinkip.ping.json";
		$eplpingdata = array();
	
		if (file_exists($eplpingfile))
		{
			$eplpingdata = json_decdat(file_get_contents($eplpingfile));
			if ($eplpingdata === false) $eplpingdata = array();
		}
	
		$stamp = date("Ymd.His");

		if (! isset($eplpingdata[ $eplinkip ])) $eplpingdata[ $eplinkip ] = array();

		$change = ManagePingresult($eplpingdata[ $eplinkip ],$stamp,$ms);
		
		if ($change !== false)
		{
			if ($change == "died")
			{
				$myms = Ping(IP($eplinkip),1000);
				
				if ($myms != -1)
				{
					CorrectPingresult($eplpingdata[ $eplinkip ],$stamp,$myms);
					$change = "noop";
				}
				else
				{
					//
					// Re-schedule right away.
					//
					
					$GLOBALS[ $isp ][ "eplpings" ][ $eplinkip ] = 0;
					asort($GLOBALS[ $isp ][ "eplpings" ]);
					
					$dirty = true;
				}
			}
		
			if ($change == "live")
			{
				$dirty = true;
			}
		
			Logdat("$remote_host:$remote_port"
			   . " => " 
			   . $trans[ "isp"  ]
			   . " => " 
			   . $trans[ "consume" ] 
			   . " => "
			   . $eplinkip 
			   . " => "
			   . $change
			   . " => "
			   . $myms
			   . " => "
			   . $trans[ "host" ]
			   . "\n");
		}
			
		file_put_contents($eplpingfile,json_encdat($eplpingdata) . "\n");
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
	if (! HasFeature($task,"netping")) return;

	$isp = GetRandomISP("netping");
	if (! IsFriendISP($isp,"netping",$request[ "isp" ])) return;

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
	
	$request[ "what" ] = "netping";
	$request[ "test" ] = GetCheckISP($isp,"netping");
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

function ScheduleWebpingTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"webping")) return;
	if (! IsVersion($task,"1.03")) return;
	
	$isp = GetRandomISP("mapdata/gateways.json");
	if ($request[ "isp" ] != $isp) return;

	if (! is_dir("../var/$isp/webping")) mkdir("../var/$isp/webping",0777);
	
	//
	// Organize todo lists.
	//
	
	if (isset($GLOBALS[ $isp ][ "webpingstime" ]) &&
		((time() - $GLOBALS[ $isp ][ "webpingstime" ]) > 600))
	{
		unset($GLOBALS[ $isp ][ "webpings" ]);
	}
	
	if ((! isset($GLOBALS[ $isp ][ "webpings" ]) ||
		  (count($GLOBALS[ $isp ][ "webpings" ]) == 0)))
	{			
		$webpingsfile = "../var/$isp/mapdata/gateways.json";
		$gateways = json_decdat(file_get_contents($webpingsfile));
		$webpings = array();
		
		foreach ($gateways as $webinkip => $gwdata)
		{
			if ($gwdata[ "loc" ] == "n.n.") continue;
			
			foreach ($gwdata[ "domains" ] as $domain => $dummy)
			{
				$webpingfile = "../var/$isp/webping/$domain.ping.json";
				
				if (file_exists($webpingfile))
				{
					$webpings[ $domain ] = filemtime($webpingfile);
				}
				else
				{
					$webpings[ $domain ] = 0;
				}
			}
		}
		
		asort($webpings);
		
		$GLOBALS[ $isp ][ "webpings"     ] = &$webpings;
		$GLOBALS[ $isp ][ "webpingstime" ] = time();
	}
	
	$webpings = &$GLOBALS[ $isp ][ "webpings" ];
	
	$pinglist = array();
	
	foreach ($webpings as $webinkip => $time)
	{
		if ((time() - $time) > 180)
		{
			array_push($pinglist,$webinkip);
			unset($webpings[ $webinkip ]);
			
			if (count($pinglist) < 64) continue;
		}
		
		break;
	}
	
	if (! count($pinglist)) return;
			
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];

	$request[ "what" ] = "webping";
	$request[ "list" ] = &$pinglist;

	$trans[ "consume" ] = "webping";
	$trans[ "isp" 	  ] = $isp;
	$trans[ "list"    ] = &$pinglist;
}

function ScheduleGwypingTask($task,&$request)
{
	if (isset($request[ "what" ])) return;
	if (! HasFeature($task,"gwyping")) return;
	if (! HasFeature($task,"mtr")) return;
	if (! IsVersion($task,"1.03")) return;
	
	$isp = GetRandomISP("mapdata/gateways.json");
	if ($request[ "isp" ] != $isp) return;

	if (! is_dir("../var/$isp/gwyping")) mkdir("../var/$isp/gwyping",0777);
	
	//
	// Organize todo lists.
	//
	
	if (isset($GLOBALS[ $isp ][ "gwypingstime" ]) &&
		((time() - $GLOBALS[ $isp ][ "gwypingstime" ]) > 600))
	{
		unset($GLOBALS[ $isp ][ "gwypings" ]);
	}
	
	if ((! isset($GLOBALS[ $isp ][ "gwypings" ]) ||
		  (count($GLOBALS[ $isp ][ "gwypings" ]) == 0)))
	{			
		$gwypingsfile = "../var/$isp/mapdata/gateways.json";
		$gwypings = json_decdat(file_get_contents($gwypingsfile));
		$gwypmtrs = array();
		
		foreach ($gwypings as $gwyinkip => $bbdata)
		{
			if ($bbdata[ "loc" ] == "n.n.") 
			{
				unset($gwypings[ $gwyinkip ]);
				continue;
			}
			
			if (isset($bbdata[ "mtrdoms" ]))
			{
				$gwypmtrs[ $gwyinkip ] = $bbdata[ "mtrdoms" ];
			}

			$gwypingfile = "../var/$isp/gwyping/$gwyinkip.ping.json";
			
			if (file_exists($gwypingfile))
			{
				$gwypings[ $gwyinkip ] = filemtime($gwypingfile);
			}
			else
			{
				$gwypings[ $gwyinkip ] = 0;
			}
		}
		
		asort($gwypings);
		
		$GLOBALS[ $isp ][ "gwypmtrs"     ] = &$gwypmtrs;
		$GLOBALS[ $isp ][ "gwypings"     ] = &$gwypings;
		$GLOBALS[ $isp ][ "gwypingstime" ] = time();
	}
	
	$gwypings = &$GLOBALS[ $isp ][ "gwypings" ];
	$gwypmtrs = &$GLOBALS[ $isp ][ "gwypmtrs" ];
	
	$pinglist = array();
	$pmtrlist = array();
	
	foreach ($gwypings as $gwyinkip => $time)
	{
		if ((time() - $time) > 180)
		{
			array_push($pinglist,$gwyinkip);
			unset($gwypings[ $gwyinkip ]);
			
			if (isset($gwypmtrs[ $gwyinkip ])) 
			{
				$pmtrlist[ $gwyinkip ] = $gwypmtrs[ $gwyinkip ];
			}
			
			if (count($pinglist) < 64) continue;
		}
		
		break;
	}
	
	if (! count($pinglist)) return;
			
	$trans = &$GLOBALS[ "transactions" ][ $request[ "guid" ] ];

	$request[ "what" ] = "gwyping";
	$request[ "list" ] = &$pinglist;
	$request[ "pmtr" ] = &$pmtrlist;

	$trans[ "consume" ] = "gwyping";
	$trans[ "isp" 	  ] = $isp;
	$trans[ "list"    ] = &$pinglist;
}

function ConsumeAnypingTask($trans,$reply,$remote_host,$remote_port)
{
	$isp  = $trans[ "isp"     ];
	$what = $trans[ "consume" ];
	
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
	
	foreach ($trans[ "list" ] as $linkip)
	{		
		$ms   = array_shift($reply[ "list" ]);
		$myms = "n.a.";

		$pingfile = "../var/$isp/$what/$linkip.ping.json";
		$pingdata = array();
	
		if (file_exists($pingfile))
		{
			$pingdata = json_decdat(file_get_contents($pingfile));
			if ($pingdata === false) $pingdata = array();
		}
	
		$stamp = date("Ymd.His");

		if (! isset($pingdata[ $linkip ])) $pingdata[ $linkip ] = array();

		$change = ManagePingresult($pingdata[ $linkip ],$stamp,$ms);
		
		if ($change !== false)
		{
			if ($change == "died")
			{
				$myms = ($what == "webping") ? -1 : Ping(IP($linkip),1000);
				
				if ($myms != -1)
				{
					CorrectPingresult($pingdata[ $linkip ],$stamp,$myms);
					$change = "noop";
				}
				else
				{
					//
					// Re-schedule right away.
					//
					
					$GLOBALS[ $isp ][ $what . "s" ][ $linkip ] = 0;
					asort($GLOBALS[ $isp ][ $what . "s" ]);
					
					$dirty = true;
				}
			}
		
			if ($change == "live")
			{
				$dirty = true;
			}
		
			Logdat("$remote_host:$remote_port"
			   . " => " 
			   . $trans[ "isp"  ]
			   . " => " 
			   . $trans[ "consume" ] 
			   . " => "
			   . $linkip 
			   . " => "
			   . $change
			   . " => "
			   . $myms
			   . " => "
			   . $trans[ "host" ]
			   . "\n");
		}
			
		file_put_contents($pingfile,json_encdat($pingdata) . "\n");
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
	$isp = ResolveISP($remote_host);
		
	if (! isset($GLOBALS[ $isp ])) $GLOBALS[ $isp ] = array();

	$request = array();
	
	$request[ "isp"  ] = $isp;
	$request[ "guid" ] = CreateGuid();

	$trans = array();
	$trans[ "host"    ] = $task[ "host" ];
	$trans[ "stamp"   ] = time();
	$trans[ "request" ] = &$request;
		
	$GLOBALS[ "transactions" ][ $request[ "guid" ] ] = &$trans;

	if (! mt_rand(0,0)) ScheduleMtrLogsTask($task,$request);
	if (! mt_rand(0,0)) ScheduleEndpingTask($task,$request);
	if (! mt_rand(0,0)) ScheduleNetpingTask($task,$request);
    if (! mt_rand(0,0)) ScheduleWebpingTask($task,$request);
    if (! mt_rand(0,0)) ScheduleGwypingTask($task,$request);
    if (! mt_rand(0,0)) ScheduleBblpingTask($task,$request);
    if (! mt_rand(0,0)) ScheduleUplpingTask($task,$request);
    if (! mt_rand(0,0)) ScheduleEplpingTask($task,$request);
  	
  	if (! mt_rand(0,0)) ScheduleMtrDomsTask($task,$request);
	if (! mt_rand(0,0)) ScheduleMtrLogsTask($task,$request);
	
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
		case "webping" : ConsumeAnypingTask($trans,$reply,$remote_host,$remote_port); break;
		case "gwyping" : ConsumeAnypingTask($trans,$reply,$remote_host,$remote_port); break;
		case "bblping" : ConsumeBblpingTask($trans,$reply,$remote_host,$remote_port); break;
		case "uplping" : ConsumeUplpingTask($trans,$reply,$remote_host,$remote_port); break;
		case "eplping" : ConsumeEplpingTask($trans,$reply,$remote_host,$remote_port); break;
		
		default: var_dump($reply); break;
	}

	unset($GLOBALS[ "transactions" ][ $guid ]);
	
	//
	// Check for expired no reply transactions.
	//
	
	if (count($GLOBALS[ "transactions" ]) > 1000)
	{
		$maxage = time() - 600;
		
		foreach ($GLOBALS[ "transactions" ] as $guid => $trans)
		{
			if ($trans[ "stamp" ] > $maxage) break;
			
			echo "Noreply: " . $trans[ "host" ] . "\n";
			unset($GLOBALS[ "transactions" ][ $guid ]);
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
		
        if (($reply === false) || (! isset($reply[ "what" ])) || (($jlen + 4) != strlen($xfer))) 
		{
			echo "Test: $jlen = " . strlen($xfer) . "\n";
			echo "Test: $json\n";
			continue;
		}
		
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
