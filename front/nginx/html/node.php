<?php

    include("token.php");
    include("db.php");

    echo "usage : http://10.0.0.14:8103/node.php?device=10.0.0.14&from=20191029T000000&to=20191029T170000";

    function node_availability($array) {

        $uptime = $array['data']['uptime'];
        $previous = 0;
        $empty = 0;
        foreach($uptime as $k => $v) {
            if (!is_nan($uptime[$k])) {
                if ($uptime[$k] > $previous) {
                     $availability = $availability + $empty + 1;
                }
                $empty = 0;
                $previous = $uptime[$k];
            }
            else {
                $empty++;
            }
        }

        return round(100 / count($uptime) * $availability, 2);

    }

    function processor_load($array) {
        $load = $array["data"]["usage"];

        reset($load);
        $first = key($load);
        end($load);
        $last = key($load);

        $interval = $last - $first;

        $data = 0;
        $previous = $first;

        foreach ($load as $k => $v) {
            $data = is_nan($v) ? $data : $data + ($v * ($k - $previous));
            $previous = $k;
        }

        return round($data / $interval, 2);
    }

    function processor_peak($array) {
        $load = $array["data"]["usage"];

        return round(max($load), 2);
    }

    function ping_max_response($array) {
        $response = $array["data"]["ping"];

        return round(max($response), 2);
    }

    function ping_average_response($array) {
        $response = $array["data"]["ping"];

        reset($response);
        $first = key($response);
        end($response);
        $last = key($response);

        $interval = $last - $first;

        $data = 0;
        $previous = $first;

        foreach ($response as $k => $v) {
            $data = is_nan($v) ? $data : $data + ($v * ($k - $previous));
            $previous = $k;
        }

        return round($data / $interval, 2);
    }


    if (isset($_GET['device']) && isset($_GET['from']) && isset($_GET['to'])) {

        // availability
        $device = $_GET['device'];
        $from = strtotime($_GET['from']);
        $to = strtotime($_GET['to']);

        $uptime = rrd_fetch("../rrd/".$device."/uptime.rrd", array("AVERAGE", "--start", $from, "--end", $to));

        // cpu db
        $db = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $q = $db->prepare('SELECT * FROM processors LEFT JOIN devices ON processors.device_id = devices.device_id WHERE devices.hostname = ?');
        $q->execute([$device]);
        $r = $q->fetchAll(PDO::FETCH_ASSOC);

        $b = "";
        $c = "";
        foreach ($r as $k => $v) { 
            $data = rrd_fetch("../rrd/".$device."/processor-".$v["processor_type"]."-".$v["processor_index"].".rrd", array("AVERAGE", "--start", $from, "--end", $to));
            $b.= processor_load($data)."<br>";
            $c.= processor_peak($data)."<br>";
        }

        // ping
        $d = "";
        $data = rrd_fetch("../rrd/".$device."/ping-perf.rrd", array("AVERAGE", "--start", $from, "--end", $to));
        $d = ping_average_response($data);
        $e = ping_max_response($data);

        echo "<table border=1 cellpadding=5 cellspacing=0>";
        echo "<tr>
            <th>host</th>
            <th>availability (%)</th>
            <th>average cpu load (%)</th>
            <th>peak cpu load (%)</th>
            <th>average response time (ms)</th>
            <th>max response time (ms)</th>
        </tr>";

        $a = node_availability($uptime);

        echo "<tr>
            <td>".$device."</td>
            <td>".$a."</td>
            <td>".$b."</td>
            <td>".$c."</td>
            <td>".$d."</td>
            <td>".$e."</td>
        </tr>";

        echo "</table>";
        echo "done";

    }

?>
