<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\IntlBundle\Templating\Helper;

use Sonata\IntlBundle\Locale\LocaleDetectorInterface;
use Sonata\IntlBundle\Timezone\TimezoneDetectorInterface;

/**
 * DateHelper displays culture information. More information here
 * http://userguide.icu-project.org/formatparse/datetime.
 *
 * @author Thomas Rabaix <thomas.rabaix@ekino.com>
 * @author Alexander <iam.asm89@gmail.com>
 */
class DateTimeHelper extends BaseHelper
{
    /**
     * @var TimezoneDetectorInterface
     */
    protected $timezoneDetector;

    /**
     * @var \ReflectionClass
     */
    protected static $reflection;

    /**
     * @param TimezoneDetectorInterface $timezoneDetector
     * @param string                    $charset
     * @param LocaleDetectorInterface   $localeDetector
     */
    public function __construct(TimezoneDetectorInterface $timezoneDetector, $charset, LocaleDetectorInterface $localeDetector)
    {
        parent::__construct($charset, $localeDetector);

        $this->timezoneDetector = $timezoneDetector;
    }

    /**
     * @param array $args
     *
     * @return \IntlDateFormatter
     */
    protected static function createInstance(array $args = array())
    {
        if (!self::$reflection) {
            self::$reflection = new \ReflectionClass('IntlDateFormatter');
        }

        $instance = self::$reflection->newInstanceArgs($args);

        self::checkInternalClass($instance, '\IntlDateFormatter', $args);

        return $instance;
    }

    /**
     * @param \Datetime|string|int $date
     * @param null|string          $locale
     * @param null|string          timezone
     * @param null|int             dateType See \IntlDateFormatter::getDateType
     *
     * @return string
     */
    public function formatDate($date, $locale = null, $timezone = null, $dateType = null)
    {
        $date = $this->getDatetime($date, $timezone);

        $formatter = self::createInstance(array(
            'locale'   => $locale ?: $this->localeDetector->getLocale(),
            'datetype' => null === $dateType ? \IntlDateFormatter::MEDIUM : $dateType,
            'timetype' => \IntlDateFormatter::NONE,
            'timezone' => $timezone ?: $this->timezoneDetector->getTimezone(),
            'calendar' => \IntlDateFormatter::GREGORIAN,
        ));

        return $this->process($formatter, $date);
    }

    /**
     * @param \Datetime|string|int $datetime
     * @param null|string          $locale
     * @param null|string          timezone
     * @param null|int             dateType See \IntlDateFormatter::getDateType
     * @param null|int             timeType See \IntlDateFormatter::getTimeType
     *
     * @return string
     */
    public function formatDateTime($datetime, $locale = null, $timezone = null, $dateType = null, $timeType = null)
    {
        $date = $this->getDatetime($datetime, $timezone);

        $formatter = self::createInstance(array(
            'locale'   => $locale ?: $this->localeDetector->getLocale(),
            'datetype' => null === $dateType ? \IntlDateFormatter::MEDIUM : $dateType,
            'timetype' => null === $timeType ? \IntlDateFormatter::MEDIUM : $timeType,
            'timezone' => $timezone ?: $this->timezoneDetector->getTimezone(),
            'calendar' => \IntlDateFormatter::GREGORIAN,
        ));

        return $this->process($formatter, $date);
    }

    /**
     * @param \Datetime|string|int $time
     * @param null|string          $locale
     * @param null|string          timezone
     * @param null|int             timeType See \IntlDateFormatter::getTimeType
     *
     * @return string
     */
    public function formatTime($time, $locale = null, $timezone = null, $timeType = null)
    {
        $date = $this->getDatetime($time, $timezone);

        $formatter = self::createInstance(array(
            'locale'   => $locale ?: $this->localeDetector->getLocale(),
            'datetype' => \IntlDateFormatter::NONE,
            'timetype' => null === $timeType ? \IntlDateFormatter::MEDIUM : $timeType,
            'timezone' => $timezone ?: $this->timezoneDetector->getTimezone(),
            'calendar' => \IntlDateFormatter::GREGORIAN,
        ));

        return $this->process($formatter, $date);
    }

    /**
     * @param \Datetime|string|int $datetime
     * @param                      $pattern
     * @param null|string          $locale
     * @param null|string          timezone
     *
     * @return string
     */
    public function format($datetime, $pattern, $locale = null, $timezone = null)
    {
        $date = $this->getDatetime($datetime, $timezone);

        $formatter = self::createInstance(array(
            'locale'   => $locale ?: $this->localeDetector->getLocale(),
            'datetype' => \IntlDateFormatter::FULL,
            'timetype' => \IntlDateFormatter::FULL,
            'timezone' => $timezone ?: $this->timezoneDetector->getTimezone(),
            'calendar' => \IntlDateFormatter::GREGORIAN,
            'pattern'  => $pattern,
        ));

        return $this->process($formatter, $date);
    }

    /**
     * @param \IntlDateFormatter $formatter
     * @param \Datetime          $date
     *
     * @return string
     */
    public function process(\IntlDateFormatter $formatter, \Datetime $date)
    {
        // strange bug with PHP 5.3.3-7+squeeze14 with Suhosin-Patch
        // getTimestamp() method alters the object...
        return $this->fixCharset($formatter->format((int) $date->format('U')));
    }

    /**
     * Gets a date time instance by a given data and timezone.
     *
     * @param \Datetime|string|int $data     Value representing date
     * @param null|string          $timezone Timezone of the date
     *
     * @return \Datetime
     */
    public function getDatetime($data, $timezone = null)
    {
        if ($data instanceof \DateTime) {
            return $data;
        }

        // the format method accept array or integer
        if (is_numeric($data)) {
            $data = (int) $data;
        }

        if (is_string($data)) {
            $data = strtotime($data);
        }

        $date = new \DateTime();
        $date->setTimestamp($data);
        $date->setTimezone(new \DateTimeZone($timezone ?: $this->timezoneDetector->getTimezone()));

        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sonata_intl_datetime';
    }
}
