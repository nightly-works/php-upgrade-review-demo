<?php

namespace HarborTours;

/** Prices a booking: adults and children at the tour's rates, then the voucher's discount. */
class Pricing
{
    private $vouchers;

    /** @param array $vouchers printed gift voucher number => percent off */
    public function __construct(array $vouchers)
    {
        $this->vouchers = $vouchers;
    }

    /** @param array $tour with adult_cents and child_cents */
    public function price(Booking $booking, array $tour)
    {
        $total = $booking->adults * $tour['adult_cents'] + $booking->children * $tour['child_cents'];

        // No voucher entered.
        if ($booking->voucher == 0) {
            $booking->total = $total;
            return $total;
        }

        $code = $booking->voucher;
        if (!isset($this->vouchers[$code])) {
            throw new \InvalidArgumentException("Unknown voucher ${code}.");
        }

        $booking->total = (int) round($total * (100 - $this->vouchers[$code]) / 100);
        return $booking->total;
    }

    public function confirmation(Booking $booking)
    {
        $name = $booking->name;
        $euros = number_format($booking->total / 100, 2);
        return "Thank you ${name}! Your booking is confirmed: ${euros} EUR, to pay on board.";
    }
}
