<?php
class Hetzner_API {
    private $api_token;
    private $api_url = 'https://api.hetzner.cloud/v1';

    public function __construct($api_token) {
        $this->api_token = $api_token;
    }

    public function get_servers() {
        return $this->make_request('GET', '/servers');
    }

    public function get_server($server_id) {
        return $this->make_request('GET', "/servers/{$server_id}");
    }

    public function get_server_metrics($server_id, $type = 'cpu,disk,network,memory', $start = null, $end = null) {
        if (!$start) {
            $start = date('Y-m-d\TH:i:s\Z', strtotime('-1 hour'));
        }
        if (!$end) {
            $end = date('Y-m-d\TH:i:s\Z');
        }

        $query = http_build_query([
            'type' => $type,
            'start' => $start,
            'end' => $end,
            'step' => '60'
        ]);

        return $this->make_request('GET', "/servers/{$server_id}/metrics?{$query}");
    }

    public function get_server_status($server_id) {
        $response = $this->get_server($server_id);
        if (isset($response['server'])) {
            return [
                'status' => $response['server']['status'],
                'name' => $response['server']['name'],
                'ip' => $response['server']['public_net']['ipv4']['ip'],
                'cores' => $response['server']['server_type']['cores'],
                'memory' => $response['server']['server_type']['memory'],
                'disk' => $response['server']['server_type']['disk'],
                'datacenter' => $response['server']['datacenter']['name'],
                'price_hourly' => $response['server']['server_type']['prices'][0]['price_hourly']['gross']
            ];
        }
        return false;
    }

    private function make_request($method, $endpoint) {
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        $response = wp_remote_request($this->api_url . $endpoint, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
