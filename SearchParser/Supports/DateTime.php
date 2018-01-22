<?php

namespace SearchParser\Supports;

use DateInterval;

class DateTime extends \DateTime
{
    /**
     * @param int|null $year
     * @param int|null $month
     * @param int|null $day
     *
     * @return \DateTime
     */
    public function setDate($year, $month, $day)
    {
        if (null == $year) {
            $year = $this->format('Y');
        }

        if (null == $month) {
            $month = $this->format('n');
        }

        if (null == $day) {
            $day = $this->format('j');
        }

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $day = $day > $daysInMonth ? $daysInMonth : $day;
        $return = parent::setDate($year, $month, $day);

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function modify($modify)
    {
        $pattern = '/( ?[-+]?\d?\w* months?)?( ?[-+]?\d?\w* years?)?/i';
        $modify = preg_replace_callback($pattern, function ($matches) use ($pattern) {
            if (empty($matches[0])) {
                return;
            }

            $orDay = $this->format('j');
            $this->setDate(null, null, 1);

            if (!parent::modify($matches[0])) {
                return;
            }

            $this->setDate(null, null, $orDay);

            return;
        },
            $modify
        );

        if ($modify = trim($modify)) {
            return parent::modify($modify);
        }

        return $this;
    }

    /**
     * 函数的参数类型按照文档应为 DateInterval $interval，但是这么设置会抛出函数声明与父类的冲突的错误
     * @inheritdoc
     */
    /*
    public function add($interval)
    {
        $format = $this->intervalToString($interval, $interval->invert ? '-' : '+');

        return $this->modify($format);
    }
    */

    /**
     * 函数的参数类型按照文档应为 DateInterval $interval，但是这么设置会抛出函数声明与父类的冲突的错误
     * @inheritdoc
     */
    /*
    public function sub($interval)
    {
        $format = $this->intervalToString($interval, $interval->invert ? '+' : '-');

        return $this->modify($format);
    }
    */

    /*
    protected function intervalToString(DateInterval $interval, $sign)
    {
        $format = vsprintf('%1$s%2$d years %1$s%3$d months %1$s%4$d days %1$s%5$d hours %1$s%6$d minutes %1$s%7$d seconds', [
            $sign,
            $interval->y,
            $interval->m,
            $interval->d,
            $interval->h,
            $interval->i,
            $interval->s,
        ]);

        return $format;
    }
    */
}
