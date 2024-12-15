<?php
class KloudPanel_API {
    private $api_url;
    private $api_key;

    public function __construct($url, $api_key) {
        $this->api_url = rtrim($url, '/');
        $this->api_key = $api_key;
    }

    public function get_system_status() {
        $endpoint = '/api/systemStatus';
        return $this->make_request('GET', $endpoint);
    }

    public function get_websites() {
        $endpoint = '/api/listWebsites';
        return $this->make_request('GET', $endpoint);
    }

    public function get_resource_usage() {
        $endpoint = '/api/getResourceUsage';
        return $this->make_request('GET', $endpoint);
    }

    private function make_request($method, $endpoint, $body = null) {
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        if ($body) {
            $args['body'] = json_encode($body);
        }

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
