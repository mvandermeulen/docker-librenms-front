<?php

include("token.php");
include("db.php");

echo "usage : http://10.0.0.14:8103/interface.php?device=10.0.0.15&from=20191020&to=20191029<br>";

function interface_availability($array) {
    $inoctets = $array['data']['INOCTETS'];
    $outoctets = $array['data']['OUTOCTETS'];

    $availability = 0;
    foreach($inoctets as $k => $v) { if (!is_nan($inoctets[$k]) && !is_nan($outoctets[$k])) $availability++; }

    return round(100 / count($inoctets) * $availability, 2);
}

function interface_peaks($array) {
    $inoctets = $array['data']['INOCTETS'];
    $outoctets = $array['data']['OUTOCTETS'];

    $a = max($inoctets);
    if (is_nan($a)) $a = 0;

    $b = max($outoctets);
    if (is_nan($b)) $b = 0;

    return [round($a * 8, 2), round($b * 8, 2)];
}

function interface_usage($array, $params) {
    $inoctets = $array['data']['INOCTETS'];
    $outoctets = $array['data']['OUTOCTETS'];

    reset($inoctets);
    $first = key($inoctets);
    end($inoctets);
    $last = key($inoctets);

    $interval = $last - $first;
    $bandwidth = $params["ifSpeed"];

    if ($bandwidth > 0) {
        $data = 0;
        $previous = $first;
        foreach ($inoctets as $k => $v) {
            $data = is_nan($v) ? $data : $data + (($v * 8) * ($k - $previous));
            $previous = $k;
        }
        $a = ($data * 100) / ($bandwidth * $interval);

        $data = 0;
        $previous = $first;
        foreach ($outoctets as $k => $v) {
            $data = is_nan($v) ? $data : $data + (($v * 8) * ($k - $previous));
            $previous = $k;
        }
        $b = ($data * 100) / ($bandwidth * $interval);
    }
    else {
        $a = 0;
        $b = 0;
    }

    return [round($a, 2), round($b, 2)];
}


if (isset($_GET['device']) && isset($_GET['from']) && isset($_GET['to'])) {
    $device = $_GET['device'];
    $from = strtotime($_GET['from']);
    $to = strtotime($_GET['to']);

    $db = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $q = $db->prepare('SELECT * FROM ports LEFT JOIN devices ON ports.device_id = devices.device_id WHERE devices.hostname = ?');
    $q->execute([$device]);
    $r = $q->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border=1 cellpadding=5 cellspacing=0>";
    echo "<tr>
        <th>host</th>
        <th>port</th>
        <th>availability (%)</th>
        <th>peak transmit (bps)</th>
        <th>peak receive (bps)</th>
        <th>transmit usage (%)</th>
        <th>receive usage (%)</th>
    </tr>";

    foreach($r as $v) {
        $data = rrd_fetch("../rrd/".$device."/port-id".$v['port_id'].".rrd", array("AVERAGE", "--start", $from, "--end", $to));

        $a = interface_availability($data);
        $b = interface_peaks($data);
        $c = interface_usage($data, $v);

        echo "<tr>
            <td>".$v['hostname']."</td>
            <td>".$v['ifName']."</td>
            <td>".$a."</td>
            <td>".$b[0]."</td>
            <td>".$b[1]."</td>
            <td>".$c[0]."</td>
            <td>".$c[1]."</td>
        </tr>";

    }
    echo "</table>";
    echo "done";
}

?>
