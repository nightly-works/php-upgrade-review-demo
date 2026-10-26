<?php

// The public booking form posts here; the site's pages include this file.
require __DIR__.'/../vendor/autoload.php';

use HarborTours\Booking;
use HarborTours\Input;
use HarborTours\Pricing;

$tours = array(
    'sunset' => array('name' => 'Sunset cruise', 'adult_cents' => 3500, 'child_cents' => 1800),
    'islands' => array('name' => 'Three islands', 'adult_cents' => 5200, 'child_cents' => 2600),
);
$pricing = new Pricing(array('4821' => 10, '7310' => 20));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /book.html');
    exit;
}

$booking = Booking::fromForm(Input::clean($_POST));
$errors = $booking->errors();
if (!isset($tours[$booking->tour])) {
    $errors[] = 'Please choose a tour.';
}
if ($errors) {
    http_response_code(422);
    echo implode('<br>', array_map('htmlspecialchars', $errors));
    exit;
}

try {
    $pricing->price($booking, $tours[$booking->tour]);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo htmlspecialchars($e->getMessage());
    exit;
}

echo htmlspecialchars($pricing->confirmation($booking));
