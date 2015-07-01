# pfsense-status-gateways-json

Module retrieves pfSense 2.x Dashboard Gateway Status and Network Traffic Graphs data in JSON format.

"status" can be "force_down", "down", "loss" (for packet-loss warning), "delay" (for latency
warning), or "okay" for online.

**WARNING**: This module bypasses the normal pfSense dashboard login, so it should be set with a different "key" of your choosing in the PHP code.  The default is shown below:

`
http://pfSenseIP/status_gateways_json.php?key=pfsense 
`

Since there is additional overhead to calculate data rates for
each connection ("inKbps", "outKbps"), it can be optionally
requested via an additional parameter as indicated below:

`
http://pfSenseIP/status_gateways_json.php?key=pfsense&rates=1 
`

To install, simply change the "key" in the code below, then drop
the status_gateways_json.php file into your pfSense "/usr/local/www"
directory and you are set to go! 
