<?php

function KappaRound($val)
{
	return floor($val * 1000) / 1000.0;
}

function AppendFile($file,$line)
{
	$fd = fopen($file,"a");
	fputs($fd,$line);
	fclose($fd);
}

function Fix_City($city)
{
	if ($city == "Munich"   ) $city = "München";
	if ($city == "Nuremberg") $city = "Nürnberg";
	if ($city == "Ratisbon" ) $city = "Regensburg";
	if ($city == "Hanover"  ) $city = "Hannover";

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

function Fix_Region($region)
{
	if ($region == "01") $region = "Baden-Württemberg";
	if ($region == "02") $region = "Bayern";
	if ($region == "03") $region = "Bremen";
	if ($region == "04") $region = "Hamburg";
	if ($region == "05") $region = "Hessen";
	if ($region == "06") $region = "Niedersachsen";
	if ($region == "07") $region = "Nordrhein-Westfalen";
	if ($region == "08") $region = "Rheinland-Pfalz";
	if ($region == "09") $region = "Saarland";
	if ($region == "10") $region = "Schleswig-Holstein";
	if ($region == "11") $region = "Brandenburg";
	if ($region == "12") $region = "Mecklenburg-Vorpommern";
	if ($region == "13") $region = "Sachsen";
	if ($region == "14") $region = "Sachsen-Anhalt";
	if ($region == "15") $region = "Thüringen";
	if ($region == "16") $region = "Berlin";

	return $region;
}

function CreateGuid() 
{
    return sprintf(
    	'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
       
       	//
        // 32 bits for "time_low"
        //
        
        mt_rand(0,0xffff),mt_rand(0,0xffff),

		//
        // 16 bits for "time_mid"
        //
        
        mt_rand(0,0xffff),

		//
        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        //
        
        mt_rand(0,0x0fff) | 0x4000,

		//
        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        //
        
        mt_rand(0,0x3fff) | 0x8000,

		//
        // 48 bits for "node"
        //
        
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff)
    );
}

function IsPower2($val)
{
	while ($val > 1)
	{
		if (($val & 1) != 0) return false;
		$val = $val >> 1;
	}
	
	return true;
}

function NetDiv($ipbin)
{
	$netdiv = 0;
		
	if (($ipbin % (256 *   1)) == 0) $netdiv =   1;
	if (($ipbin % (256 *   2)) == 0) $netdiv =   2;
	if (($ipbin % (256 *   4)) == 0) $netdiv =   4;
	if (($ipbin % (256 *   8)) == 0) $netdiv =   8;
	if (($ipbin % (256 *  16)) == 0) $netdiv =  16;
	if (($ipbin % (256 *  32)) == 0) $netdiv =  32;
	if (($ipbin % (256 *  64)) == 0) $netdiv =  64;
	if (($ipbin % (256 * 128)) == 0) $netdiv = 128;
	if (($ipbin % (256 * 256)) == 0) $netdiv = 256;

	return $netdiv;
}

function IP2Bin($ip)
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

function Bin2IP($bin)
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

function IP($ip)
{
	return Bin2IP(IP2Bin($ip));
}

function IPZero($ip)
{	
	$bin = strpos($ip,".") ? IP2Bin($ip) : $ip;
	
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
			
			$entry[ "from" ] = IP2Bin($parts[ 0 ]);
			$entry[ "upto" ] = IP2Bin($parts[ 1 ]);
			$entry[ "name" ] = $parts[ 4 ];
			
			array_push($isplist,$entry);
		}
		
		$GLOBALS[ "isplist" ] = $isplist;
	}

	$ipbin = IP2Bin($ip);
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

function Ping($host)
{
	if (! isset($GLOBALS[ "uname" ])) $GLOBALS[ "uname" ] = `uname`;
	
	if (! isset($GLOBALS[ "pingbad" ])) $GLOBALS[ "pingbad" ] = 0;
	
	if ($GLOBALS[ "uname" ] == "Darwin")
	{
		exec("ping -c 1 -t 1 $host",$lines,$return);
	}
	else
	{
		exec("ping -c 1 -W 1 $host",$lines,$return);
	}
	
	if ($return == 0)
	{
		$lines = implode("\n",$lines);
		
		if (preg_match('/time=([0-9.]*) ms\n/',$lines,$matches))
		{
			$GLOBALS[ "pingbad" ] = 0;
			
			return intval($matches[ 1 ]);
		}
	}
	
	$GLOBALS[ "pingbad" ]++;
	
	return -1;
}

?>