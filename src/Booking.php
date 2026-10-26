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

    public static function fromForm(array $form)
    {
        $booking = new Booking();
        $booking->tour = isset($form['tour']) ? $form['tour'] : null;
        $booking->name = isset($form['name']) ? $form['name'] : null;
        $booking->phone = isset($form['phone']) ? $form['phone'] : null;
        $booking->adults = isset($form['adults']) ? $form['adults'] : '';
        $booking->children = isset($form['children']) ? $form['children'] : '';
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
