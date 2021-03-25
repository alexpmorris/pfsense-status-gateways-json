<?php
/*
	/usr/local/www/status_gateways_json.php
	by Alexander Morris
	v0.13 20210325, added "gatewayip" for each interface
	v0.12 20170312, fetch rates by using friendlyiface name and added more info on gateway names, interface, etc.
	v0.11 20160421, only "clean" interface name if starts with "gw_"
	v0.1 20150630, for pfSense 2.x

	Module retrieves pfSense 2.x Dashboard Gateway Status and Traffic
	Graphs data in JSON format.  "status" can be "force_down", 
	"down", "loss" (for packet-loss warning), "delay" (for latency
	warning), or "okay" for online.

	WARNING: This module bypasses the normal pfSense dashboard 
	login, so it should be set with a different "key" of your 
	choosing in the PHP code.  The default is shown below:

	http://pfSenseIP/status_gateways_json.php?key=pfsense 

	Since there is additional overhead to calculate data rates for 
	each connection ("inKbps", "outKbps"), it can be optionally 
	requested via an additional parameter as indicated below:

	http://pfSenseIP/status_gateways_json.php?key=pfsense&rates=1 

	To install, simply change the "key" in the code below, then drop 
	the status_gateways_json.php file into your pfSense "/usr/local/www" 
	directory and you are set to go! 

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

##|+PRIV
##|*IDENT=page-status-gateways-json
##|*NAME=Status: Gateways json
##|*DESCR=Allow access to the 'Status: Gateways' data via json.
##|*MATCH=status_gateways_json.php*
##|-PRIV

##change key, used for quick and dirty security outside of GUI login
if ($_GET['key'] != "pfsense") die("invalidRequest");

require("interfaces.inc");

$a_gateways = return_gateways_array();
$gateways_status = return_gateways_status(true);

$pfgateways = array();

function get_interface_rates($iface, &$inKbps, &$outKbps, &$realif)
{
  $realif = get_real_interface($iface);
  $ifinfo1 = pfSense_get_interface_stats($realif);
  $tmrStart = microtime(true);
  usleep(100000);
  $ifinfo2 = pfSense_get_interface_stats($realif);
  $totTime = microtime(true)-$tmrStart;
  $inKbps = abs($ifinfo2['inbytes']-$ifinfo1['inbytes'])*(1/$totTime)/1000*8;
  $outKbps = abs($ifinfo2['outbytes']-$ifinfo1['outbytes'])*(1/$totTime)/1000*8;
}

if ($_GET['rates'] == 1) {
  get_interface_rates('lan',$inKbps,$outKbps,$realif);
  $pfgateways["lan"]["inKbps"] = $inKbps;
  $pfgateways["lan"]["outKbps"] = $outKbps;
  $pfgateways["lan"]["interface"] = $realif;
}

foreach ($gateways_status as $a_gateway) {
  $iface = $a_gateways[$a_gateway['name']]['friendlyiface'];
  if ($_GET['rates'] == 1) {
    get_interface_rates($iface,$inKbps,$outKbps,$realif);
    $pfgateways[$iface]["inKbps"] = $inKbps;
    $pfgateways[$iface]["outKbps"] = $outKbps;
  }
  $realif = $a_gateways[$a_gateway['name']]['interface'];
  $status = $a_gateway['status'];
  if ($status == "none") $status = "okay";
  $pfgateways[$iface]['interface'] = $realif;
  $pfgateways[$iface]['friendlyiface'] = $iface;
  $pfgateways[$iface]['name'] = $a_gateway['name'];
  $pfgateways[$iface]['status'] = $status;
  $pfgateways[$iface]['monitorip'] = $a_gateway['monitorip'];
  $pfgateways[$iface]['sourceip'] = $a_gateway['srcip'];
  $pfgateways[$iface]['gatewayip'] = $a_gateways[$a_gateway['name']]['gateway'];
  $pfgateways[$iface]['delay'] = $a_gateway['delay'];
  $pfgateways[$iface]['loss'] = $a_gateway['loss'];
}

echo json_encode($pfgateways);

?>
