<?php

include("../php/json.php");
include("../php/misc.php");

function WriteEventFile($file,$key,$what,$maxstamp)
{
	$json = file_exists($file) ? file_get_contents($file) : null;
	if ($json && (substr($file,-3) == ".js")) $json = substr($json,22,-3);
	$data = $json ? json_decdat($json) : array();	
	
	if ($key == null) 
	{
		//
		// Create empty event file.
		//
		
		$data = array();
	}
	else
	{
		$data[ $key ] = $what;
		krsort($data);
	
		if ($maxstamp != null)
		{
			foreach ($data as $key => $dummy)
			{
				if ($key < $maxstamp) unset($data[ $key ]);
			}
		}
	}
	
	$json = json_encdat($data) . "\n";
	if (substr($file,-3) == ".js") $json = "kappa.EventsCallback(\n$json);\n";
	file_put_contents($file . ".tmp",$json);
	rename($file . ".tmp",$file);
}

function WriteEvent($isp,$stamp = null,$type = null,$ip = null,$nc = null,$what = null)
{
	$nc = str_pad($nc,2,"0",STR_PAD_LEFT);
	
	$key = ($stamp != null) ? "$stamp|$type|$ip|$nc" : null;
	
	if ($key != null)
	{
		$eventfilear = "../var/$isp/eventar/events." . substr($stamp,0,8) . ".json";
		$maxstamp    = null;
		WriteEventFile($eventfilear,$key,$what,$maxstamp);
	}
	
	$eventfilenew = "../www/$isp/events.new.js";
	$maxstamp     = $stamp = date("Ymd.His",time() - (1 * 3600));
	WriteEventFile($eventfilenew,$key,$what,$maxstamp);
	
	$eventfile24h = "../www/$isp/events.24h.js";
	$maxstamp     = $stamp = date("Ymd.His",time() - (24 * 3600));
	WriteEventFile($eventfile24h,$key,$what,$maxstamp);
	
	$eventfile48h = "../www/$isp/events.48h.js";
	$maxstamp     = $stamp = date("Ymd.His",time() - (48 * 3600));
	WriteEventFile($eventfile48h,$key,$what,$maxstamp);
	
	$eventfile1wk = "../www/$isp/events.1wk.js";
	$maxstamp     = $stamp = date("Ymd.His",time() - (7 * 24 * 3600));
	WriteEventFile($eventfile1wk,$key,$what,$maxstamp);
}

function Stamp2Time($stamp)
{
	$strtime = substr($stamp,0,4) . "-" . substr($stamp, 4,2) . "-" . substr($stamp, 6,2)
	   . " " . substr($stamp,9,2) . ":" . substr($stamp,11,2) . ":" . substr($stamp,13,2)
	   ;
	   
	return strtotime($strtime);
}

function ComputeEvents($isp,$what)
{
	date_default_timezone_set("UTC");

	$targetdir = "../var/$isp/$what";
	
	if (! is_dir($targetdir)) return;
	
	$dfd  = opendir($targetdir);

	while (($file = readdir($dfd)) !== false)
	{
		if (substr($file,0,1) == ".") continue;
	
		$file = "$targetdir/$file";
	
		$json = json_decdat(file_get_contents($file));
		
		if (($json === false) || (count($json) == 0))
		{
			echo "Corrupt: $file\n";
			continue;
		}
		
		$dirty = false;
		$reorg = false;

		if (isset($json[ "*" ])) unset($json[ "*" ]);
		
		//
		// Cleanup stamps.
		//
		
		foreach ($json as $ip => $val)
		{
			ksort($json[ $ip ]);

			while (true)
			{
				$changed = false;
				
				$bforstamp  = null;
				$bfortime   = null;
				$laststamp  = null;
				$lasttime   = null;
				
				foreach ($json[ $ip ] as $stamp => $time)
				{
					if ($bforstamp && $laststamp)
					{
						if (($time == -1) && ($lasttime == -1) && ($bfortime == -1))
						{
							unset($json[ $ip ][ $laststamp ]);
							$changed = $reorg = true;
						}
						else
						{
							if (($time != -1) && ($lasttime == -1) && ($bfortime != -1))
							{
								unset($json[ $ip ][ $laststamp ]);
								$changed = $reorg = true;
							}
							else
							{
								if (($time != -1) && ($lasttime != -1) && ($bfortime != -1))
								{ 
									$nowslow = ($time     > 1000);
									$lstslow = ($lasttime > 1000);
									$bfoslow = ($bfortime > 1000);
						
									if (($nowslow == $lstslow) && ($lstslow == $bfoslow))
									{
										unset($json[ $ip ][ $laststamp ]);
										$changed = $reorg = true;
									}
								}
							}
						}
					}
					
					$bforstamp = $laststamp;
					$bfortime  = $lasttime;	
					$laststamp = $stamp;
					$lasttime  = $time;	
				}
			
				if (! $changed) breaK;
			}
			
			krsort($json[ $ip ]);
		}
				
		$nc 	 = ($what == "endping") ? count($json) : 0;
		$netip   = null;
		$summary = array();
		
		foreach ($json as $ip => $val)
		{
			if ($netip === null) $netip = $ip;
			
			ksort($val);
		
			$bforstamp  = null;
			$bfortime   = null;
			$laststamp  = null;
			$lasttime   = null;
			
			$diedstamp  = 0;
			$slowstamp  = 0;
			$eventcount = 0;
			
			$killstamps = array();
			
			foreach ($val as $stamp => $time)
			{
				if ($what == "endping")
				{
					if (! isset($summary[ $stamp ])) $summary[ $stamp ] = array();
					$summary[ $stamp ][ $ip ] = $time;
					
					continue;
				}
								
				if ($laststamp && $bforstamp)
				{
					if (($bfortime == -1) && ($lasttime == -1) && ($time != -1))
					{
						$dtime = Stamp2Time($diedstamp);
						$ltime = Stamp2Time($stamp);
						
						if (($ltime - $dtime) < 60)
						{
							$killstamps[ $diedstamp ] = true;
							$killstamps[ $stamp     ] = true;
						}
						
						$eventcount++;
					}
					else
					{
						if (($bfortime != -1) && ($lasttime == -1) && ($time == -1))
						{
							$diedstamp = $laststamp;
							$eventcount++;
						}
						else
						{
							/*
							if (($lasttime != -1) && ($time != -1))
							{
								//
								// Check for fast/slow transition.
								//
					
								if (($lasttime > 1000) && ($time <= 1000))
								{
									$dtime = Stamp2Time($slowstamp);
									$ltime = Stamp2Time($stamp);
				
									if (($ltime - $dtime) < 60)
									{
										$killstamps[ $dslowstamp ] = true;
										$killstamps[ $stamp      ] = true;
									}
				
									$eventcount++;
								}
								else
								if (($lasttime <= 1000) && ($time > 1000))
								{
									$dslowstamp = $stamp;
									$eventcount++;
								}
							}
							*/
						}
					}
				}
				
				$bforstamp = $laststamp;
				$bfortime  = $lasttime;	
				$laststamp = $stamp;
				$lasttime  = $time;	
			}
		
			if ($eventcount > 10)
			{
				echo "Flakey: $isp $what => $ip\n";
				continue;
			}
			
			if ($what == "endping") continue;
	
			$bforstamp = null;
			$bfortime  = null;
			$laststamp = null;
			$lasttime  = null;
			
			foreach ($val as $stamp => $time)
			{
				if ($laststamp && $bforstamp)
				{
					if (($bfortime == -1) && ($lasttime == -1) && ($time != -1))
					{
						if (! isset($killstamps[ $stamp ])) WriteEvent($isp,$stamp,$what,$ip,$nc,"live");
					}
					else
					{
						if (($bfortime != -1) && ($lasttime == -1) && ($time == -1))
						{
							if (! isset($killstamps[ $laststamp ])) WriteEvent($isp,$laststamp,$what,$ip,$nc,"died");
						}
						else
						{
							/*
							if (($lasttime != -1) && ($time != -1))
							{
								//
								// Check for fast/slow transition.
								//
					
								if (($lasttime > 1000) && ($time <= 1000))
								{
									if (! isset($killstamps[ $stamp ])) WriteEvent($isp,$stamp,$what,$ip,$nc,"fast");
								}
								else
								if (($lasttime <= 1000) && ($time > 1000))
								{
									if (! isset($killstamps[ $stamp ])) WriteEvent($isp,$stamp,$what,$ip,$nc,"slow");
								}
							}
							*/
						}
					}
				}

				$bforstamp = $laststamp;
				$bfortime  = $lasttime;	
				$laststamp = $stamp;
				$lasttime  = $time;	
			}
		}
		
		if ($what == "endping")
		{	
			$curtimes = array();
			$asterisk = array();
			
			ksort($summary);
			
			$laststamp = null;
			$lasttime  = null;

			foreach ($summary as $stamp => $ips)
			{
				foreach ($ips as $ip => $time)
				{
					$curtimes[ $ip ] = $time;
				}
				
				$sumalive = 0;
				
				foreach ($curtimes as $ip => $time)
				{
					$sumalive += ($time == -1) ? 0 : 1;
				}

				$asterisk[ $stamp ] = $sumalive;
			}
			
			krsort($asterisk);
			
			$json[ "*" ] = $asterisk;
			ksort($json);
			
			$dirty = true;
		
			ksort($asterisk);
								
			$laststamp = null;
			$lastsum   = null;

			foreach ($asterisk as $stamp => $sum)
			{
				if ($laststamp)
				{
					if (($lastsum == 0) && ($sum != 0))
					{
						WriteEvent($isp,$stamp,"endarea",$netip,$nc,"live");
					}
				
					if (($lastsum != 0) && ($sum == 0))
					{
						WriteEvent($isp,$stamp,"endarea",$netip,$nc,"died");
					}
				}

				$laststamp = $stamp;
				$lastsum   = $sum;	
			}
		}
		
		if ($dirty || $reorg) 
		{
			if ($reorg) echo "Reorg: $file\n";
			file_put_contents($file,json_encdat($json) . "\n");
		}
	}

	closedir($dfd);
}

	WriteEvent   ("de/kd");
	WriteEvent   ("de/tk");
	WriteEvent   ("de/tf");
	WriteEvent   ("de/vf");

	ComputeEvents("de/kd","eplping");
	ComputeEvents("de/kd","uplping");
	ComputeEvents("de/kd","bblping");
	ComputeEvents("de/kd","gwyping");
	ComputeEvents("de/kd","webping");
	ComputeEvents("de/kd","endping");
	
	ComputeEvents("de/tk","uplping");
	ComputeEvents("de/tk","bblping");
	ComputeEvents("de/tk","gwyping");
	ComputeEvents("de/tk","webping");
	ComputeEvents("de/tk","endping");
	
	ComputeEvents("de/tf","uplping");
	ComputeEvents("de/tf","bblping");
	ComputeEvents("de/tf","gwyping");
	ComputeEvents("de/tf","webping");
	ComputeEvents("de/tf","endping");
	
	ComputeEvents("de/vf","uplping");
	ComputeEvents("de/vf","bblping");
	ComputeEvents("de/vf","gwyping");
	ComputeEvents("de/vf","webping");
	ComputeEvents("de/vf","endping");
?>