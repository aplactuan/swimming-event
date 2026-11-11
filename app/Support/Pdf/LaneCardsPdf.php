<?php

namespace App\Support\Pdf;

use FPDF;

class LaneCardsPdf extends FPDF
{
    /**
     * Philippine legal paper: 8.5in x 13in.
     */
    public const PAGE_WIDTH_MM = 215.9;

    public const PAGE_HEIGHT_MM = 330.2;

    public const COLUMNS = 2;

    public const ROWS = 5;

    public const CARDS_PER_PAGE = self::COLUMNS * self::ROWS;

    private const MARGIN_X = 6.0;

    private const MARGIN_Y = 6.0;

    private const GUTTER_X = 3.0;

    private float $cardHeight;

    private float $cardWidth;

    private int $cardsOnPage = 0;

    public function __construct()
    {
        parent::__construct('P', 'mm', [self::PAGE_WIDTH_MM, self::PAGE_HEIGHT_MM]);
        $this->SetAutoPageBreak(false);
        $this->SetMargins(self::MARGIN_X, self::MARGIN_Y, self::MARGIN_X);
        $this->cardWidth = (self::PAGE_WIDTH_MM - (self::MARGIN_X * 2) - self::GUTTER_X) / self::COLUMNS;
        $this->cardHeight = (self::PAGE_HEIGHT_MM - (self::MARGIN_Y * 2)) / self::ROWS;
    }

    /**
     * @param  list<array{
     *     meet: string,
     *     event: string,
     *     heat: string,
     *     lane: string,
     *     seed: string,
     *     name: string,
     *     age: string,
     *     team: string
     * }>  $cards
     */
    public function build(array $cards): void
    {
        if ($cards === []) {
            $this->AddPage();
            $this->SetFont('Helvetica', 'I', 11);
            $this->Cell(0, 8, $this->encode('No assigned lanes to print.'), 0, 1, 'L');

            return;
        }

        foreach ($cards as $card) {
            $this->renderCard($card);
        }
    }

    /**
     * @param  array{
     *     meet: string,
     *     event: string,
     *     heat: string,
     *     lane: string,
     *     seed: string,
     *     name: string,
     *     age: string,
     *     team: string
     * }  $card
     */
    private function renderCard(array $card): void
    {
        if ($this->PageNo() === 0 || $this->cardsOnPage >= self::CARDS_PER_PAGE) {
            $this->AddPage();
            $this->cardsOnPage = 0;
        }

        $column = intdiv($this->cardsOnPage, self::ROWS);
        $row = $this->cardsOnPage % self::ROWS;
        $left = self::MARGIN_X + ($column * ($this->cardWidth + self::GUTTER_X));
        $top = self::MARGIN_Y + ($row * $this->cardHeight);

        $this->SetXY($left, $top);

        $line = max(4.2, min(5.2, ($this->cardHeight - 3.0) / 7));
        $innerWidth = $this->cardWidth - 1.5;

        $this->SetFont('Helvetica', 'B', 7);
        $this->Cell($innerWidth, $line, $this->encode($this->truncate($card['meet'], 48)), 0, 1, 'L');

        $this->SetX($left);
        $this->SetFont('Helvetica', 'B', 7);
        $this->Cell($innerWidth, $line, $this->encode($this->truncate($card['event'], 52)), 0, 1, 'L');

        $this->SetX($left);
        $this->SetFont('Helvetica', '', 8);
        $this->Cell(
            $innerWidth,
            $line,
            $this->encode(sprintf(
                'Heat: %s   Lane: %s   Seed: %s',
                $card['heat'],
                $card['lane'],
                $card['seed'],
            )),
            0,
            1,
            'L',
        );

        $this->SetX($left);
        $this->SetFont('Helvetica', 'B', 9);
        $ageWidth = 18.0;
        $nameWidth = $innerWidth - $ageWidth;
        $this->Cell($nameWidth, $line + 0.6, $this->encode($this->truncate($card['name'], 32)), 0, 0, 'L');
        $this->SetFont('Helvetica', 'B', 9);
        $ageLabel = $card['age'] !== '' ? 'Age: '.$card['age'] : 'Age:';
        $this->Cell($ageWidth, $line + 0.6, $this->encode($ageLabel), 0, 1, 'R');

        $this->SetX($left);
        $this->SetFont('Helvetica', '', 7);
        $teamLabel = $card['team'] !== '' ? 'Team: '.$card['team'] : 'Team:';
        $this->Cell($innerWidth * 0.68, $line, $this->encode($this->truncate($teamLabel, 36)), 0, 0, 'L');
        $this->Cell($innerWidth * 0.32, $line, $this->encode('ID#:'), 0, 1, 'R');

        $this->SetX($left);
        $this->SetFont('Helvetica', '', 7);
        $blank = '___:___.___';
        $this->Cell($innerWidth, $line, $this->encode("T1 {$blank}  T2 {$blank}  T3 {$blank}"), 0, 1, 'L');

        $this->SetX($left);
        $this->Cell($innerWidth, $line, $this->encode("Official Time  {$blank}"), 0, 1, 'L');

        $separatorY = $top + $this->cardHeight - 1.4;
        $this->SetDrawColor(0);
        $this->SetLineWidth(0.2);
        $this->Line($left, $separatorY, $left + $this->cardWidth - 1.0, $separatorY);

        if ($column === 0) {
            $this->SetDrawColor(180);
            $this->SetLineWidth(0.15);
            $gutterX = $left + $this->cardWidth + (self::GUTTER_X / 2);
            $this->Line($gutterX, $top + 1.0, $gutterX, $separatorY);
            $this->SetDrawColor(0);
        }

        $this->cardsOnPage++;
    }

    private function truncate(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max - 1).'.';
    }

    private function encode(string $value): string
    {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);

        return $converted === false ? $value : $converted;
    }
}
