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

    public function get_server_metrics($server_id, $type = 'cpu,disk,memory', $start = null, $end = null) {
        if (!$start) {
            $start = date('Y-m-d\TH:i:s\Z', strtotime('-5 minutes'));
        }
        if (!$end) {
            $end = date('Y-m-d\TH:i:s\Z');
        }

        $query = http_build_query([
            'type' => $type,
            'start' => $start,
            'end' => $end,
            'step' => '30'
        ]);

        $response = $this->make_request('GET', "/servers/{$server_id}/metrics?{$query}");
        
        if (isset($response['metrics'])) {
            $metrics = $response['metrics'];
            $processed_metrics = [];

            // Process CPU metrics
            if (isset($metrics['cpu'])) {
                $cpu_values = $metrics['cpu']['values'];
                if (!empty($cpu_values)) {
                    $latest_cpu = end($cpu_values)[1]; // Get the last value
                    $processed_metrics['cpu'] = round($latest_cpu * 100, 2); // Convert to percentage
                }
            }

            // Process Memory metrics
            if (isset($metrics['memory'])) {
                $memory_values = $metrics['memory']['values'];
                if (!empty($memory_values)) {
                    $latest_memory = end($memory_values)[1];
                    $processed_metrics['memory'] = round($latest_memory * 100, 2);
                }
            }

            // Process Disk metrics
            if (isset($metrics['disk'])) {
                $disk_values = $metrics['disk']['values'];
                if (!empty($disk_values)) {
                    $latest_disk = end($disk_values)[1];
                    $processed_metrics['disk'] = round($latest_disk * 100, 2);
                }
            }

            return [
                'success' => true,
                'metrics' => $processed_metrics
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to fetch metrics'
        ];
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
