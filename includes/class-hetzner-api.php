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

    public function get_server_metrics($server_id) {
        // Get server details for total resources
        $server_details = $this->get_server($server_id);
        if (!isset($server_details['server'])) {
            return [
                'success' => false,
                'message' => 'Failed to get server details'
            ];
        }

        $server = $server_details['server'];
        $total_memory = $server['server_type']['memory'] * 1024 * 1024 * 1024; // Convert GB to bytes
        $total_disk = $server['server_type']['disk'] * 1024 * 1024 * 1024; // Convert GB to bytes
        $total_cpu = $server['server_type']['cores'];

        // Get metrics for the last 5 minutes
        $end = date('Y-m-d\TH:i:s\Z');
        $start = date('Y-m-d\TH:i:s\Z', strtotime('-5 minutes'));

        $query = http_build_query([
            'type' => 'cpu,memory,disk',
            'start' => $start,
            'end' => $end,
            'step' => '30'
        ]);

        $response = $this->make_request('GET', "/servers/{$server_id}/metrics?{$query}");
        
        if (isset($response['metrics'])) {
            $metrics = $response['metrics'];
            $processed_metrics = [];

            // Process CPU metrics (CPU usage percentage)
            if (isset($metrics['cpu'])) {
                $cpu_values = $metrics['cpu']['values'];
                if (!empty($cpu_values)) {
                    $latest_cpu = end($cpu_values)[1];
                    // Convert CPU seconds to percentage
                    $processed_metrics['cpu'] = round(($latest_cpu / $total_cpu) * 100, 2);
                }
            }

            // Process Memory metrics (Memory usage in bytes to percentage)
            if (isset($metrics['memory'])) {
                $memory_values = $metrics['memory']['values'];
                if (!empty($memory_values)) {
                    $latest_memory = end($memory_values)[1];
                    // Convert bytes to percentage
                    $processed_metrics['memory'] = round(($latest_memory / $total_memory) * 100, 2);
                }
            }

            // Process Disk metrics (Disk usage in bytes to percentage)
            if (isset($metrics['disk'])) {
                $disk_values = $metrics['disk']['values'];
                if (!empty($disk_values)) {
                    $latest_disk = end($disk_values)[1];
                    // Convert bytes to percentage
                    $processed_metrics['disk'] = round(($latest_disk / $total_disk) * 100, 2);
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

    public function get_prices() {
        return $this->make_request('GET', '/pricing');
    }

    public function calculate_server_costs($servers) {
        $prices = $this->get_prices();
        if (!isset($prices['pricing'])) {
            return [
                'success' => false,
                'message' => 'Failed to fetch pricing information'
            ];
        }

        $server_prices = $prices['pricing']['server_types'];
        $total_hourly = 0;
        $total_monthly = 0;
        $server_costs = [];

        foreach ($servers as $server) {
            $server_type = $server['server_type']['name'];
            $location = $server['datacenter']['location']['name'];
            $price_info = null;

            // Find price for this server type
            foreach ($server_prices as $price) {
                if ($price['name'] === $server_type) {
                    $price_info = $price;
                    break;
                }
            }

            if ($price_info) {
                // Get prices with VAT
                $hourly = $price_info['prices'][0]['price_hourly']['gross'];
                $monthly = $price_info['prices'][0]['price_monthly']['gross'];

                $server_costs[] = [
                    'id' => $server['id'],
                    'name' => $server['name'],
                    'type' => $server_type,
                    'location' => $location,
                    'hourly' => $hourly,
                    'monthly' => $monthly
                ];

                if ($server['status'] === 'running') {
                    $total_hourly += $hourly;
                    $total_monthly += $monthly;
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'total_hourly' => round($total_hourly, 4),
                'total_monthly' => round($total_monthly, 2),
                'server_costs' => $server_costs
            ]
        ];
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
