<?php
namespace App\Service;


class DateUtilityService
{
    /** @var array */
    private $dayOfWeekMap = array(
        1 => array(
            'short' => "Mon",
            "long"  => "Monday",
        ),
        2 => array(
            'short' => "Tue",
            "long"  => "Tuesday",
        ),
        3 => array(
            'short' => "Wed",
            "long"  => "Wednesday",
        ),
        4 => array(
            'short' => "Thu",
            "long"  => "Thursday",
        ),
        5 => array(
            'short' => "Fri",
            "long"  => "Friday",
        ),
        6 => array(
            'short' => "Sat",
            "long"  => "Saturday",
        ),
        7 => array(
            'short' => "Sun",
            "long"  => "Sunday",
        ),
    );

    /**
     * @param int    $dayOfWeekIndex
     * @param string $type
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getDayOfWeekText(int $dayOfWeekIndex, $type='short')
    {
        if (!(0 <= $dayOfWeekIndex && $dayOfWeekIndex <= 7)) {
            throw new \InvalidArgumentException(
                "Index " . $dayOfWeekIndex . " out of range in " . __METHOD__,
                500
            );
        }

        if ($type != 'short' &&
            $type != 'long'
        ) {
            throw new \InvalidArgumentException(
                "Type " . $type . " invalid value in " . __METHOD__,
                500
            );
        }

        return ($this->dayOfWeekMap[$dayOfWeekIndex][$type]);
    }

    /**
     * @param string $dayOfWeekName
     *
     * @return int
     * @throws \InvalidArgumentException
     */
    public function getDayOfWeekIndex(string $dayOfWeekName)
    {
        foreach ($this->dayOfWeekMap as $index => $values) {
            if ($values['short'] == $dayOfWeekName ||
                $values['long'] == $dayOfWeekName
            ) {
                return $index;
            }
        }

        throw new \InvalidArgumentException(
            "Name " . $dayOfWeekName . " not found in " . __METHOD__,
            500
        );
    }
}
