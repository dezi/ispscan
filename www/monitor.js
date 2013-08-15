kappa = new Object();

kappa.ISPList =
{
	"de" :
	{
		"kd" : 
		{
			name : "Kabel Deutschland",
			nets :
			[
    			'024.134.000.000-024.134.255.255',
    			'031.016.000.000-031.019.255.255',
    			'037.004.000.000-037.005.255.255',
    			'077.020.000.000-077.023.255.255',
    			'088.134.000.000-088.134.191.255',
    			'091.064.000.000-091.067.255.255',
    			'095.088.000.000-095.091.255.255',
    			'146.052.000.000-146.052.255.255',
    			'178.024.000.000-178.027.255.255',
    			'188.192.000.000-188.195.255.255',
			]
		},
	
		"tk" : 
		{
			name : "Deutsche Telekom",
			nets :
			[
    			'046.080.000.000-046.095.255.255',
    			'079.192.000.000-079.255.255.255',
    			'080.128.000.000-080.159.255.255',
    		  //'080.187.000.000-080.187.255.255',
    			'084.128.000.000-084.191.255.255',
    			'087.128.000.000-087.159.255.255',
    			'087.160.000.000-087.191.255.255',
    			'091.000.000.000-091.063.255.255',
    			'093.192.000.000-093.255.255.255',
    			'217.000.000.000-217.007.255.255',
    			'217.080.000.000-217.095.255.255',
    			'217.224.000.000-217.255.255.255',
			]
		}
	}
}

kappa.MenuOnClick = function()
{
	if (this.mytype == 'selector') 
	{
		kappa.LocalStorageSet('selector',this.myval);
	}
	
	if (this.mytype == 'country') 
	{
		kappa.LocalStorageSet('country',this.myval);
	}
	
	if (this.mytype == 'provider') 
	{
		kappa.LocalStorageSet('provider-' + kappa.country,this.myval);
	}
	
	document.location.reload();
}

kappa.InitializeMenu = function()
{	
	var selectors = { "ep" : "Endpoints" , "bb" : "Backbone" , "gw" : "Gateways" };

	if (! kappa.LocalStorageGet('selector')) kappa.LocalStorageSet('selector','ep');
	kappa.selector = kappa.LocalStorageGet('selector');
	
	if (! kappa.LocalStorageGet('country')) kappa.LocalStorageSet('country','de');
	kappa.country = kappa.LocalStorageGet('country');

	if (! kappa.LocalStorageGet('provider-' + kappa.country)) kappa.LocalStorageSet('provider-' + kappa.country,'kd');
	kappa.provider = kappa.LocalStorageGet('provider-' + kappa.country);

	document.title = kappa.country.toUpperCase() 
				   + "/"
				   + kappa.provider.toUpperCase() 
				   + " " 
				   + selectors[ kappa.selector ]
				   ;
	
	kappa.Menu = document.createElement('div');
	
	kappa.Menu.style.position  		 = 'absolute';
	kappa.Menu.style.top       		 = '48px';
	kappa.Menu.style.right     		 = '5px';
	kappa.Menu.style.width     		 = '214px';
	kappa.Menu.style.height    		 = '48px';
	kappa.Menu.style.fontSize  		 = 'small';
	kappa.Menu.style.fontWeight		 = 'normal';
	kappa.Menu.style.fontFamily 	 = 'arial';
	kappa.Menu.style.cursor		 	 = 'pointer';

	document.body.appendChild(kappa.Menu);
	
	kappa.Countries = document.createElement('span');
	kappa.Countries.style.position   = 'absolute';
	kappa.Countries.style.top        = '0px';
	kappa.Countries.style.left       = '0px';
	kappa.Countries.style.height     = '15px';
	kappa.Countries.style.lineHeight = '17px';
	kappa.Countries.style.borderTop  = '1px solid grey';
	kappa.Countries.style.borderLeft = '1px solid grey';
	kappa.Countries.style.boxShadow  = '6px 6px 5px #aaa';
	kappa.Menu.appendChild(kappa.Countries);

	kappa.Providers = document.createElement('span');
	kappa.Providers.style.position   = 'absolute';
	kappa.Providers.style.top        = '16px';
	kappa.Providers.style.left       = '0px';
	kappa.Providers.style.height     = '15px';
	kappa.Providers.style.lineHeight = '17px';
	kappa.Providers.style.borderTop  = '1px solid grey';
	kappa.Providers.style.borderLeft = '1px solid grey';
	kappa.Providers.style.boxShadow  = '6px 6px 5px #aaa';
	kappa.Menu.appendChild(kappa.Providers);
	
	kappa.Selectors = document.createElement('span');
	kappa.Selectors.style.position   = 'absolute';
	kappa.Selectors.style.top        = '32px';
	kappa.Selectors.style.left       = '0px';
	kappa.Selectors.style.height     = '15px';
	kappa.Selectors.style.lineHeight = '17px';
	kappa.Selectors.style.borderTop  = '1px solid grey';
	kappa.Selectors.style.borderLeft = '1px solid grey';
	kappa.Selectors.style.boxShadow  = '6px 6px 5px #aaa';
	kappa.Menu.appendChild(kappa.Selectors);

	for (var ckey in kappa.ISPList)
	{
		var ce = document.createElement('span');
		ce.style.display	 	 = 'inline-block'
		ce.style.width  	 	 = '52px';
		ce.style.height		 	 = '15px';
		ce.style.textAlign   	 = 'center';
		ce.style.borderRight 	 = '1px solid grey';
		ce.style.fontWeight	 	 = (ckey == kappa.country) ? 'bold' : 'normal';
		ce.style.backgroundColor = '#ffffff';
		
		ce.innerHTML = ckey.toUpperCase();
		ce.onclick   = kappa.MenuOnClick;
		ce.mytype    = 'country';
		ce.myval     = ckey;
		
		kappa.Countries.appendChild(ce);
	}
	
	for (var pkey in kappa.ISPList[ kappa.country ])
	{
		var pe = document.createElement('span');
		pe.style.display	 	 = 'inline-block'
		pe.style.width  	 	 = '52px';
		pe.style.height 	 	 = '15px';
		pe.style.textAlign   	 = 'center';
		pe.style.borderRight 	 = '1px solid grey';
		pe.style.fontWeight	 	 = (pkey == kappa.provider) ? 'bold' : 'normal';
		pe.style.backgroundColor = '#ffffff';
		
		pe.innerHTML = pkey.toUpperCase();
		pe.onclick   = kappa.MenuOnClick;
		pe.mytype    = 'provider';
		pe.myval     = pkey;
		
		kappa.Providers.appendChild(pe);
	}
	
	for (var skey in selectors)
	{
		var ep = document.createElement('span');
		ep.style.display	 	 = 'inline-block'
		ep.style.width  	 	 = '52px';
		ep.style.height 	 	 = '15px';
		ep.style.textAlign   	 = 'center';
		ep.style.borderRight 	 = '1px solid grey';
		ep.style.borderBottom	 = '1px solid grey';
		ep.style.fontWeight		 = (skey == kappa.selector) ? 'bold' : 'normal';
		ep.style.backgroundColor = '#ffffff';
		
		ep.innerHTML = skey.toUpperCase();
		ep.onclick   = kappa.MenuOnClick;
		ep.mytype    = 'selector';
		ep.myval     = skey;

		kappa.Selectors.appendChild(ep);
	}
}

kappa.InitializeInfo = function()
{
	kappa.Info = document.createElement('div');
	kappa.Info.style.position  		 = 'absolute';
	kappa.Info.style.top       		 = '96px';
	kappa.Info.style.right     		 = '6px';
	kappa.Info.style.width     		 = '203px';
	kappa.Info.style.height    		 = '402px';
	kappa.Info.style.whiteSpace		 = 'nowrap';
	kappa.Info.style.overflow  		 = 'hidden';
	kappa.Info.style.padding    	 = '4px';
	kappa.Info.style.fontSize  		 = 'small';
	kappa.Info.style.fontWeight		 = 'normal';
	kappa.Info.style.fontFamily 	 = 'arial';
	kappa.Info.style.lineHeight  	 = '15px';
	kappa.Info.style.fontSize  		 = 'small';
	kappa.Info.style.fontWeight		 = 'normal';
	kappa.Info.style.fontFamily 	 = 'arial';
	kappa.Info.style.border    		 = '1px solid grey';
	kappa.Info.style.boxShadow  	 = '6px 6px 5px #aaa';
	kappa.Info.style.backgroundColor = '#ffffff';
	document.body.appendChild(kappa.Info);
	
	kappa.Info.divIndex = new Object();

	kappa.Info.setText = function(text,index)
	{
		var div = kappa.Info.divIndex[ index ] || document.createElement('div');
		
		if (div.parentNode) div.parentNode.removeChild(div);
		
		if (kappa.Info.firstChild)
		{
			kappa.Info.insertBefore(div,kappa.Info.firstChild);
		}
		else
		{
			kappa.Info.appendChild(div);
		}
		
		kappa.Info.divIndex[ index ] = div;
		
		div.innerHTML = text;
		
		var tag = text.substr(0,4);
		
		var sig = 0;
		
		if (tag == 'Live') sig = 1;
		if (tag == 'Fast') sig = 1;
		if (tag == 'Slow') sig = 2;
		if (tag == 'Died') sig = 3;
		if (tag == 'Dead') sig = 3;
		
		kappa.setSignal(sig);
	}
}

kappa.InitializeLabel = function()
{
	kappa.Label = function(opt_options) 
	{
		this.setValues(opt_options);

		this.myspan = document.createElement('span');
		this.myspan.style.cssText = 'position: relative; left: -50%; top: -8px; ' +
						  'white-space: nowrap; border: 1px solid blue; ' +
						  'border-radius: 8px; ' +
						  'padding: 2px; background-color: white; z-index:5000; ' +
						  'font-size: small;font-weight: normal; font-family: arial'
						  ;

		this.mydiv = document.createElement('div');
		this.mydiv.appendChild(this.myspan);
		this.mydiv.style.cssText = 'position: absolute; display: none';
	};

	kappa.Label.prototype = new google.maps.OverlayView;

	kappa.Label.prototype.onAdd = function() 
	{
		var pane = this.getPanes().overlayLayer;
		pane.appendChild(this.mydiv);

		var self = this;
	
		this.listeners = 
		[
			google.maps.event.addListener(this,'text_changed',    function(){self.draw();}),
			google.maps.event.addListener(this,'visible_changed', function(){self.draw();}),
			google.maps.event.addListener(this,'position_changed',function(){self.draw();})
		];
	};

	kappa.Label.prototype.onRemove = function() 
	{
		this.mydiv.parentNode.removeChild(this.mydiv);

		for (var i = 0; i < this.listeners.length; ++i) 
		{
			google.maps.event.removeListener(this.listeners[ i ]);
		}
	};

	kappa.Label.prototype.draw = function() 
	{
		var projection = this.getProjection();
		var position = projection.fromLatLngToDivPixel(this.get('position'));

		this.mydiv.style.left    = position.x + 'px';
		this.mydiv.style.top     = position.y + 'px';
		this.mydiv.style.zIndex  = this.zIndex;
		this.mydiv.style.display = this.visible ? 'block' : 'none';

		this.myspan.style.backgroundColor = this.bgColor;

		this.myspan.innerHTML = this.text
	};
	
	kappa.Label.prototype.setBGColor = function(bgColor)
	{
		this.bgColor = bgColor;
	};
	
	kappa.Label.prototype.setZIndex = function(zIndex)
	{
		this.zIndex = zIndex;
	};
	
	kappa.Label.prototype.setVisible = function(visible)
	{
		this.visible = visible;
	};
	
	kappa.Label.prototype.setText = function(text)
	{
		this.text = text;
	};
}

kappa.EventsCallback = function(events)
{
	kappa.Events = new Array();

	for (var einx in events)
	{
		var event = events[ einx ];

		if (! kappa.Events[ event.ip ]) kappa.Events[ event.ip ] = new Array();

		kappa.Events[ event.ip ].push(event);
	}
}

kappa.MyipCallback = function(myip)
{
	kappa.Mystuff = new Object();
	kappa.Mystuff.myip = myip;
} 

kappa.RoutersCallback = function(routers)
{
	kappa.Routers = routers;
}

kappa.EndpointPingsCallback = function(endpointpings)
{
	kappa.EndpointPings = endpointpings;
}

kappa.GatewayPingsCallback = function(gatewaypings)
{	
	console.log('GatewayPingsCallback...');

	if (kappa.GatewayScript)
	{
		kappa.GatewayScript.parentNode.removeChild(kappa.GatewayScript);
		kappa.GatewayScript = false;
	}

	window.clearTimeout(kappa.GatewayPingsTimer);

	if ((! kappa.GatewayPings) || (kappa.GatewayPings.stamp != gatewaypings.stamp))
	{
		kappa.GatewayPings = gatewaypings;
	
		if (kappa.GatewaysLocs) 
		{
			kappa.Signal = 0;
		
			console.log('GatewaysDraw...');
			
			kappa.GatewaysDraw(); 
		
			if (kappa.Signal == 1) kappa.SignalLive.play();
			if (kappa.Signal == 2) kappa.SignalSlow.play();
			if (kappa.Signal == 3) kappa.SignalDead.play();
		}
	}
		
	window.setTimeout('kappa.GatewayPingsRefresh()',2000);
}

kappa.GatewayPingsWatcher = function()
{
	kappa.GatewayPingsRefresh();
}

kappa.GatewayPingsRefresh = function()
{
	kappa.GatewayScript = document.createElement('script');
	kappa.GatewayScript.src = 'kd.gateways.ping.js?rnd=' + Math.random();
	document.body.appendChild(kappa.GatewayScript);
	
	kappa.GatewayPingsTimer = window.setTimeout('kappa.GatewayPingsWatcher()',16000);
}

kappa.NetworkCallback = function(network)
{
	kappa.Network = network;
}

kappa.GatewaysCallback = function(gateways)
{
	kappa.Gateways = gateways;
}

kappa.ManualsCallback = function(manuals)
{
	kappa.Manuals = manuals;
}

kappa.LocalStorageSet = function(key,obj)
{
	var local = localStorage.getItem('kappa') ? JSON.parse(localStorage.getItem('kappa')) : new Object();
	
	local[ key ] = obj;

	localStorage.setItem('kappa',JSON.stringify(local));
}

kappa.LocalStorageGet = function(key)
{
	var local = localStorage.getItem('kappa') ? JSON.parse(localStorage.getItem('kappa')) : new Object();

	return local[ key ];
}

kappa.TIPad = function(num,len)
{
	var nbsp = String.fromCharCode(160);
	
	while (num.length < len) num = nbsp + num;

	return num;
}

kappa.NiceNumber = function(num)
{
	return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}

kappa.IPPad = function(digit)
{
	if (digit.length == 1) return '00' + digit;
	if (digit.length == 2) return '0'  + digit;
	
	return digit;
}

kappa.IP2Bin = function(ip)
{
	var parts = ip.split('.');
	if (parts.length != 4) return 0;
	
	var bin = (parseInt(parts[ 0 ]) << 24)
			+ (parseInt(parts[ 1 ]) << 16)
		 	+ (parseInt(parts[ 2 ]) <<  8)
		 	+ (parseInt(parts[ 3 ]) <<  0)
		 	;
		 
	return bin;
}

kappa.IPZero = function(ip)
{	
	var bin = ip.length ? IP2Bin(ip) : ip;
	
	var zero = kappa.IPPad(((bin >> 24) & 0xff).toString())
			 + "."
			 + kappa.IPPad(((bin >> 16) & 0xff).toString())
			 + "."
			 + kappa.IPPad(((bin >>  8) & 0xff).toString())
			 + "."
			 + kappa.IPPad(((bin >>  0) & 0xff).toString())
			 ;

	return zero;
}

kappa.CenterChanged = function()
{
	var center = kappa.map.getCenter();
	kappa.LocalStorageSet('center_lat',center.lat());
	kappa.LocalStorageSet('center_lon',center.lng());
}

kappa.ZoomChanged = function()
{
	var zoom = kappa.map.getZoom();
	kappa.LocalStorageSet('zoom',zoom);
	
	if (kappa.Segpoints)
	{
		var minendcount = 9999999999;

		if (zoom >=  7) minendcount = 8192;
		if (zoom >=  8) minendcount = 4096;
		if (zoom >=  9) minendcount = 2048;
		if (zoom >= 10) minendcount = 0;
		
		for (var mkey in kappa.Segpoints)
		{
			var marker  = kappa.Segpoints[ mkey ];
			var visible = (marker.endcount >= minendcount) || marker.ishome || ! marker.isalive;
			
			visible = true;
			
			if (marker.visible != visible)
			{
				marker.setVisible(visible);
				marker.myline.setVisible(visible);
			}
		}
	}
	
	if ((zoom >= 6) != kappa.ExtDetails)
	{
		kappa.ExtDetails = (zoom >= 6);
	
		if (kappa.GatewaysLocs)
		{
			for (var lkey in kappa.GatewaysLocs)
			{
				var location = kappa.GatewaysLocs[ lkey ];
			
				for (var linx in location.gips)
				{
					for (var ginx in location.gips[ linx ])
					{
						var extpoint = location.gips[ linx ][ ginx ];
					
						extpoint.line.setVisible  (kappa.ExtDetails || ! extpoint.isalive);
						extpoint.marker.setVisible(kappa.ExtDetails || ! extpoint.isalive);
					}
				}
		
				for (var linx in location.vips)
				{
					for (var vinx in location.vips[ linx ])
					{
						var domain = location.vips[ linx ][ vinx ];

						domain.line.setVisible  (kappa.ExtDetails || ! domain.isalive);
						domain.marker.setVisible(kappa.ExtDetails || ! domain.isalive);
						domain.label.setVisible (kappa.ExtDetails || ! domain.isalive);
					}
				}
			}
		}
	}
}

kappa.Initialize = function() 
{
	var zoom   = kappa.LocalStorageGet('zoom') ? kappa.LocalStorageGet('zoom') : 6;
	var center = new google.maps.LatLng(52.5,10.0);
	
	if ((kappa.LocalStorageGet('center_lat') != null) &&
		(kappa.LocalStorageGet('center_lon') != null))
	{
		center = new google.maps.LatLng(
			kappa.LocalStorageGet('center_lat'),
			kappa.LocalStorageGet('center_lon')
			);
	}
	
	var mapOptions = 
	{
		zoom      : zoom,
		center    : center,
		mapTypeId : google.maps.MapTypeId.ROADMAP
	};

	kappa.map = new google.maps.Map(document.getElementById('map-canvas'),mapOptions);
	
    google.maps.event.addListener(kappa.map,'zoom_changed',kappa.ZoomChanged);
    google.maps.event.addListener(kappa.map,'center_changed',kappa.CenterChanged);
	
	kappa.InitializeMenu();
	kappa.InitializeInfo();
	kappa.InitializeLabel();

	kappa.MapDetails = false;
	kappa.ExtDetails = false;
	
	kappa.NetPoint      = new Array();
	kappa.NetPointColor = new Array();
	
	for (var inx = 1; inx < 8; inx++)
	{
		var grey  = Number(inx * 2).toString(16) + Number(inx * 2).toString(16);
		var color = grey + grey + 'ff';
		
		kappa.NetPointColor.unshift(color);
		
    	kappa.NetPoint.unshift(new google.maps.MarkerImage(
			'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|' + color,
			new google.maps.Size(21,34),
			new google.maps.Point(0,0),
			new google.maps.Point(10,34)));
	}

    kappa.HomePointzIndex = 7000;

	kappa.HomePoint = new google.maps.MarkerImage(
		'http://maps.google.com/mapfiles/kml/pushpin/red-pushpin.png',
        new google.maps.Size(64,64),
        new google.maps.Point(0,0),
        new google.maps.Point(20,60));

	var ps = new google.maps.Size(21,34);
	var or = new google.maps.Point(0,0);
	var an = new google.maps.Point(10,34);
	
    kappa.EndPointzIndex = 5000;
    
	kappa.EndPoint 	     = new google.maps.MarkerImage('img/map.point.green.png',ps,or,an);
	kappa.EndPointDead   = new google.maps.MarkerImage('img/map.point.green-red.png',ps,or,an);
	kappa.EndPointEvent  = new google.maps.MarkerImage('img/map.point.green-yellow.png',ps,or,an);

    kappa.SegPointZoom   = 7;
    kappa.SegPointzIndex = 3000;
    
    kappa.SegPoint 		 = new google.maps.MarkerImage('img/map.point.lt-green.png',ps,or,an);
    kappa.SegPointDead 	 = new google.maps.MarkerImage('img/map.point.lt-green-red.png',ps,or,an);
    kappa.SegPointEvent  = new google.maps.MarkerImage('img/map.point.lt-green-yellow.png',ps,or,an);
   
    kappa.UplPointzIndex = 5000;
    
    kappa.UplPoint		 = new google.maps.MarkerImage('img/map.point.lt-violett.png',ps,or,an);
    kappa.UplPointDead	 = new google.maps.MarkerImage('img/map.point.lt-violett-red.png',ps,or,an);
    kappa.UplPointEvent	 = new google.maps.MarkerImage('img/map.point.lt-violett-yellow.png',ps,or,an);
        
    kappa.LocPointzIndex = 3000;
    
    kappa.LocPoint		 = new google.maps.MarkerImage('img/map.point.blue.png',ps,or,an);
    kappa.LocPointDead	 = new google.maps.MarkerImage('img/map.point.blue-red.png',ps,or,an);
    kappa.LocPointSlow	 = new google.maps.MarkerImage('img/map.point.blue-orange.png',ps,or,an);
    kappa.LocPointEvent	 = new google.maps.MarkerImage('img/map.point.blue-orange.png',ps,or,an);
        
    kappa.GwyPointzIndex = 4000;
    
    kappa.NixPoint 		 = new google.maps.MarkerImage('img/map.point.trans.png',ps,or,an);
    kappa.GwyPoint		 = new google.maps.MarkerImage('img/map.point.violett.png',ps,or,an);
    kappa.GwyPointDead	 = new google.maps.MarkerImage('img/map.point.violett-red.png',ps,or,an);
    kappa.GwyPointSlow	 = new google.maps.MarkerImage('img/map.point.violett-orange.png',ps,or,an);
    kappa.GwyPointEvent	 = new google.maps.MarkerImage('img/map.point.violett-yellow.png',ps,or,an);

    kappa.VipPointzIndex = 6000;
    
	kappa.VipPoint		 = new google.maps.MarkerImage('img/map.point.dk-green.png',ps,or,an);
	kappa.VipPointDead	 = new google.maps.MarkerImage('img/map.point.dk-green-red.png',ps,or,an);
	kappa.VipPointEvent	 = new google.maps.MarkerImage('img/map.point.dk-green-orange.png',ps,or,an);

    kappa.RouterPoint = new google.maps.MarkerImage(
    	'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|' + '8888dd',
        new google.maps.Size(21,34),
        new google.maps.Point(0,0),
        new google.maps.Point(10,34));
    
    kappa.SignalLive = document.createElement('audio');
    kappa.SignalLive.src = 'snd/sound.synth.wav';
    kappa.SignalLive.preload = true;

    kappa.SignalSlow = document.createElement('audio');
    kappa.SignalSlow.src = 'snd/sound.nokia.wav';
    kappa.SignalSlow.preload = true;
    
    kappa.SignalDead = document.createElement('audio');
    kappa.SignalDead.src = 'snd/sound.sirene.wav';
    kappa.SignalDead.preload = true;

	kappa.EndpointsNopingsRefresh();
	kappa.EplinksNopingsRefresh();
	kappa.UplinksNopingsRefresh();
	
	if (kappa.selector == 'ep') window.setTimeout('kappa.EndpointsRequest()',1000);
	if (kappa.selector == 'bb') window.setTimeout('kappa.BackbonesRequest()',1000);
}

kappa.setSignal = function(signal)
{
	if (signal > kappa.Signal) kappa.Signal = signal;
}

kappa.Round = function(val)
{
	return Math.floor(val * 1000.0) / 1000.0;
}

kappa.CityWeights =
{
	'Berlin'  : 2,
	'Leipzig' : 2,
	'Hamburg' : 2
} 

kappa.GatewaysDraw = function()
{
	if (kappa.GatewaysLocs)
	{
		for (var lkey in kappa.GatewaysLocs)
		{
			kappa.GatewaysLocs[ lkey ].vcnt[ 0 ] = 0;
			kappa.GatewaysLocs[ lkey ].vcnt[ 1 ] = 0;
		}
	}
	else
	{
		kappa.GatewaysLocs = new Object();
	}
	
	var duplicates = new Object();
	
	for (var gip in kappa.Gateways)
	{
		var extpoint = kappa.Gateways[ gip ];
		if (! extpoint.loc) continue;

		var extlat = kappa.Round(extpoint.lat);
		var extlon = kappa.Round(extpoint.lon);
		
		var markerkey = extlat + '/' + extlon;
		var location  = kappa.GatewaysLocs[ markerkey ];
		
		if (! location) 
		{
			location = new Object();
			
			location.maplat  = extlat;
			location.maplon  = extlon;
			
			var parts = extpoint.loc.split(',');
			location.title = parts[ 2 ];

			location.gips      = new Array();
			location.gips[ 0 ] = new Object();
			location.gips[ 1 ] = new Object();
			
			location.gcnt      = new Array();
			location.gcnt[ 0 ] = 0;
			location.gcnt[ 1 ] = 0;
			
			location.vcnt      = new Array();
			location.vcnt[ 0 ] = 0;
			location.vcnt[ 1 ] = 0;
			
			location.marker = new google.maps.Marker
			({
				map      : kappa.map,
				position : new google.maps.LatLng(location.maplat,location.maplon),
				visible  : true,
				zIndex	 : kappa.LocPointzIndex,
				icon	 : kappa.LocPoint,
				title    : location.title
			});
			
			location.isalive = true;
			
			kappa.GatewaysLocs[ markerkey ] = location;
		}
		
		if (! extpoint.gips) extpoint.gips = new Object();
		if (! extpoint.vips) extpoint.vips = new Object();
		if (! extpoint.vcmp) extpoint.vcmp = '';
		if (! extpoint.vcnt) extpoint.vcnt = 0;

		for (var einx in extpoint.extern)
		{
			var parts  = extpoint.extern[ einx ].split(',');
			var extip  = parts[ 0 ];
			var domain = parts[ 1 ];
			var alexa  = parts[ 2 ];
			
			var wanted = kappa.Manuals.domains[ domain ] && (kappa.Manuals.domains[ domain ] == 2);
			var isdead = (kappa.GatewayPings.domain[ domain ] == -1);			
			var isslow = (kappa.GatewayPings.domain[ domain ] > 500);			
			
			if (wanted || isdead || isslow)
			{
				if (! extpoint.vips[ domain ])
				{
					var vip = new Object();
				
					vip.extip    = extip;
					vip.domain   = domain;
					vip.alexa    = alexa;
					vip.extpoint = extpoint;
				
					extpoint.vips[ domain ] = vip;
				
					extpoint.vcmp += domain;
					extpoint.vcnt += 1;
				}
			}
		}
		
		//
		// Check for duplicate and unused gateways.
		//
		
		if (extpoint.vcnt < 1) continue;
		
		if (duplicates[ extpoint.vcmp ]) continue;		
		duplicates[ extpoint.vcmp ] = extpoint;
		
		if (location.gips[ 0 ][ gip ])
		{
			location.vcnt[ 0 ] += extpoint.vcnt;
						
			continue;
		}
		
		if (location.gips[ 1 ][ gip ])
		{
			location.vcnt[ 1 ] += extpoint.vcnt;
				
			continue;
		}
		
		//
		// Find a new place for gateway on map.
		//
		
		if ((location.gcnt[ 0 ] < 10) &&
			(location.vcnt[ 0 ] <= location.vcnt[ 1 ]))
		{
			location.gips[ 0 ][ gip ] = extpoint;
			location.vcnt[ 0 ] += extpoint.vcnt;
			location.gcnt[ 0 ] += 1;
			
			continue;
		}
		
		if ((location.gcnt[ 1 ] < 10) &&
			(location.vcnt[ 1 ] <= location.vcnt[ 0 ]))
		{
			location.gips[ 1 ][ gip ] = extpoint;
			location.vcnt[ 1 ] += extpoint.vcnt;
			location.gcnt[ 1 ] += 1;
			
			continue;
		}
	}
	
	for (var lkey in kappa.GatewaysLocs)
	{
		var location = kappa.GatewaysLocs[ lkey ];
		
		location.vips = new Array();
		location.vips[ 0 ] = new Array();
		location.vips[ 1 ] = new Array();
		
		var localive = true;
		var locslow  = false;
		
		for (var linx in location.gips)
		{
			var gips    = location.gips[ linx ];
			var degree  = (0.55 * 3.14) / location.gcnt[ linx ];
			var xradius = 0.25;
			var yradius = 0.60;
			var startdg = (1.5 * 3.14) - (((location.gcnt[ linx ] - 1) * 0.5) * degree);
		 	
		 	if (linx == '0') startdg -= 3.14;
		 	
			for (var gip in gips)
			{
				extpoint = gips[ gip ];
			
				for (var vkey in extpoint.vips)
				{
					location.vips[ linx ].push(extpoint.vips[ vkey ]);
				}
				
				extpoint.maplat = kappa.Round(location.maplat + (Math.cos(startdg) * xradius));
				extpoint.maplon = kappa.Round(location.maplon + (Math.sin(startdg) * yradius));

				startdg += degree;

				var alive    = (kappa.GatewayPings.gateway[ gip ] != -1);
				var slow     = (kappa.GatewayPings.gateway[ gip ] > 80);
				var ping	 =  kappa.GatewayPings.gateway[ gip ];
				
				var position = new google.maps.LatLng(extpoint.maplat,extpoint.maplon);
				var icon	 = alive ? (slow ? kappa.GwyPointSlow : kappa.GwyPoint) : kappa.GwyPointDead;
				var text	 = gip + (slow ? (' > ' + ping + 'ms'): '');
				
				var path = 
				[
					new google.maps.LatLng(extpoint.maplat,extpoint.maplon),
					new google.maps.LatLng(location.maplat,location.maplon)
				];
				
				if (extpoint.marker)
				{
					//
					// Old gateway.
					//
					
					if ((extpoint.oldlat != extpoint.maplat) ||
						(extpoint.oldlon != extpoint.maplon))
					{
						//
				 		// Gateway place changed.
				 		//

						extpoint.marker.setPosition(position);
						extpoint.line.setPath(path);
					}
					
					if ((extpoint.isalive != alive) || (extpoint.isslow != slow))
				 	{	
				 		//
				 		// Gateway status changed.
				 		//
				 		
						extpoint.marker.setZIndex(kappa.GwyPointzIndex++);
						extpoint.marker.setIcon(icon);
						
						extpoint.marker.setVisible(kappa.ExtDetails || slow || ! alive);
						
						extpoint.line.setVisible(kappa.ExtDetails || slow || ! alive);
				 		
				 		var tag = alive ? 'Live' : 'Died';
			
						if (extpoint.isslow != slow)
						{
							tag  = slow ? 'Slow' : 'Fast';
							text = gip + (slow ? ' > ' : ' < ') + ping + 'ms';
						}
						
				 		var itext = tag + ': ' + text;
						kappa.Info.setText(itext,gip);
					}
				}
				else
				{
					//
					// New gateway.
					//
					
					extpoint.marker = new google.maps.Marker
					({
						map      : kappa.map,
						position : position,
						visible  : kappa.ExtDetails || slow || ! alive,
						zIndex	 : kappa.GwyPointzIndex++,
						icon	 : icon,
						title    : gip
					});
						
					extpoint.line = new google.maps.Polyline
					({
						map      	  : kappa.map,
						path          : path,
						visible       : kappa.ExtDetails || slow || ! alive,
						zIndex        : 0,
						strokeColor   : '#8888ff',
						strokeWeight  : 2.0,
						strokeOpacity : 1.0
					});

					if (slow || ! alive)
					{
				 		var itext = (alive ? 'Slow' : 'Died') + ': ' + text;
						kappa.Info.setText(itext,gip);
					}
				}

				extpoint.isalive = alive;
				extpoint.isslow  = slow;

				if (! extpoint.isalive) localive = false;
				if (  extpoint.isslow ) locslow  = true;

				extpoint.oldlat = extpoint.maplat;
				extpoint.oldlon = extpoint.maplon;
			}
		}
		
		if ((location.isalive != localive) || (location.isslow != locslow))
		{
			var icon = localive ? (locslow ? kappa.LocPointSlow : kappa.LocPoint) : kappa.LocPointDead;
			
			location.marker.setZIndex(kappa.LocPointzIndex++);
			location.marker.setIcon(icon);

			location.isalive = localive;
			location.isslow  = locslow;
		}
	}
	
	for (var lkey in kappa.GatewaysLocs)
	{
		var location = kappa.GatewaysLocs[ lkey ];

		for (var linx in location.vips)
		{
			var vips    = location.vips[ linx ];
			var degree  = (0.55 * 3.14) / vips.length;
			var xradius = 0.25 * 2;
			var yradius = 0.60 * 2;
			var startdg = (1.5 * 3.14) - (((vips.length - 1) * 0.5) * degree);
			
			if (linx == '0') startdg -= 3.14;

			for (var vinx in vips)
			{
				vip = vips[ vinx ];
			
				vip.maplat = kappa.Round(location.maplat + (Math.cos(startdg) * xradius));
				vip.maplon = kappa.Round(location.maplon + (Math.sin(startdg) * yradius));
				
				startdg += degree;

				var alive    = (kappa.GatewayPings.domain[ vip.domain ] != -1);
				var slow     = (kappa.GatewayPings.domain[ vip.domain ] > 500);
				var ping	 =  kappa.GatewayPings.domain[ vip.domain ];
				
				var position = new google.maps.LatLng(vip.maplat,vip.maplon);
				var bgColor	 = alive ? (slow ? '#ff8800' : '#ffffff') : '#ff8888';
				var text	 = vip.domain + (slow ? (' > ' + ping + 'ms'): '');
				
				var path = 
				[
					new google.maps.LatLng(vip.maplat,vip.maplon),
					new google.maps.LatLng(vip.extpoint.maplat,vip.extpoint.maplon)
				];
				
				if (vip.marker)
				{
					//
					// Old domain.
					//
					
					if ((vip.oldlat != vip.maplat) ||
						(vip.oldlon != vip.maplon))
					{
						//
				 		// Domain place changed.
				 		//

						vip.marker.setPosition(position);
						vip.line.setPath(path);
					}
					
					if ((vip.isalive != alive) || (vip.isslow != slow))
				 	{	
				 		//
				 		// Domain status changed.
				 		//
				 		
						vip.marker.setZIndex(kappa.GwyPointzIndex++);
						
						vip.label.setZIndex(kappa.GwyPointzIndex++);
						vip.label.setBGColor(bgColor);
						vip.label.setText(text);
						
						vip.marker.setVisible(kappa.ExtDetails || slow || ! alive);
						vip.label.setVisible (kappa.ExtDetails || slow || ! alive);
						vip.line.setVisible  (kappa.ExtDetails || slow || ! alive);

						vip.label.draw();

				 		var tag = alive ? 'Live' : 'Died';
			
						if (vip.isslow != slow)
						{
							tag  = slow ? 'Slow' : 'Fast';
							text = vip.domain + (slow ? ' > ' : ' < ') + ping + 'ms';
						}
						
				 		var itext = tag + ': ' + text;
						kappa.Info.setText(itext,vip.domain);
					}
				}
				else
				{
					//
					// New domain.
					//

					vip.marker = new google.maps.Marker
					({
						map      : kappa.map,
						position : position,
						visible  : kappa.ExtDetails || slow || ! alive,
						zIndex	 : kappa.GwyPointzIndex++,
						icon	 : kappa.NixPoint,
						title    : vip.domain + ' (alexa=' + vip.alexa + ')'
					});
			
					vip.label = new kappa.Label
					({
						map     : kappa.map,
						visible : kappa.ExtDetails || slow || ! alive,
						zIndex	: kappa.GwyPointzIndex++,
						bgColor	: bgColor,
						text    : text
					});
			
					vip.label.bindTo('position',vip.marker,'position');
					
					vip.line = new google.maps.Polyline
					({
						map      	  : kappa.map,
						path          : path,
						visible       : kappa.ExtDetails || slow || ! alive,
						zIndex        : 0,
						strokeColor   : '#8888ff',
						strokeWeight  : 2.0,
						strokeOpacity : 1.0
					});
					
					if (slow || ! alive)
					{
				 		var itext = (alive ? 'Slow' : 'Died') + ': ' + text;
						kappa.Info.setText(itext,vip.domain);
					}
				}
				
				vip.oldlat = vip.maplat;
				vip.oldlon = vip.maplon;

				vip.isalive = alive;
				vip.isslow  = slow;
			}
		}
	}
}

kappa.NetworkDraw = function()
{
	kappa.NetworkMarkers   = new Object();
	kappa.NetworkLines     = new Object();
	kappa.NetworkEndpoints = new Object();
	kappa.NetworkVips	   = new Object();
	
	for (var rip in kappa.Network)
	{
		var network = kappa.Network[ rip ];
		
		var netlat = network.lat;
		var netlon = network.lon;
		var nettyp = network.typ;

		var markerkey = netlat + '/' + netlon;
		var marker    = kappa.NetworkMarkers[ markerkey ];

		if (nettyp == 0) continue;
		if (nettyp == 5) continue;
		
		if (nettyp == 6) 
		{
			kappa.NetworkVips[ markerkey ] = true;
			
			continue;
		}
		
		/*
		if (marker)
		{
			marker = kappa.NetworkMarkers[ markerkey ];
			
			marker.setIcon(kappa.NetPoint[ nettyp ])
			marker.setTitle(marker.getTitle() + '\n' + rip + ' ' + nettyp);
		}
		else
		{
			marker = new google.maps.Marker
			({
				position : new google.maps.LatLng(netlat,netlon),
				map      : kappa.map,
				icon	 : kappa.NetPoint[ nettyp ],
				zIndex	 : 100000 + nettyp,
				title    : rip + ' ' + nettyp
			});
			
			kappa.NetworkMarkers[ markerkey ] = marker;
		}

		google.maps.event.addListener(marker,'click',kappa.NetworkClick);

		*/
		
		for (var linx in network.locs)
		{
			var loc = network.locs[ linx ];
			
			var parts = loc.split(',');

			var endlat = kappa.Round(parseFloat(parts[ 3 ])); 
			var endlon = kappa.Round(parseFloat(parts[ 4 ]));
			
			var endkey = endlat + '/' + endlon;
			kappa.NetworkEndpoints[ endkey ] = true;
		
			var linekey = markerkey + '-' + endkey;
			var line    = kappa.NetworkLines[ linekey ];
			
			if (! line)
			{
				var path = 
				[
					new google.maps.LatLng(netlat,netlon),
					new google.maps.LatLng(endlat,endlon)
				];
			
				line = new google.maps.Polyline
				({
					map      	  : kappa.map,
					path          : path,
					visible       : kappa.MapDetails,
					strokeColor   : '#' + kappa.NetPointColor[ nettyp ],
					strokeWeight  : 3.0,
					strokeOpacity : 1.0
				});
				
				kappa.NetworkLines[ linekey ] = line;
			}
			
			line.isdetail     = (nettyp <  6);
			line.visible	  = (nettyp >= 6) || kappa.MapDetails;
			line.strokeColor  = '#' + kappa.NetPointColor[ nettyp ];
		}
	}
}

kappa.HomeDrag = function()
{
	var latlon = kappa.Mystuff.marker.getPosition();
	
	kappa.Mystuff.path[ 1 ] = latlon;
	
	kappa.Mystuff.line.setPath(kappa.Mystuff.path);
}

kappa.HomeDragend = function()
{
	var latlon = kappa.Mystuff.marker.getPosition();
	
	kappa.LocalStorageSet('home_lat',latlon.lat());
	kappa.LocalStorageSet('home_lon',latlon.lng());
}

kappa.EndpointClick = function()
{
	var copyme = this.title.split('\n');
	
	copyme.shift();
	
	for (var cinx in copyme)
	{
		var funz = copyme[ cinx ].substr(0,15).split('.');
		
		for (var finx in funz)
		{
			while ((funz[ finx ].length > 1) && (funz[ finx ].charAt(0) == '0'))
			{
				funz[ finx ] = funz[ finx ].substr(1);
			}
		}	
		
		copyme[ cinx ] = funz.join('.');	
	}
	
	copyme = copyme.join('\n');
	
	window.prompt('Copy Me...',copyme);
}

kappa.NetworkClick = function()
{
	var copyme = this.title.split(' ');
	
	copyme = copyme[ 0 ];
	
	window.prompt('Copy Me...',copyme);
}

kappa.RouterClick = function()
{
	var copyme = this.title;
	
	window.prompt('Copy Me...',copyme);
}

kappa.HomeDraw = function(snet,seg)
{
	if ((kappa.Mystuff.myip < seg.from) || (kappa.Mystuff.myip > seg.last))
	{
		return false;
	}

	var homelat = kappa.Round(seg.loc.lat);
	var homelon = kappa.Round(seg.loc.lon);
	
	if (kappa.LocalStorageGet('home_lat')) homelat = kappa.LocalStorageGet('home_lat');
	if (kappa.LocalStorageGet('home_lon')) homelon = kappa.LocalStorageGet('home_lon');
	
	var marker = new google.maps.Marker
	({
		map       : kappa.map,
		position  : new google.maps.LatLng(homelat,homelon),
		zIndex	  : kappa.HomePointzIndex,
		icon	  : kappa.HomePoint,
		visible   : true,
		draggable : true,
		title     : seg.loc.city + '\n' + kappa.Mystuff.myip
	});

	google.maps.event.addListener(marker,'drag',kappa.HomeDrag);
	google.maps.event.addListener(marker,'dragend',kappa.HomeDragend);
	
	var path = 
	[
		new google.maps.LatLng(seg.loc.lat,seg.loc.lon),
		new google.maps.LatLng(homelat,homelon)
	];

	var line = new google.maps.Polyline
	({
		map      	  : kappa.map,
		path          : path,
		visible       : true,
		strokeColor   : '#00cc00',
		strokeOpacity : 1.0,
		strokeWeight  : 2
	});
	
	kappa.Mystuff.path	  = path;
	kappa.Mystuff.marker  = marker;
	kappa.Mystuff.line    = line;
	kappa.Mystuff.subnet  = snet;
	kappa.Mystuff.segment = seg;
	
	return true;
}

//
// Nopings section.
//

kappa.UplinksNopingsCallback = function(data)
{
	kappa.UplinksScript.parentNode.removeChild(kappa.UplinksScript);
	window.clearTimeout(kappa.UplinksNopingsTimer);

	if ((! kappa.UplinksNopings) || (kappa.UplinksNopings.stamp != data.stamp))
	{
		kappa.UplinksNopings = data;
	}
		
	window.setTimeout('kappa.UplinksNopingsRefresh()',2000);
}

kappa.UplinksNopingsRefresh = function()
{
	kappa.UplinksScript = document.createElement('script');
	kappa.UplinksScript.src = kappa.country + '/' + kappa.provider + '/nopings.uplinks.js?rnd=' + Math.random();
	document.body.appendChild(kappa.UplinksScript);
	
	kappa.UplinksNopingsTimer = window.setTimeout('kappa.UplinksNopingsRefresh()',16000);
}

kappa.EplinksNopingsCallback = function(data)
{
	kappa.EplinksScript.parentNode.removeChild(kappa.EplinksScript);
	window.clearTimeout(kappa.EplinksNopingsTimer);

	if ((! kappa.EplinksNopings) || (kappa.EplinksNopings.stamp != data.stamp))
	{
		kappa.EplinksNopings = data;
	}
		
	window.setTimeout('kappa.EplinksNopingsRefresh()',2000);
}

kappa.EplinksNopingsRefresh = function()
{
	kappa.EplinksScript = document.createElement('script');
	kappa.EplinksScript.src = kappa.country + '/' + kappa.provider + '/nopings.eplinks.js?rnd=' + Math.random();
	document.body.appendChild(kappa.EplinksScript);
	
	kappa.EplinksNopingsTimer = window.setTimeout('kappa.EplinksNopingsRefresh()',16000);
}

kappa.EndpointsNopingsCallback = function(data)
{
	kappa.EndpointsScript.parentNode.removeChild(kappa.EndpointsScript);
	window.clearTimeout(kappa.EndpointsNopingsTimer);

	if ((! kappa.EndpointsNopings) || (kappa.EndpointsNopings.stamp != data.stamp))
	{
		kappa.EndpointsNopings = data;
	}
		
	window.setTimeout('kappa.EndpointsNopingsRefresh()',2000);
}

kappa.EndpointsNopingsRefresh = function()
{
	kappa.EndpointsScript = document.createElement('script');
	kappa.EndpointsScript.src = kappa.country + '/' + kappa.provider + '/nopings.endpoints.js?rnd=' + Math.random();
	document.body.appendChild(kappa.EndpointsScript);
	
	kappa.EndpointsNopingsTimer = window.setTimeout('kappa.EndpointsNopingsRefresh()',16000);
}

//
// Backbones section.
//

kappa.BackbonesRequest = function()
{
	kappa.backbonestoload = 0;
	kappa.backbonesloaded = 0;
	
	var uplload = document.createElement('script');
	uplload.src = kappa.country + '/' + kappa.provider + '/' + 'uplinks.map.js' + '?rnd=' + Math.random();
	document.body.appendChild(uplload);
		
	kappa.backbonestoload++;
}

kappa.UplinksCallback = function(uplinks)
{
	kappa.Uplinks = uplinks;
	
	if (++kappa.backbonesloaded == kappa.backbonestoload) 
	{
		kappa.BackbonesDraw();
		kappa.ZoomChanged();
    	kappa.CenterChanged();
	}
} 

kappa.BackbonesDraw = function()
{
	kappa.Uplpoints = new Object();
	
	for (var uinx in kappa.Uplinks)
	{		
		var uplink = kappa.Uplinks[ uinx ];
		
		var upllat  = kappa.Round(uplink.loc.lat);
		var upllon  = kappa.Round(uplink.loc.lon);
		
		var isalive = ! (kappa.UplinksNopings && kappa.UplinksNopings.nopings[ uplink.ip ]);
		
		var markerkey = upllat + '/' + upllon;
		var uplmarker = kappa.Uplpoints[ markerkey ];
				
		if (! uplmarker)
		{
			uplmarker = new google.maps.Marker
			({
				map      : kappa.map,
				position : new google.maps.LatLng(upllat,upllon),
				zIndex	 : kappa.UplPointzIndex + (isalive ? 0 : 10000),
				icon	 : isalive ? kappa.UplPoint : kappa.UplPointDead,
				visible  : true,
				title    : uplink.loc.city
			});
			
			kappa.Uplpoints[ markerkey ] = uplmarker;
			
			uplmarker.isalive  = isalive;
			uplmarker.isrouter = true;
			uplmarker.subnets  = 0;
		}
		
		var nl = (uplmarker.subnets % 3) ? '\n' : '\n';
		var ac = isalive ? '+' : '-';
		var ip = uplink.ip.replace(/^001.000./,'???.???.');
		
		uplmarker.setTitle(uplmarker.getTitle() + nl + ip + ac);
		uplmarker.subnets++;
		
		if (! isalive) 
		{	
			uplmarker.isalive = false;

			uplmarker.setZIndex(kappa.UplPointzIndex + 10000);
			uplmarker.setIcon(kappa.UplPointDead);
		}
	}
}

//
// Endpoints section.
//

kappa.EndpointsRequest = function()
{
	kappa.endpointstoload = 0;
	kappa.endpointsloaded = 0;
	
	for (var ninx in kappa.ISPList[ kappa.country ][ kappa.provider ].nets)
	{
		var endload = document.createElement('script');
		
		endload.src = kappa.country 
					+ '/'
					+ kappa.provider
					+ '/'
					+ kappa.ISPList[ kappa.country ][ kappa.provider ].nets[ ninx ]
					+ '.map.js'
					+ '?rnd=' 
					+ Math.random();
					;
	
		document.body.appendChild(endload);
		
		kappa.endpointstoload++;
	}
}

kappa.EndpointsCallback = function(endpoints)
{
	if (! kappa.Endpoints) kappa.Endpoints = new Array();
	
	kappa.Endpoints = kappa.Endpoints.concat(endpoints);
	
	if (++kappa.endpointsloaded == kappa.endpointstoload) 
	{
		kappa.EndpointsDraw();
		kappa.ZoomChanged();
    	kappa.CenterChanged();
	}
} 

kappa.EndpointsDraw = function()
{
	kappa.Fixpoints = new Object();
	kappa.Segpoints = new Object();
	
	for (var sinx in kappa.Endpoints)
	{
		var snet = kappa.Endpoints[ sinx ];
		
		var fixlat  = kappa.Round(snet.loc.lat);
		var fixlon  = kappa.Round(snet.loc.lon);
		var fixips  = kappa.IP2Bin(snet.bc) - kappa.IP2Bin(snet.ip) + 1;
		var fixnum  = kappa.NiceNumber(snet.pc) + '/' + kappa.NiceNumber(fixips);

		var isalive = (snet.pc < 15) || ! (kappa.EndpointsNopings && kappa.EndpointsNopings.nopings[ snet.ip ]);
		
		var markerkey = fixlat + '/' + fixlon;
		var snmarker  = kappa.Fixpoints[ markerkey ];
				
		if (snmarker)
		{
			snmarker.netcount += 1;
			snmarker.endcount += fixips;
			snmarker.actcount += snet.pc;
		}
		else
		{
			snmarker = new google.maps.Marker
			({
				map      : kappa.map,
				position : new google.maps.LatLng(fixlat,fixlon),
				zIndex	 : kappa.EndPointzIndex + (isalive ? 0 : 10000),
				icon	 : isalive ? kappa.EndPoint : kappa.EndPointDead,
				visible  : true,
				title    : snet.loc.city + '=@@@'
			});
			
			kappa.Fixpoints[ markerkey ] = snmarker;
    		
    		google.maps.event.addListener(snmarker,'click',kappa.EndpointClick);
			
			snmarker.netcount = 1;
			snmarker.endcount = fixips;
			snmarker.actcount = snet.pc;
			
			snmarker.isalive  = isalive;
			snmarker.issubnet = true;
		}
		
		var ac = isalive ? '+' : '-';
		
		snmarker.setTitle(snmarker.getTitle() + '\n' + snet.ip + '-' + snet.bc + '=' + fixnum + ac);
				
		if (! isalive) 
		{	
			snmarker.isalive = false;

			snmarker.setZIndex(kappa.EndPointzIndex + 10000);
			snmarker.setIcon(kappa.EndPointDead);
		}
		
		for (var ginx in snet.segs)
		{
			var seg = snet.segs[ ginx ];

			var seglat  = kappa.Round(seg.loc.lat);
			var seglon  = kappa.Round(seg.loc.lon);
			var segips  = kappa.IP2Bin(seg.last) - kappa.IP2Bin(seg.from) + 1;
			var segnum  = kappa.NiceNumber(seg.pc) + '/' + kappa.NiceNumber(segips);
			
			var ishome  = kappa.HomeDraw(snet,seg);
			var isalive = true;
			
			var from = kappa.IP2Bin(seg.from);
			var last = kappa.IP2Bin(seg.last);
			
			for (var iptest = from; iptest <= last; iptest += 256)
			{
				if ((seg.pc < 15) || ! (kappa.EndpointsNopings && kappa.EndpointsNopings.nopings[ kappa.IPZero(iptest) ])) continue;
				
				isalive = false;
			}
			
			if ((Math.abs(seglat - fixlat) < 0.500) && 
				(Math.abs(seglon - fixlon) < 0.500))
			{
				//continue;
			}
			
			if ((Math.abs(seglat - fixlat) < 0.001) && 
				(Math.abs(seglon - fixlon) < 0.001))
			{
				//
				// Segment is also fixpoint. Move a little.
				//
				
				seglat = fixlat + 0.00;
				seglon = fixlon + 0.03;
			}
			else
			{
				var weight = kappa.CityWeights[ seg.loc.city ] ? kappa.CityWeights[ seg.loc.city ] : 20;
			
				seglat = kappa.Round(((seglat * (weight - 1)) + fixlat) / weight);
				seglon = kappa.Round(((seglon * (weight - 1)) + fixlon) / weight);
			}
			
			/*
			if ((Math.abs(seglat - fixlat) < 3.001) && 
				(Math.abs(seglon - fixlon) < 3.001))
			{
				continue;
			}
			*/
			
			var markerkey = seglat + '/' + seglon;
			var sgmarker  = kappa.Segpoints[ markerkey ];

			if (sgmarker)
			{
				sgmarker.netcount += 1;
				sgmarker.endcount += segips;
				sgmarker.actcount += seg.pc;
			}
			else
			{
				sgmarker = new google.maps.Marker
				({
					map      : kappa.map,
					position : new google.maps.LatLng(seglat,seglon),
					zIndex	 : kappa.SegPointzIndex + (isalive ? 0 : 10000),
					icon	 : isalive ? kappa.SegPoint : kappa.SegPointDead,
					visible  : false,
					title    : seg.loc.city + '=@@@'
				});
				
				kappa.Segpoints[ markerkey ] = sgmarker;
				
				google.maps.event.addListener(sgmarker,'click',kappa.EndpointClick);

				sgmarker.netcount  = 1;
				sgmarker.endcount  = segips;
				sgmarker.actcount  = seg.pc;
				
				sgmarker.isalive   = isalive;
				sgmarker.issegment = true;
			}
			
			var ac = isalive ? '+' : '-';
			
			sgmarker.setTitle(sgmarker.getTitle() + '\n' + seg.from + '-' + seg.last + '=' + segnum + ac);
				
			if (ishome) sgmarker.ishome = true;
			
			if (! isalive) 
			{	
				sgmarker.isalive = false;

				sgmarker.setZIndex(kappa.SegPointzIndex + 10000);
				sgmarker.setIcon(kappa.SegPointDead);
				
				snmarker.setZIndex(kappa.EndPointzIndex + 10000);
				snmarker.setIcon(kappa.EndPointDead);
			}
			
			var path = 
			[
				new google.maps.LatLng(fixlat,fixlon),
				new google.maps.LatLng(seglat,seglon)
			];
			
			if (sgmarker.mypath) path = sgmarker.mypath;
			
			if (seg.via)
			{
				var vialat = kappa.Round(((seg.via.lat * (weight - 1)) + fixlat) / weight);
				var vialon = kappa.Round(((seg.via.lon * (weight - 1)) + fixlon) / weight);
		
				path[ 0 ] = new google.maps.LatLng(vialat,vialon);
			}
	
			if (sgmarker.myline)
			{
				sgmarker.myline.setPath(path);
			}
			else
			{
				var line = new google.maps.Polyline
				({
					map      	  : kappa.map,
					path          : path,
					visible       : false,
					strokeColor   : '#007700',
					strokeOpacity : 1.0,
					strokeWeight  : 2
				});

				sgmarker.myline = line;
				sgmarker.mypath = path;
			}
		}
	}
	
	for (var key in kappa.Fixpoints)
	{
		var marker = kappa.Fixpoints[ key ];
		var title  = marker.getTitle();
		var total  = kappa.NiceNumber(marker.actcount) + "/" + kappa.NiceNumber(marker.endcount)

		marker.setTitle(title.replace(/@@@/,total));
	}
	
	for (var key in kappa.Segpoints)
	{
		var marker = kappa.Segpoints[ key ];
		var title  = marker.getTitle();
		var total  = kappa.NiceNumber(marker.actcount) + "/" + kappa.NiceNumber(marker.endcount)

		marker.setTitle(title.replace(/@@@/,total));
	}
}
