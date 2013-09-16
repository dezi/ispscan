<?php

function Fix($xor,$y)
{
	return $xor ^ $y;
}

function Fux($xor,$flt)
{
	return floor($flt * 10000) ^ $xor;
}

function Fip($xor,$ip)
{
	return IP_Bin($ip) ^ ($xor << 8);
}

function EncodeEndpoints($map)
{	
	$new = array();
	$new[ "stamp"     ] = date("Ymd.His");
	$new[ "locations" ] = array();
	$new[ "endpoints" ] = array();

	//
	// Derive locations array list.
	//
	
	$lix = array();
	
	$fuxor = intval(substr($new[ "stamp" ],9,4),16);
	
	foreach ($map as $subinx => $subnet)
	{
		$map[ $subinx ][ "loc" ] 
			= $subnet[ "loc" ][ "country" ]
			. ","
			. $subnet[ "loc" ][ "region" ]
			. ","
			. $subnet[ "loc" ][ "city" ]
			. ","
			. Fux($fuxor,$subnet[ "loc" ][ "lat" ])
			. ","
			. Fux($fuxor,$subnet[ "loc" ][ "lon" ])
			;
		
		$lix[ $map[ $subinx ][ "loc" ] ] = 0;
		
		foreach ($subnet[ "segs" ] as $seginx => $segment)
		{
			$map[ $subinx ][ "segs" ][ $seginx ][ "loc" ] 
				= $segment[ "loc" ][ "country" ]
				. ","
				. $segment[ "loc" ][ "region" ]
				. ","
				. $segment[ "loc" ][ "city" ]
				. ","
				. Fux($fuxor,$segment[ "loc" ][ "lat" ])
				. ","
				. Fux($fuxor,$segment[ "loc" ][ "lon" ])
				;
			
			$lix[ $map[ $subinx ][ "segs" ][ $seginx ][ "loc" ] ] = 0;
		}
	}
	
	//
	// Shuffle locations list by sorting.
	//
	
	ksort($lix);
	
	//
	// Attach locations indices.
	//
	
	$lixmax = 0;
	
	foreach ($lix as $loc => $dummy)
	{
		$lix[ $loc ] = $lixmax++;
	}

	//
	// Derive magic xor from stamp.
	//
	
	$fixor = intval(substr($new[ "stamp" ],9,4),16) & $lixmax;
	
	//
	// Attach location indices to map.
	//

	foreach ($map as $subinx => $dummy)
	{
		if (isset($map[ $subinx ][ "ip"  ])) $map[ $subinx ][ "ip"  ] = Fip($fuxor,$map[ $subinx ][ "ip" ]);
		if (isset($map[ $subinx ][ "bc"  ])) $map[ $subinx ][ "bc"  ] = Fip($fuxor,$map[ $subinx ][ "bc" ]);
		if (isset($map[ $subinx ][ "gw"  ])) $map[ $subinx ][ "gw"  ] = Fip($fuxor,$map[ $subinx ][ "gw" ]);
		
		$map[ $subinx ][ "loc" ] = Fix($fixor,$lix[ $map[ $subinx ][ "loc" ] ],$lixmax);
		
		foreach ($map[ $subinx ][ "segs" ] as $seginx => $dummy)
		{
			$map[ $subinx ][ "segs" ][ $seginx ][ "from" ] = Fip($fuxor,$map[ $subinx ][ "segs" ][ $seginx ][ "from" ]);
			$map[ $subinx ][ "segs" ][ $seginx ][ "last" ] = Fip($fuxor,$map[ $subinx ][ "segs" ][ $seginx ][ "last" ]);
			
			$map[ $subinx ][ "segs" ][ $seginx ][ "loc" ] = Fix($fixor,$lix[ $map[ $subinx ][ "segs" ][ $seginx ][ "loc" ] ],$lixmax);
		}
		
		array_push($new[ "endpoints" ],$map[ $subinx ]);
	}
	
	foreach ($lix as $loc => $inx)
	{
		array_push($new[ "locations" ],$loc);
	}

	return $new;
}

?>
