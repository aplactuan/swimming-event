<?php

namespace App\Support\Pdf;

use FPDF;

class ResultsBookPdf extends FPDF
{
    public function __construct(
        private string $competitionName,
        private string $competitionDate,
        private string $scopeLabel,
        private string $generatedAt,
    ) {
        parent::__construct('P', 'mm', 'Letter');
        $this->SetAutoPageBreak(true, 18);
        $this->SetMargins(12, 14, 12);
    }

    /**
     * @param  list<array{heading: string, rows: list<array{place: string, name: string, age: string, team: string, time: string, points: string}>}>  $sections
     */
    public function build(array $sections): void
    {
        $this->AddPage();
        $this->SetFont('Helvetica', 'B', 11);
        $this->Cell(0, 6, $this->encode('Results - '.$this->competitionName), 0, 1, 'L');
        $this->SetFont('Helvetica', '', 9);
        $this->Cell(0, 5, $this->encode($this->scopeLabel), 0, 1, 'L');
        $this->Ln(2);

        if ($sections === []) {
            $this->SetFont('Helvetica', 'I', 10);
            $this->Cell(0, 6, $this->encode('No events match this selection.'), 0, 1, 'L');

            return;
        }

        foreach ($sections as $section) {
            $this->renderSection($section['heading'], $section['rows']);
        }
    }

    public function Header(): void
    {
        $this->SetFont('Helvetica', '', 8);
        $this->Cell(0, 4, $this->encode('Swim Meet Results · '.$this->generatedAt), 0, 0, 'L');
        $this->Cell(0, 4, $this->encode('Page '.$this->PageNo()), 0, 1, 'R');
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(0, 5, $this->encode($this->competitionName.' - '.$this->competitionDate), 0, 1, 'L');
        $this->SetDrawColor(0);
        $this->Line(12, $this->GetY(), 204, $this->GetY());
        $this->Ln(3);
    }

    /**
     * @param  list<array{place: string, name: string, age: string, team: string, time: string, points: string}>  $rows
     */
    private function renderSection(string $heading, array $rows): void
    {
        $needed = 16 + (max(count($rows), 1) * 5);
        if ($this->GetY() + min($needed, 40) > $this->PageBreakTrigger) {
            $this->AddPage();
        }

        $this->SetFont('Helvetica', 'B', 10);
        $this->MultiCell(0, 5, $this->encode($heading), 0, 'L');
        $this->Ln(1);

        $this->SetFont('Helvetica', 'B', 8);
        $this->SetFillColor(240, 240, 240);
        $this->rowCells('Place', 'Name', 'Age', 'Team', 'Finals Time', 'Points', true);
        $this->SetFont('Helvetica', '', 8);

        if ($rows === []) {
            $this->Cell(0, 5, $this->encode('No results recorded for this event.'), 0, 1, 'L');
            $this->Ln(3);

            return;
        }

        foreach ($rows as $row) {
            if ($this->GetY() + 6 > $this->PageBreakTrigger) {
                $this->AddPage();
                $this->SetFont('Helvetica', 'B', 9);
                $this->MultiCell(0, 5, $this->encode('('.$heading.')'), 0, 'L');
                $this->SetFont('Helvetica', 'B', 8);
                $this->rowCells('Place', 'Name', 'Age', 'Team', 'Finals Time', 'Points', true);
                $this->SetFont('Helvetica', '', 8);
            }

            $this->rowCells(
                $row['place'],
                $row['name'],
                $row['age'],
                $row['team'],
                $row['time'],
                $row['points'],
                false,
            );
        }

        $this->Ln(4);
    }

    private function rowCells(
        string $place,
        string $name,
        string $age,
        string $team,
        string $time,
        string $points,
        bool $fill,
    ): void {
        $this->Cell(12, 5, $this->encode($place), 0, 0, 'C', $fill);
        $this->Cell(58, 5, $this->encode($this->truncate($name, 34)), 0, 0, 'L', $fill);
        $this->Cell(12, 5, $this->encode($age), 0, 0, 'C', $fill);
        $this->Cell(62, 5, $this->encode($this->truncate($team, 36)), 0, 0, 'L', $fill);
        $this->Cell(24, 5, $this->encode($time), 0, 0, 'R', $fill);
        $this->Cell(14, 5, $this->encode($points), 0, 1, 'R', $fill);
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
