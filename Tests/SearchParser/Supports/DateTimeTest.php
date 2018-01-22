<?php

namespace Tests\Supports;

use Tests\SearchParser\AbstractTestCase;
use SearchParser\Supports\DateTime;

class SearchParserTest extends AbstractTestCase
{
    public function providerSetDate()
    {
        $now = new DateTime();

        return [
            ['2018-02-28', 29, 2],
            ['2018-02-28', 30, 2],
            ['2018-02-28', 31, 2],
            ['2018-04-30', 31, 4],
            ['2018-06-30', 31, 6],
            ['2018-09-30', 31, 9],
            ['2018-11-30', 31, 11],
            ['2018-02-01', 1, 2],
            [$now->format('Y-m-d'), null, null, null],
        ];
    }

    /**
     * @dataProvider providerSetDate
     * @param $result
     * @param $day
     * @param $month
     * @param string $year
     */
    public function testSetDate($result, $day, $month, $year = '2018')
    {
        $dateTime = new DateTime();
        $dateTime->setDate($year, $month, $day);
        $this->assertEquals($result, $dateTime->format('Y-m-d'));
    }

    public function providerModify()
    {
        return [
            ['2018-02-28', '0 day 0 month 0 year'],
            ['2018-01-28', 'next month'],
            ['2018-01-28', 'next months'],
            ['2018-01-02', '+1 month', '2018-02-02'],
            ['2018-01-31', '1 months'],
            ['2018-03-31', '-1 month'],
            ['2016-02-29', '24 month'],
            ['2020-02-29', '-24 months'],
            ['2018-02-27', 'next day'],
            ['2018-02-27', '1 day'],
            ['2018-02-27', '+1 day'],
            ['2017-02-01', '-1 day next month next year'],
            ['2017-01-27', '1 day 1 month 1 year'],
            ['2019-04-01', '-1 day -1 month -1 year'],
            ['2015-12-26', '2 day 2 month 2 year'],
            ['2015-12-31', '2 month 2 year'],
            ['2018-01-31', '-1 month', '2017-12-31'],
            ['2018-02-28', '-1 month', '2018-01-28'],
            ['2018-03-31', '-1 month', '2018-02-28'],
            ['2018-04-30', '-1 month', '2018-03-30'],
            ['2018-05-31', '-1 month', '2018-04-30'],
            ['2018-06-30', '-1 month', '2018-05-30'],
            ['2018-07-31', '-1 month', '2018-06-30'],
            ['2018-08-31', '-1 month', '2018-07-31'],
            ['2018-09-30', '-1 month', '2018-08-30'],
            ['2018-10-31', '-1 month', '2018-09-30'],
            ['2018-11-30', '-1 month', '2018-10-30'],
            ['2018-12-31', '-1 month', '2018-11-30'],
            ['2018-01-31', '+1 month', '2018-02-28'],
            ['2018-02-28', '+1 month', '2018-03-28'],
            ['2018-03-31', '+1 month', '2018-04-30'],
            ['2018-04-30', '+1 month', '2018-05-30'],
            ['2018-05-31', '+1 month', '2018-06-30'],
            ['2018-06-30', '+1 month', '2018-07-30'],
            ['2018-07-31', '+1 month', '2018-08-31'],
            ['2018-08-31', '+1 month', '2018-09-30'],
            ['2018-09-30', '+1 month', '2018-10-30'],
            ['2018-10-31', '+1 month', '2018-11-30'],
            ['2018-11-30', '+1 month', '2018-12-30'],
            ['2018-12-31', '+1 month', '2019-01-31'],
        ];
    }

    /**
     * @dataProvider providerModify
     * @param $date
     * @param $shift
     * @param string $result
     */
    public function testModify($date, $shift, $result = '2018-02-28')
    {
        $dateTime = new DateTime($date);
        $dateTime->modify($shift);
        $this->assertEquals($result, $dateTime->format('Y-m-d'));
    }

    /*
    public function providerAdd()
    {
        return [
            ['2018-02-28', 'PT1S'],
            ['2018-01-28', 'P1M'],
            ['2018-01-02', 'P1M', '2018-02-02'],
            ['2018-01-31', 'P1M'],
            ['2018-01-31', 'P2M', '2018-03-31'],
            ['2018-01-30', 'P3M', '2018-04-30'],
            ['2016-02-29', 'P2Y'],
            ['2016-02-29', 'P24M'],
            ['2018-02-27', 'P1D'],
            ['2017-01-27', 'P1Y1M1D'],
            ['2015-12-26', 'P2Y2M2D'],
            ['2015-12-31', 'P2Y2M'],
        ];
    }
    */

    /**
     * @dataProvider providerAdd
     * @param $date
     * @param $format
     * @param string $result
     */
    /*
    public function testAdd($date, $format, $result = '2018-02-28')
    {
        $dateTime = new DateTime($date);
        $dateTime->add(new \DateInterval($format));
        $this->assertEquals($result, $dateTime->format('Y-m-d'));
    }
    */

    /*
    public function providerSub()
    {
        return [
            ['2018-03-31', 'P1M'],
            ['2020-02-29', 'P24M'],
            ['2019-04-01', 'P1Y1M1D'],
        ];
    }
    */

    /**
     * @dataProvider providerSub
     * @param $date
     * @param $format
     * @param string $result
     */
    /*
    public function testSub($date, $format, $result = '2018-02-28')
    {
        $dateTime = new DateTime($date);
        $dateTime->sub(new \DateInterval($format));
        $this->assertEquals($result, $dateTime->format('Y-m-d'));
    }
    */
}
