<?php

namespace HarborTours\Tests;

use HarborTours\Booking;
use HarborTours\Input;
use HarborTours\Manifest;
use HarborTours\Pricing;
use PHPUnit\Framework\TestCase;

/**
 * The site worked on PHP 7.4. Each numbered test is one way it breaks on PHP 8.x: on the code as delivered
 * (tag as-delivered) they pass on PHP 7.4 and fail on PHP 8.3; after the fixes they pass on PHP 8.3 and 8.4.
 * Written in PHP 7.4 syntax so the same file runs on both sides of the upgrade.
 */
class UpgradeTest extends TestCase
{
    private $sunset = array('name' => 'Sunset cruise', 'adult_cents' => 3500, 'child_cents' => 1800);

    private function pricing()
    {
        return new Pricing(array('4821' => 10, '7310' => 20));
    }

    // Break 1: get_magic_quotes_gpc() was removed in PHP 8.0, and every request goes through Input::clean().

    public function test_1_form_input_is_cleaned()
    {
        $clean = Input::clean(array('name' => "  O'Brien  ", 'adults' => ' 2 '));

        $this->assertSame(array('name' => "O'Brien", 'adults' => '2'), $clean);
    }

    // Break 2: each() was removed in PHP 8.0, and implode() no longer takes its arguments in the old order.

    public function test_2_passenger_list_counts_seats()
    {
        $manifest = Manifest::fromLines(array(
            "# sunset cruise, 26 October\n",
            "Anna Petit|+33 6 12 34 56 78|2|1\n",
            "\n",
            "Tom Reed||1|0\n",
        ));

        $this->assertSame(
            "name;phone;adults;children;seats\nAnna Petit;+33 6 12 34 56 78;2;1;3\nTom Reed;;1;0;1\ntotal;;;;4",
            $manifest
        );
    }

    // Break 3: an empty number field. PHP 7.4 read "" as 0 with a warning; PHP 8 throws a TypeError.

    public function test_3_empty_children_field_means_no_children()
    {
        $booking = Booking::fromForm(array('tour' => 'sunset', 'name' => 'Anna', 'adults' => '2', 'children' => '', 'voucher' => '4821'));

        $this->assertSame(6300, $this->pricing()->price($booking, $this->sunset));
    }

    // Break 4: "" == 0 was true in PHP 7.4 and is false in PHP 8, so an empty voucher field is looked up as a code.

    public function test_4_empty_voucher_field_means_no_discount()
    {
        $booking = Booking::fromForm(array('tour' => 'sunset', 'name' => 'Anna', 'adults' => '2', 'children' => '1', 'voucher' => ''));

        $this->assertSame(8800, $this->pricing()->price($booking, $this->sunset));
    }

    // Break 5: deprecations of PHP 8.1 and 8.2 (null to strlen(), dynamic properties, "${var}"), errors in PHP 9.

    public function test_5_booking_flow_raises_no_deprecation()
    {
        $deprecations = array();
        set_error_handler(function ($type, $message) use (&$deprecations) {
            $deprecations[] = $message;
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);

        try {
            $booking = Booking::fromForm(array('tour' => 'sunset', 'name' => 'Anna', 'adults' => '2', 'children' => '0', 'voucher' => '7310'));
            $booking->errors();
            $pricing = $this->pricing();
            $pricing->price($booking, $this->sunset);
            $confirmation = $pricing->confirmation($booking);
        } finally {
            restore_error_handler();
        }

        $this->assertSame(array(), $deprecations);
        $this->assertSame('Thank you Anna! Your booking is confirmed: 56.00 EUR, to pay on board.', $confirmation);
    }

    // Normal use.

    public function test_voucher_discount_and_unknown_voucher()
    {
        $booking = Booking::fromForm(array('tour' => 'sunset', 'name' => 'Anna', 'adults' => '1', 'children' => '0', 'voucher' => '7310'));
        $this->assertSame(2800, $this->pricing()->price($booking, $this->sunset));

        $booking = Booking::fromForm(array('tour' => 'sunset', 'name' => 'Anna', 'adults' => '1', 'children' => '0', 'voucher' => '1234'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown voucher 1234.');
        $this->pricing()->price($booking, $this->sunset);
    }

    /**
     * A deliberate change from PHP 7.4, where "ABC" == 0 was true and such a code was silently ignored.
     * @group changed-from-7.4
     */
    public function test_voucher_with_letters_is_refused_not_ignored()
    {
        foreach (array('ABC', '0') as $code) {
            $booking = Booking::fromForm(array('tour' => 'sunset', 'name' => 'Anna', 'adults' => '1', 'children' => '0', 'voucher' => $code));
            try {
                $this->pricing()->price($booking, $this->sunset);
                $this->fail("voucher $code was ignored");
            } catch (\InvalidArgumentException $e) {
                $this->assertSame("Unknown voucher $code.", $e->getMessage());
            }
        }
    }

    public function test_form_errors()
    {
        $this->assertSame(array(), Booking::fromForm(array('name' => 'Anna', 'phone' => '+33 6 12 34 56 78', 'adults' => '1'))->errors());
        $this->assertSame(
            array('Please enter your name.', 'Please check your phone number.', 'At least one adult per booking.'),
            Booking::fromForm(array('name' => '', 'phone' => 'call me', 'adults' => '0'))->errors()
        );
    }
}
