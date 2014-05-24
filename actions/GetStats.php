<?php 
	
require_once 'config/define.php';
require_once 'class/miner.class.php';
require_once 'class/cache.class.php';

$cache = new Cache(PATH_CACHE);


$devices = $cache->get(CACHE_DEVICE);


$success = false;

$iniArr = parse_ini_file(FILE_CONFIG);


$runmode = "IDLE";

if ($iniArr["model"] == 1) {$runmode = 'BTC';}
if ($iniArr["model"] == 2) {$runmode = 'LTC';}
if ($iniArr["model"] == 3) {$runmode = 'DUAL';}

$runtime = $cache->get(CACHE_RUNTIME);
$uptime = time() - $runtime["runtime"];
$li = '';
$offline = 0;
$totalhash = 0;
$totalhashbtc = 0;
$info = "";
$table = 'No devices found';
if(!empty($devices))
{

	$table = "";
	$tablebtc = "";

	if ($runmode == 'BTC' || $runmode == 'DUAL'){
		$statsui = Miner::getCGMinerStats();
	}else if ($runmode == 'LTC'){
		$statsui = Miner::getBFGMinerStats();
	}
	
	$counter = 0;
	$runType = "ltc";
	if($iniArr["model"] == 1 || $iniArr["model"] == 3)
	{
		$runType = "btc";
	}
	foreach($statsui["devices"] as $devid)
	{

			
			$hash = isset($devid["hashrate"]) ? $devid["hashrate"] : 0;
            $poolhash = isset($devid["poolhashrate"]) ? $devid["poolhashrate"] : 0;
			$valids = isset($devid["valid"]) ? $devid["valid"] : 0;
			$invalids = isset($devid["invalid"]) ? $devid["invalid"] : 0;
			$totals = $valids + $invalids;
			$rejrate = $totals > 0 ? round(100 * $invalids / $totals, 2) : 0;
            $serial = isset($devid["serial"]) ? $devid["serial"] : 0;
			$time = $devid["time"];
            $hw = $devid["hw"];
			$lastcommittime = ($time > 0) ? (time() - $time) / 60 : 0;
			$comma = ($counter == 0)? '':',';
			$table .= $comma.'{"dev" : "'.$runType.'_'.$devid['device'].'" ,
			    "poolhash" : "'.$poolhash.'",
			    "hash" : "'.$hash.'",
                "valids" : "'.$valids.'" ,
                "totals" : "'.$totals.'",
                "rejectrate" : "'.$rejrate.'" ,
                "time" : "'.$time.'",
                "lastcommit" : "'.$lastcommittime.'",
                "serial" : "'.$serial.'",
                "hw" : "'.$hw.'"

			}';
			$counter++;
		

	}
	$summary = "{}";
	if (isset($statsui["summary"])) {
		$summary = json_encode($statsui["summary"]);
	}
	$pools = "{}";
	if (isset($statsui["pools"])){
		$pools = json_encode($statsui["pools"]);
	}
	if(count($devices) == $offline)
	{
		$uptime = 0;
	}

}
?>

{
	"Summary" : <?php echo $summary?>,
	"pools" : <?php echo $pools ?>,
	"LTCDevices" : [<?php if($runmode === 'LTC') echo $table?>],
	"BTCDevices" : [<?php if ($runmode === 'DUAL' || $runmode === 'BTC') echo $table?>]
}


