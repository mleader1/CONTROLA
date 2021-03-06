<?php 
openlog("CGMinerClient", LOG_PID, LOG_LOCAL0);
	class  CGMinerClient {
	
		#
		# Sample Socket I/O to CGMiner API
		#
		static function getsock($addr, $port)
		{
			$socket = null;
			$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if ($socket === false || $socket === null)
			{
				$error = socket_strerror(socket_last_error());
				$msg = "socket create(TCP) failed {$error}";
				syslog(LOG_ALERT, $msg );
				return null;
		}
		
		$res = socket_connect($socket, $addr, $port);
		if ($res === false)
		{
			$error = socket_strerror(socket_last_error());
			$msg = "socket connect($addr,$port) failed";
			syslog(LOG_ALERT, $msg . ' returning null');
			socket_close($socket);
			return null;
		}
		return $socket;
		}
		
		static function readsockline($socket)
		{
			$line = '';
			while (true)
			{
				$byte = socket_read($socket, 1);
				if ($byte === false || $byte === '')
					break;
				if ($byte === "\0")
					break;
				$line .= $byte;
			}
			return $line;
		}
		
		static function request($cmd)
		{
			$socket = CGMinerClient::getsock('127.0.0.1', 4001);
			if ($socket != null)
			{
				socket_write($socket, $cmd, strlen($cmd));
				$line = CGMinerClient::readsockline($socket);
				socket_close($socket);
		
				if (strlen($line) == 0)
				{
					
					return $line;
				}
		
		
			if (substr($line,0,1) == '{')
				return json_decode($line, true);
		
			$data = array();
		
			$objs = explode('|', $line);
			foreach ($objs as $obj)
			{
				if (strlen($obj) > 0)
				{
					$items = explode(',', $obj);
					$item = $items[0];
					$id = explode('=', $items[0], 2);
					if (count($id) == 1 or !ctype_digit($id[1]))
						$name = $id[0];
					else
						$name = $id[0].$id[1];
		
					if (strlen($name) == 0)
						$name = 'null';
		
					if (isset($data[$name]))
					{
						$num = 1;
						while (isset($data[$name.$num]))
							$num++;
						$name .= $num;
					}
		
					$counter = 0;
					foreach ($items as $item)
					{
						$id = explode('=', $item, 2);
						if (count($id) == 2)
							$data[$name][$id[0]] = $id[1];
						else
							$data[$name][$counter] = $id[0];
		
						$counter++;
					}
				}
			}
		
			return $data;
		}
        exec('sleep 5');
		return CGMinerClient::request($cmd);
		}
		
		static function disableDevice($devid) {
			return CGMinerClient::request("ascdisable|".$devid); 
		}
		
		static function restart() {
			return CGMinerClient::request("restart");
		}
		
		static function enableDevice($devid) {
			return CGMinerClient::request("ascenable|".$devid);
		}

        static function setClockSpeed($devIdx , $clock) {
            CGMinerClient::request("pgaset|$devIdx,clock,$clock");
        }
		
		static function requestDevices() {
            $devs = CGMinerClient::request("devs");
            if (empty($devs)) return $devs;
            $devDetails = array_values(CGMinerClient::request("devdetails"));
            $counter = 0;
            foreach ($devs as $key=>&$dev) {
                if (strpos($key, 'PGA') !== false){
                    $dev["Serial"] = $devDetails[$counter]["Serial"];
                }
                $counter++;
            }
            return $devs;
		}
		
		static function requestPools() {
			return CGMinerClient::request("pools");
		}
		
		static function requestSummary() {
			return CGMinerClient::request("summary");
		}
		
	}
?>