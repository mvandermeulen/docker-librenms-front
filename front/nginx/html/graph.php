<?php

include("token.php");
include("db.php");

echo "usage : http://10.0.0.14:8103/graph.php?device=10.0.0.15&interface=gi1&&from=20191020T000000&to=20191030T100000<br>";

function get_curl_data($url){
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Auth-Token: $token"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
}

function get_port_graph($device, $ifname, $from, $to) {
        $result = get_curl_data("http://librenms/api/v0/devices/".urlencode($device)."/ports/".urlencode($ifname)."/port_bits?from=".urlencode($from)."&to=".urlencode($to));
        return $result;
}

function get_port_data($device, $ifname) {
        $result = get_curl_data("http://librenms/api/v0/devices/".urlencode($device)."/ports/".urlencode($ifname));
        return $result;
}

if (isset($_GET['device']) && isset($_GET['interface']) && isset($_GET['from']) && isset($_GET['to'])) {

    $device = $_GET['device'];
    $interface = $_GET['interface'];
    $from = strtotime($_GET['from']);
    $to = strtotime($_GET['to']);

    echo '<img src="data:image/jpeg;base64, '.base64_encode(get_port_graph($device, $interface, $from, $to)).'">';

    echo "<pre>";
    print_r(json_decode(get_port_data($device, $interface), true));
    echo "</pre>";

}

?>

