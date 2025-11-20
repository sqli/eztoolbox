<?php

namespace SQLI\EzToolboxBundle\Services;

use DateTime;
use SQLI\EzToolboxBundle\Exceptions\DataFormatterException;

/**
 * Class DataFormatterHelper
 * @package SQLI\EzToolboxBundle\Services
 */
class DataFormatterHelper
{
    /**
     * @param mixed $data
     * @param string $format
     * @param null $pattern
     * @return string
     */
    public function format($data, string $format, $pattern = null): string
    {
        switch ($format) {
            case "float":
                return preg_replace('#^([0-9\s]+),([0-9]+)$#', '$1.$2', $data);
            case "amount":
                $number = $this->format($data, "float");

                return number_format((float)$number, 2, ",", " ");
            case "price":
                // $pattern should contains currency code according to ISO 4217
                $pattern = $pattern ?: "EUR";

                $numberFormatter = new \NumberFormatter(locale_get_default(), \NumberFormatter::CURRENCY);
                return $numberFormatter->formatCurrency((float)$data, $pattern);
            case "french_date":
                $pattern = is_null($pattern) ? 'l d F à H:i' : $pattern;
                $data = $this->toDateTime($data);

                return $this->formatFrenchDate($data, $pattern);
            case "filesize":
                $pattern = is_int($pattern) ? $pattern : 1;

                return $this->humanFilesize($data, $pattern);
            case "datetime":
                return $this->toDateTime($data);
            case "url":
                $url = $data;
                // Check if protocol is in $data
                if (!preg_match('#^http(?:s)?://#', $data)) {
                    $url = "http://" . $data;
                }
                return $url;
            case "slug":
                return $this->slugify($data);
        }

        throw new DataFormatterException("Unknown format name : $format");
    }

    /**
     * Returns a DateTime object from a string
     *
     * @param DateTime|string $date Date to convert
     * @param mixed $defaultReturn Value to return if cannot create a DateTime
     * @return DateTime|false
     */
    public function toDateTime($date, $defaultReturn = false)
    {
        if (!$date instanceof DateTime) {
            $date = (string)$date;

            // Try to build DateTime with format d/m/Y
            $dateTime = DateTime::createFromFormat("d/m/Y", $date, new \DateTimeZone('UTC'));

            if ($dateTime === false) {
                // Try to build DateTime with format Y-m-d
                $dateTime = DateTime::createFromFormat("Y-m-d", $date, new \DateTimeZone('UTC'));
            }

            if ($dateTime === false) {
                $date != "" ?: $date = "now";
                try {
                    $dateTime = new DateTime($date, new \DateTimeZone('UTC'));
                } catch (\Exception $exception) {
                    $dateTime = $defaultReturn;
                }
            }
        } else {
            // Already a DateTime object
            $dateTime = $date;
        }

        return $dateTime instanceof DateTime ? $dateTime : $defaultReturn;
    }

    /**
     * @param DateTime|string $date
     * @param null $pattern
     * @return string
     * @throws \Exception
     */
    private function formatFrenchDate($date, $pattern = null): string
    {
        $monthEn = [
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December"
        ];

        $monthFr = [
            "Janvier",
            "Février",
            "Mars",
            "Avril",
            "Mai",
            "Juin",
            "Juillet",
            "Août",
            "Septembre",
            "Octobre",
            "Novembre",
            "Décembre"
        ];

        $dayFullEn = [
            "Monday",
            "Tuesday",
            "Wednesday",
            "Thursday",
            "Friday",
            "Saturday",
            "Sunday",
        ];

        $dayFullFr = [
            "Lundi",
            "Mardi",
            "Mercredi",
            "Jeudi",
            "Vendredi",
            "Samedi",
            "Dimanche",
        ];

        $dayEn = [
            "Mon",
            "Tue",
            "Wed",
            "Thu",
            "Fri",
            "Sat",
            "Sun",
            "Jan",
            "Feb",
            "Mar",
            "Apr",
            "May",
            "Jun",
            "Jul",
            "Aug",
            "Sep",
            "Oct",
            "Nov",
            "Dec"
        ];

        $dayFr = [
            "Lun",
            "Mar",
            "Mer",
            "Jeu",
            "Ven",
            "Sam",
            "Dim",
            "Jan",
            "Fév",
            "Mar",
            "Avr",
            "Mai",
            "Jui",
            "Jui",
            "Aoû",
            "Sep",
            "Oct",
            "Nov",
            "Déc"
        ];

        if (!$date instanceof DateTime) {
            $date = new DateTime($date);
        }

        $dateFormatToFrensh = str_replace($monthEn, $monthFr, $date->format($pattern));
        $dateFormatToFrensh = str_replace($dayFullEn, $dayFullFr, $dateFormatToFrensh);

        return str_replace($dayEn, $dayFr, $dateFormatToFrensh);
    }

    /**
     * Format filesize (in bytes) into human readable filesize
     *
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    private function humanFilesize(int $bytes, int $decimals = 2): string
    {
        $sz = ["o", "Ko", "Mo", "Go", "To", "Po"];
        $factor = (int)floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f ", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    /**
     * @param string $string
     * @param string $delimiter
     * @return string
     */
    public function slugify(string $string, string $delimiter = '-'): string
    {
        $oldLocale = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'en_US.UTF-8');
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower($clean);
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        $clean = trim($clean, $delimiter);
        setlocale(LC_ALL, $oldLocale);

        return $clean;
    }
}
