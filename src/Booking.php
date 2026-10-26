<?php

namespace HarborTours;

/** A booking request from the public form. */
class Booking
{
    public $tour;
    public $name;
    public $phone;
    public $adults;
    public $children;
    public $voucher;
    /** Price in cents once priced. Declared: PHP 8.2 deprecates properties created on the fly. */
    public $total;

    public static function fromForm(array $form)
    {
        $booking = new Booking();
        $booking->tour = isset($form['tour']) ? $form['tour'] : null;
        $booking->name = isset($form['name']) ? $form['name'] : '';
        $booking->phone = isset($form['phone']) ? $form['phone'] : '';
        // Numbers from the form: an empty or missing field counts as 0, as PHP 7.4 read it.
        $booking->adults = isset($form['adults']) ? (int) $form['adults'] : 0;
        $booking->children = isset($form['children']) ? (int) $form['children'] : 0;
        $booking->voucher = isset($form['voucher']) ? $form['voucher'] : '';
        return $booking;
    }

    /** @return string[] what is wrong with the request, empty when it can be priced */
    public function errors()
    {
        $errors = array();
        if (strlen($this->name) < 2) {
            $errors[] = 'Please enter your name.';
        }
        if (strlen($this->phone) > 0 && !preg_match('/^\+?[0-9 ]{6,20}$/', $this->phone)) {
            $errors[] = 'Please check your phone number.';
        }
        if ($this->adults < 1) {
            $errors[] = 'At least one adult per booking.';
        }
        return $errors;
    }
}
