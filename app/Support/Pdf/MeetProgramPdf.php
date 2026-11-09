<?php

namespace App\Support\Pdf;

use FPDF;

class MeetProgramPdf extends FPDF
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
     * @param  list<array{
     *     heading: string,
     *     heats: list<array{
     *         label: string,
     *         rows: list<array{lane: string, name: string, age: string, team: string, seed: string}>
     *     }>
     * }>  $sections
     */
    public function build(array $sections): void
    {
        $this->AddPage();
        $this->SetFont('Helvetica', 'B', 11);
        $this->Cell(0, 6, $this->encode('Meet Program - '.$this->competitionName), 0, 1, 'L');
        $this->SetFont('Helvetica', '', 9);
        $this->Cell(0, 5, $this->encode($this->scopeLabel), 0, 1, 'L');
        $this->Ln(2);

        if ($sections === []) {
            $this->SetFont('Helvetica', 'I', 10);
            $this->Cell(0, 6, $this->encode('No events match this selection.'), 0, 1, 'L');

            return;
        }

        foreach ($sections as $section) {
            $this->renderSection($section['heading'], $section['heats']);
        }
    }

    public function Header(): void
    {
        $this->SetFont('Helvetica', '', 8);
        $this->Cell(0, 4, $this->encode('Meet Program · '.$this->generatedAt), 0, 0, 'L');
        $this->Cell(0, 4, $this->encode('Page '.$this->PageNo()), 0, 1, 'R');
        $this->SetFont('Helvetica', 'B', 10);
        $this->Cell(0, 5, $this->encode($this->competitionName.' - '.$this->competitionDate), 0, 1, 'L');
        $this->SetFont('Helvetica', '', 9);
        $this->Cell(0, 5, $this->encode('Meet Program'), 0, 1, 'L');
        $this->SetDrawColor(0);
        $this->Line(12, $this->GetY(), 204, $this->GetY());
        $this->Ln(3);
    }

    /**
     * @param  list<array{
     *     label: string,
     *     rows: list<array{lane: string, name: string, age: string, team: string, seed: string}>
     * }>  $heats
     */
    private function renderSection(string $heading, array $heats): void
    {
        $needed = 18 + (max(count($heats), 1) * 8);
        if ($this->GetY() + min($needed, 40) > $this->PageBreakTrigger) {
            $this->AddPage();
        }

        $this->SetFont('Helvetica', 'B', 10);
        $this->MultiCell(0, 5, $this->encode($heading), 0, 'L');
        $this->Ln(0.5);

        $this->SetFont('Helvetica', 'B', 8);
        $this->SetFillColor(240, 240, 240);
        $this->rowCells('Lane', 'Name', 'Age', 'Team', 'Seed Time', '', true);
        $this->SetFont('Helvetica', '', 8);

        if ($heats === []) {
            $this->Cell(0, 5, $this->encode('No heats generated for this event.'), 0, 1, 'L');
            $this->Ln(3);

            return;
        }

        foreach ($heats as $heat) {
            $this->renderHeat($heading, $heat['label'], $heat['rows']);
        }

        $this->Ln(3);
    }

    /**
     * @param  list<array{lane: string, name: string, age: string, team: string, seed: string}>  $rows
     */
    private function renderHeat(string $eventHeading, string $heatLabel, array $rows): void
    {
        if ($this->GetY() + 12 > $this->PageBreakTrigger) {
            $this->AddPage();
            $this->SetFont('Helvetica', 'B', 9);
            $this->MultiCell(0, 5, $this->encode('('.$eventHeading.')'), 0, 'L');
            $this->SetFont('Helvetica', 'B', 8);
            $this->SetFillColor(240, 240, 240);
            $this->rowCells('Lane', 'Name', 'Age', 'Team', 'Seed Time', '', true);
            $this->SetFont('Helvetica', '', 8);
        }

        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell(0, 5, $this->encode($heatLabel), 0, 1, 'L');
        $this->SetFont('Helvetica', '', 8);

        if ($rows === []) {
            $this->Cell(0, 5, $this->encode('No swimmers assigned to this heat.'), 0, 1, 'L');

            return;
        }

        foreach ($rows as $row) {
            if ($this->GetY() + 6 > $this->PageBreakTrigger) {
                $this->AddPage();
                $this->SetFont('Helvetica', 'B', 9);
                $this->MultiCell(
                    0,
                    5,
                    $this->encode($this->continuationLabel($heatLabel, $eventHeading)),
                    0,
                    'L',
                );
                $this->SetFont('Helvetica', 'B', 8);
                $this->SetFillColor(240, 240, 240);
                $this->rowCells('Lane', 'Name', 'Age', 'Team', 'Seed Time', '', true);
                $this->SetFont('Helvetica', '', 8);
            }

            $this->rowCells(
                $row['lane'],
                $row['name'],
                $row['age'],
                $row['team'],
                $row['seed'],
                '_____',
                false,
            );
        }
    }

    private function continuationLabel(string $heatLabel, string $eventHeading): string
    {
        if (preg_match('/^Heat\s+(\d+)/i', $heatLabel, $matches) === 1) {
            return 'Heat   '.$matches[1].' Finals (#'.$eventHeading.')';
        }

        return $heatLabel.' (#'.$eventHeading.')';
    }

    private function rowCells(
        string $lane,
        string $name,
        string $age,
        string $team,
        string $seed,
        string $blank,
        bool $fill,
    ): void {
        $this->Cell(12, 5, $this->encode($lane), 0, 0, 'C', $fill);
        $this->Cell(70, 5, $this->encode($this->truncate($name, 42)), 0, 0, 'L', $fill);
        $this->Cell(12, 5, $this->encode($age), 0, 0, 'C', $fill);
        $this->Cell(48, 5, $this->encode($this->truncate($team, 28)), 0, 0, 'L', $fill);
        $this->Cell(26, 5, $this->encode($seed), 0, 0, 'R', $fill);
        $this->Cell(24, 5, $this->encode($blank), 0, 1, 'R', $fill);
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
