<?php
/**
 * CLI smoke test for multi-service appointments.
 *
 * Usage inside container:
 * php /var/www/html/tools/smoke_multi_services.php
 *
 * It will:
 *  - pick the first provider, customer, and two services found
 *  - create an appointment with services[]
 *  - print stored appointment (model) and services[] from API encoding
 */

define('CI_RUNNING_SMOKE', true);

// Bootstrap CodeIgniter.
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
require __DIR__ . '/../index.php';

$CI =& get_instance();
$CI->load->model('appointments_model');
$CI->load->model('providers_model');
$CI->load->model('customers_model');
$CI->load->model('services_model');

// Fetch sample provider, customer, services.
$provider = $CI->db
    ->select('u.id')
    ->from('users AS u')
    ->join('roles r', 'r.id = u.id_roles', 'inner')
    ->where('r.slug', DB_SLUG_PROVIDER)
    ->order_by('u.id', 'ASC')
    ->limit(1)
    ->get()
    ->row_array();

$customer = $CI->db
    ->select('u.id')
    ->from('users AS u')
    ->join('roles r', 'r.id = u.id_roles', 'inner')
    ->where('r.slug', DB_SLUG_CUSTOMER)
    ->order_by('u.id', 'ASC')
    ->limit(1)
    ->get()
    ->row_array();

$services = $CI->db->select('id,duration,price')->from('services')->order_by('id','ASC')->limit(3)->get()->result_array();

if (!$provider || !$customer || count($services) < 1) {
    fwrite(STDERR, "Missing provider/customer/services to run smoke test.\n");
    exit(1);
}

$now = new DateTime('now');
$start = $now->format('Y-m-d H:i:s');
$end = (clone $now)->add(new DateInterval('PT' . max(30, (int) ($services[0]['duration'] ?? 30)) . 'M'))->format('Y-m-d H:i:s');

$services_payload = [];
$position = 1;
foreach ($services as $srv) {
    $services_payload[] = [
        'service_id' => (int) $srv['id'],
        'duration' => $srv['duration'] !== null ? (int) $srv['duration'] : null,
        'price' => $srv['price'] !== null ? (float) $srv['price'] : null,
        'position' => $position++,
    ];
    if ($position > 3) {
        break;
    }
}

$appointment_payload = [
    'start_datetime' => $start,
    'end_datetime' => $end,
    'id_users_provider' => (int) $provider['id'],
    'id_users_customer' => (int) $customer['id'],
    'notes' => 'Smoke test multi-services',
    'location' => 'Smoke',
    'status' => 'Booked',
    'services' => $services_payload,
];

$id = $CI->appointments_model->save($appointment_payload);
$stored = $CI->appointments_model->find($id);
$CI->appointments_model->api_encode($stored);

echo "Created appointment ID: {$id}\n";
echo "API payload:\n";
echo json_encode($stored, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
