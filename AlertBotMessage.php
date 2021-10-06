<?php
require_once __DIR__ . '/GetSheet.php';

class AlertBotMessage
{
    private $spreadsheets = [];
    private $message = '';
    private $sheet;


    public function __construct($spreadsheets, $message)
    {
        $this->spreadsheets = $spreadsheets;
        $this->message = $message;

        $this->sheet = new GetSheet('linkmanager-305415-7af4ba4f61e5.json');
    }

    /**
     * @param array $spreadsheets
     */
    public function setSpreadsheets($spreadsheets)
    {
        $this->spreadsheets = $spreadsheets;
    }

    public function extMessage($text)
    {
        $this->message .= $text;
    }

    function getBotMessage($timestamp, $day_range)
    {
        foreach ($this->spreadsheets as $spreadsheet) {
            $title = $this->sheet->getTitle($spreadsheet);
            $values = $this->sheet->getValues($spreadsheet, 'Drops');

            $expiringDomainsInfo = $this->getExpiringDomainsInfo($values, $timestamp, $day_range);

            $this->extMessage('<b>'.$title.'</b>'."\n");

            foreach ($expiringDomainsInfo as $line) {
                $this->extMessage($line[0].' '.$line[1].' <i>'.$line[2].'</i> '.$line[3]);

                if ($this->isExpired($line[2], $timestamp))
                    $this->extMessage(' <b>ПРОСРОЧЕН</b>');
                else if ($this->isExpiring($line[2], $timestamp, 0))
                    $this->extMessage( ' <b>ИСТЕКАЕТ СЕГОДНЯ</b>');

                $this->extMessage("\n");
            }
            $this->extMessage("\n");
        }
        return $this->message;
    }

    private function getExpiringDomainsInfo($values, $timestamp, $day_range)
    {
        $firstLineStructure = [
            'NUMBER' => ['#', '№'],
            'DOMAIN' => ['Domain', 'Домен'],
            'PAID_UNTIL' => ['Domain Paid Until', 'Домен Оплачен До'],
            'REGISTRAR' => ['Registrar', 'Регистратор']
        ];
        $firstLineStructure = $this->matchFirstLineStructure($values[0], $firstLineStructure);

        $expiringDomainsInfo = [];

        $counter = 0;
        foreach ($values as $line) {
            if ($counter == 0) {
                $counter++;
                continue;
            }

            if ($this->isExpiring($line[$firstLineStructure['PAID_UNTIL']], $timestamp, $day_range)) {
                $expiringDomainsInfo[] = [
                    $line[$firstLineStructure['NUMBER']],
                    $line[$firstLineStructure['DOMAIN']],
                    $line[$firstLineStructure['PAID_UNTIL']],
                    $line[$firstLineStructure['REGISTRAR']]
                ];
            }
        }

        return $expiringDomainsInfo;
    }

    private function matchFirstLineStructure($firstLine, $firstLineStructure)
    {
        $matchedFirstLineStructure = [];
        $counter = 0;
        foreach ($firstLine as $value) {
            foreach ($firstLineStructure as $keyword => $range) {
                if (in_array($value, $range, true))
                    $matchedFirstLineStructure[$keyword] = $counter;
            }
            $counter++;
        }

        return $matchedFirstLineStructure;
    }

    private function isExpiring($paidUntil, $timestamp, $dayRange)
    {
        $paidUntil = DateTime::createFromFormat('Y-m-d', $paidUntil);
        if ($paidUntil) $paidUntil = $paidUntil->getTimestamp();
        else return false;

        $difference = ($dayRange + 1) * 24 * 60 * 60;

        return ($timestamp + $difference) > $paidUntil;
    }

    private function isExpired($paidUntil, $timestamp)
    {
        $paidUntil = DateTime::createFromFormat('Y-m-d', $paidUntil);
        if ($paidUntil) $paidUntil = $paidUntil->getTimestamp();
        else return false;

        return $paidUntil < $timestamp;
    }

}