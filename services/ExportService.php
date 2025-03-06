<?php
namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use TCPDF;

class ExportService {
    
    /**
     * Exporteer transacties naar Excel
     *
     * @param array $transactions De transacties om te exporteren
     * @param string $filename De bestandsnaam voor het Excel bestand
     * @return string Het pad naar het geëxporteerde bestand
     */
    public function exportTransactionsToExcel($transactions, $filename = 'transacties.xlsx') {
        // Nieuwe spreadsheet maken
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Titel van het werkblad instellen
        $sheet->setTitle('Transacties');
        
        // Headers instellen
        $headers = ['Datum', 'Beschrijving', 'Categorie', 'Rekening', 'Type', 'Bedrag'];
        $sheet->fromArray($headers, NULL, 'A1');
        
        // Headers opmaken
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
        
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // Data toevoegen
        $row = 2;
        foreach ($transactions as $transaction) {
            $sheet->setCellValue('A' . $row, date('d-m-Y', strtotime($transaction['date'])));
            $sheet->setCellValue('B' . $row, $transaction['description'] ?: '');
            $sheet->setCellValue('C' . $row, $transaction['category_name'] ?: '');
            $sheet->setCellValue('D' . $row, $transaction['account_name'] ?: '');
            $sheet->setCellValue('E' . $row, $this->translateTransactionType($transaction['type']));
            
            // Bedrag opmaken afhankelijk van type
            $amount = $transaction['amount'];
            $amountFormatted = '€' . number_format($amount, 2, ',', '.');
            $sheet->setCellValue('F' . $row, $amountFormatted);
            
            // Kleuren toepassen op bedragen
            if ($transaction['type'] === 'expense') {
                $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('FF0000');
            } elseif ($transaction['type'] === 'income') {
                $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('008000');
            }
            
            $row++;
        }
        
        // Automatische kolombreedte
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Zebra-striping toepassen op rijen
        for ($i = 2; $i < $row; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle('A'.$i.':F'.$i)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F3F3F3');
            }
        }
        
        // Bestandspad instellen
        $filePath = __DIR__ . '/../public/exports/' . $filename;
        
        // Zorg ervoor dat de directory bestaat
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        
        // Excel bestand schrijven
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        
        return $filePath;
    }
    
    /**
     * Exporteer transacties naar PDF
     *
     * @param array $transactions De transacties om te exporteren
     * @param array $metadata Metadata zoals titel, periode, etc.
     * @param string $filename De bestandsnaam voor het PDF bestand
     * @return string Het pad naar het geëxporteerde bestand
     */
    public function exportTransactionsToPDF($transactions, $metadata, $filename = 'transacties.pdf') {
        // Nieuwe PDF maken
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        
        // Document informatie instellen
        $pdf->SetCreator('Financieel Beheer');
        $pdf->SetAuthor($metadata['user_name'] ?? 'Gebruiker');
        $pdf->SetTitle($metadata['title'] ?? 'Transactie Overzicht');
        $pdf->SetSubject('Transactie Export');
        
        // Headers en footers verwijderen
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Standaard lettertype instellen
        $pdf->SetDefaultMonospacedFont('courier');
        
        // Marges instellen
        $pdf->SetMargins(15, 15, 15);
        
        // Auto page breaks
        $pdf->SetAutoPageBreak(true, 15);
        
        // Pagina toevoegen
        $pdf->AddPage();
        
        // Titel toevoegen
        $title = $metadata['title'] ?? 'Transactie Overzicht';
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $title, 0, 1, 'C');
        
        // Periode toevoegen indien aanwezig
        if (isset($metadata['period_start']) && isset($metadata['period_end'])) {
            $period = 'Periode: ' . date('d-m-Y', strtotime($metadata['period_start'])) . ' t/m ' . date('d-m-Y', strtotime($metadata['period_end']));
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, $period, 0, 1, 'C');
        }
        
        // Extra ruimte
        $pdf->Ln(5);
        
        // Tabel headers
        $headers = [['Datum', 'Beschrijving', 'Categorie', 'Rekening', 'Type', 'Bedrag']];
        
        // Tabel data voorbereiden
        $tableData = [];
        foreach ($transactions as $transaction) {
            $type = $this->translateTransactionType($transaction['type']);
            $amount = '€' . number_format($transaction['amount'], 2, ',', '.');
            
            $tableData[] = [
                date('d-m-Y', strtotime($transaction['date'])),
                $transaction['description'] ?: '',
                $transaction['category_name'] ?: '',
                $transaction['account_name'] ?: '',
                $type,
                $amount
            ];
        }
        
        // Kolombreedte berekenen (in mm)
        $pageWidth = $pdf->getPageWidth() - 30; // 30mm marges (15mm aan elke kant)
        $colWidths = [
            25, // Datum
            50, // Beschrijving
            45, // Categorie
            45, // Rekening
            25, // Type
            30  // Bedrag
        ];
        
        // Header van de tabel
        $pdf->SetFillColor(66, 114, 196); // Blauwe achtergrond
        $pdf->SetTextColor(255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetLineWidth(0.3);
        
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        
        for ($i = 0; $i < count($headers[0]); $i++) {
            $pdf->MultiCell($colWidths[$i], 10, $headers[0][$i], 1, 'C', true, 0, '', '', true, 0, false, true, 10, 'M');
        }
        
        $pdf->Ln();
        
        // Data van de tabel
        $pdf->SetFillColor(243, 243, 243); // Lichtgrijze achtergrond voor zebra-striping
        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', '', 9);
        
        $fill = false;
        foreach ($tableData as $row) {
            // Tekstkleur instellen voor bedrag afhankelijk van type
            $transactionType = array_search($row[4], ['Uitgave', 'Inkomst', 'Overschrijving']);
            if ($transactionType === 0) { // Uitgave
                $pdf->SetTextColor(255, 0, 0);
            } elseif ($transactionType === 1) { // Inkomst
                $pdf->SetTextColor(0, 128, 0);
            } else { // Overschrijving of anders
                $pdf->SetTextColor(0);
            }
            
            // Regel toevoegen
            for ($i = 0; $i < count($row); $i++) {
                if ($i == 5) { // Alleen het bedrag met speciale tekstkleur
                    $pdf->MultiCell($colWidths[$i], 10, $row[$i], 1, 'R', $fill, 0, '', '', true, 0, false, true, 10, 'M');
                    $pdf->SetTextColor(0); // Reset tekstkleur
                } elseif ($i == 1) { // Beschrijving links uitlijnen
                    $pdf->MultiCell($colWidths[$i], 10, $row[$i], 1, 'L', $fill, 0, '', '', true, 0, false, true, 10, 'M');
                } else { // Overige cellen centreren
                    $pdf->MultiCell($colWidths[$i], 10, $row[$i], 1, 'C', $fill, 0, '', '', true, 0, false, true, 10, 'M');
                }
            }
            
            $pdf->Ln();
            $fill = !$fill; // Wissel de fill voor zebra-striping
        }
        
        // Samenvatting toevoegen
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Samenvatting', 0, 1, 'L');
        
        // Bereken totalen
        $totalIncome = 0;
        $totalExpense = 0;
        foreach ($transactions as $transaction) {
            if ($transaction['type'] === 'income') {
                $totalIncome += $transaction['amount'];
            } elseif ($transaction['type'] === 'expense') {
                $totalExpense += $transaction['amount'];
            }
        }
        
        $balance = $totalIncome - $totalExpense;
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(60, 7, 'Totaal inkomsten:', 0, 0, 'L');
        $pdf->SetTextColor(0, 128, 0);
        $pdf->Cell(50, 7, '€' . number_format($totalIncome, 2, ',', '.'), 0, 1, 'L');
        
        $pdf->SetTextColor(0);
        $pdf->Cell(60, 7, 'Totaal uitgaven:', 0, 0, 'L');
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(50, 7, '€' . number_format($totalExpense, 2, ',', '.'), 0, 1, 'L');
        
        $pdf->SetTextColor(0);
        $pdf->Cell(60, 7, 'Balans:', 0, 0, 'L');
        if ($balance >= 0) {
            $pdf->SetTextColor(0, 128, 0);
        } else {
            $pdf->SetTextColor(255, 0, 0);
        }
        $pdf->Cell(50, 7, '€' . number_format($balance, 2, ',', '.'), 0, 1, 'L');
        
        // Bestandspad instellen
        $filePath = __DIR__ . '/../public/exports/' . $filename;
        
        // Zorg ervoor dat de directory bestaat
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        
        // PDF opslaan
        $pdf->Output($filePath, 'F');
        
        return $filePath;
    }
    
    /**
     * Exporteer budgetrapport naar PDF
     *
     * @param array $budgets De budgetgegevens om te exporteren
     * @param array $metadata Metadata zoals titel, periode, etc.
     * @param string $filename De bestandsnaam voor het PDF bestand
     * @return string Het pad naar het geëxporteerde bestand
     */
    public function exportBudgetsToPDF($budgets, $metadata, $filename = 'budgetten.pdf') {
        // Nieuwe PDF maken
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Document informatie instellen
        $pdf->SetCreator('Financieel Beheer');
        $pdf->SetAuthor($metadata['user_name'] ?? 'Gebruiker');
        $pdf->SetTitle('Budget Overzicht');
        $pdf->SetSubject('Budget Export');
        
        // Headers en footers verwijderen
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Standaard lettertype instellen
        $pdf->SetDefaultMonospacedFont('courier');
        
        // Marges instellen
        $pdf->SetMargins(15, 15, 15);
        
        // Auto page breaks
        $pdf->SetAutoPageBreak(true, 15);
        
        // Pagina toevoegen
        $pdf->AddPage();
        
        // Titel toevoegen
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Budget Overzicht', 0, 1, 'C');
        
        // Periode toevoegen indien aanwezig
        if (isset($metadata['period'])) {
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 10, 'Periode: ' . $metadata['period'], 0, 1, 'C');
        }
        
        // Extra ruimte
        $pdf->Ln(5);
        
        // Budgetten weergeven
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Budgetstatus', 0, 1, 'L');
        
        // Tabel headers
        $headers = [['Categorie', 'Budget', 'Uitgegeven', 'Resterend', 'Voortgang']];
        
        // Kolombreedte berekenen (in mm)
        $pageWidth = $pdf->getPageWidth() - 30; // 30mm marges (15mm aan elke kant)
        $colWidths = [
            50, // Categorie
            30, // Budget
            30, // Uitgegeven
            30, // Resterend
            30  // Voortgang (%)
        ];
        
        // Header van de tabel
        $pdf->SetFillColor(66, 114, 196);
        $pdf->SetTextColor(255);
        $pdf->SetFont('helvetica', 'B', 10);
        
        for ($i = 0; $i < count($headers[0]); $i++) {
            $pdf->Cell($colWidths[$i], 10, $headers[0][$i], 1, 0, 'C', true);
        }
        
        $pdf->Ln();
        
        // Data van de tabel
        $pdf->SetFillColor(243, 243, 243);
        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', '', 10);
        
        $fill = false;
        foreach ($budgets as $budget) {
            $remaining = $budget['amount'] - $budget['spent'];
            $progress = $budget['amount'] > 0 ? round(($budget['spent'] / $budget['amount']) * 100, 1) : 0;
            
            // Tekstkleur bepalen op basis van budget status
            if ($budget['is_exceeded']) {
                $textColor = [255, 0, 0]; // Rood
            } elseif ($budget['is_warning']) {
                $textColor = [230, 126, 34]; // Oranje
            } else {
                $textColor = [0, 128, 0]; // Groen
            }
            
            $pdf->Cell($colWidths[0], 10, $budget['category_name'], 1, 0, 'L', $fill);
            $pdf->Cell($colWidths[1], 10, '€' . number_format($budget['amount'], 2, ',', '.'), 1, 0, 'R', $fill);
            $pdf->Cell($colWidths[2], 10, '€' . number_format($budget['spent'], 2, ',', '.'), 1, 0, 'R', $fill);
            
            // Resterende bedrag met kleur
            $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
            $pdf->Cell($colWidths[3], 10, '€' . number_format($remaining, 2, ',', '.'), 1, 0, 'R', $fill);
            
            // Voortgang met kleur
            $pdf->Cell($colWidths[4], 10, $progress . '%', 1, 0, 'C', $fill);
            $pdf->SetTextColor(0); // Reset tekstkleur
            
            $pdf->Ln();
            $fill = !$fill; // Wissel de fill voor zebra-striping
        }
        
        // Voeg een visuele representatie van de budgetten toe
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Visuele Budgetoverzicht', 0, 1, 'L');
        
        // Begin positie van de grafiek
        $graphX = 15;
        $graphY = $pdf->GetY() + 5;
        $graphWidth = 180;
        $barHeight = 15;
        $gap = 5;
        
        foreach ($budgets as $index => $budget) {
            $progress = $budget['amount'] > 0 ? min(100, ($budget['spent'] / $budget['amount']) * 100) : 0;
            $barWidth = ($progress / 100) * $graphWidth;
            
            // Kleuren bepalen op basis van budget status
            if ($budget['is_exceeded']) {
                $fillColor = [255, 0, 0]; // Rood
            } elseif ($budget['is_warning']) {
                $fillColor = [230, 126, 34]; // Oranje
            } else {
                $fillColor = [39, 174, 96]; // Groen
            }
            
            // Label tekenen
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetXY($graphX, $graphY + ($index * ($barHeight + $gap)));
            $pdf->Cell(50, $barHeight, $budget['category_name'], 0, 0, 'L');
            
            // Achtergrond van de balk tekenen
            $pdf->SetDrawColor(200, 200, 200);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Rect($graphX + 50, $graphY + ($index * ($barHeight + $gap)), $graphWidth, $barHeight, 'DF');
            
            // Voortgangsbalk tekenen
            $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
            if ($barWidth > 0) {
                $pdf->Rect($graphX + 50, $graphY + ($index * ($barHeight + $gap)), $barWidth, $barHeight, 'F');
            }
            
            // Percentage en bedrag toevoegen
            $label = number_format($progress, 1) . '% (€' . number_format($budget['spent'], 2, ',', '.') . ' / €' . number_format($budget['amount'], 2, ',', '.') . ')';
            $pdf->SetXY($graphX + 55, $graphY + ($index * ($barHeight + $gap)) + ($barHeight/2) - 2);
            $pdf->SetTextColor(50, 50, 50);
            $pdf->Cell(0, 5, $label, 0, 0, 'L');
        }
        
        // PDF opslaan
        $filePath = __DIR__ . '/../public/exports/' . $filename;
        
        // Zorg ervoor dat de directory bestaat
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        
        $pdf->Output($filePath, 'F');
        
        return $filePath;
    }
    
    /**
     * Exporteer rekeningen overzicht naar PDF
     *
     * @param array $accounts De rekeninggegevens om te exporteren
     * @param array $metadata Metadata zoals titel, periode, etc.
     * @param string $filename De bestandsnaam voor het PDF bestand
     * @return string Het pad naar het geëxporteerde bestand
     */
    public function exportAccountsToPDF($accounts, $metadata, $filename = 'rekeningen.pdf') {
        // Nieuwe PDF maken
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Document informatie instellen
        $pdf->SetCreator('Financieel Beheer');
        $pdf->SetAuthor($metadata['user_name'] ?? 'Gebruiker');
        $pdf->SetTitle('Rekeningen Overzicht');
        $pdf->SetSubject('Rekeningen Export');
        
        // Headers en footers verwijderen
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Standaard lettertype instellen
        $pdf->SetDefaultMonospacedFont('courier');
        
        // Marges instellen
        $pdf->SetMargins(15, 15, 15);
        
        // Auto page breaks
        $pdf->SetAutoPageBreak(true, 15);
        
        // Pagina toevoegen
        $pdf->AddPage();
        
        // Titel toevoegen
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Rekeningen Overzicht', 0, 1, 'C');
        
        // Datum toevoegen
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 10, 'Gegenereerd op: ' . date('d-m-Y H:i'), 0, 1, 'C');
        
        // Extra ruimte
        $pdf->Ln(5);
        
        // Totaal saldo berekenen
        $totalBalance = array_reduce($accounts, function($total, $account) {
            return $total + $account['balance'];
        }, 0);
        
        // Totaal saldo tonen
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Totaal saldo: ' . ($totalBalance >= 0 ? '' : '-') . '€' . number_format(abs($totalBalance), 2, ',', '.'), 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Ln(5);
        
        // Tabel headers
        $headers = [['Rekening', 'Type', 'Saldo', 'Valuta']];
        
        // Kolombreedte berekenen
        $colWidths = [60, 50, 40, 25];
        
        // Header van de tabel
        $pdf->SetFillColor(66, 114, 196);
        $pdf->SetTextColor(255);
        $pdf->SetFont('helvetica', 'B', 10);
        
        for ($i = 0; $i < count($headers[0]); $i++) {
            $pdf->Cell($colWidths[$i], 10, $headers[0][$i], 1, 0, 'C', true);
        }
        
        $pdf->Ln();
        
        // Data van de tabel
        $pdf->SetFillColor(243, 243, 243);
        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', '', 10);
        
        $fill = false;
        foreach ($accounts as $account) {
            $pdf->Cell($colWidths[0], 10, $account['name'], 1, 0, 'L', $fill);
            $pdf->Cell($colWidths[1], 10, $account['type_name'], 1, 0, 'L', $fill);
            
            // Saldo met kleur
            if ($account['balance'] >= 0) {
                $pdf->SetTextColor(0, 128, 0); // Groen
            } else {
                $pdf->SetTextColor(255, 0, 0); // Rood
            }
            $pdf->Cell($colWidths[2], 10, '€' . number_format($account['balance'], 2, ',', '.'), 1, 0, 'R', $fill);
            $pdf->SetTextColor(0);
            
            $pdf->Cell($colWidths[3], 10, $account['currency'], 1, 0, 'C', $fill);
            
            $pdf->Ln();
            $fill = !$fill;
        }
        
        // Grafiek toevoegen als er meerdere rekeningen zijn
        if (count($accounts) > 1) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Verdeling van saldo over rekeningen', 0, 1, 'C');
            
            // Diagram gegevens
            $labels = [];
            $values = [];
            $colors = [];
            
            foreach ($accounts as $account) {
                $labels[] = $account['name'];
                $values[] = abs($account['balance']); // Gebruik absolute waarde voor diagram
                
                // Genereer willekeurige kleuren voor het diagram
                $colors[] = [
                    rand(50, 200),
                    rand(50, 200),
                    rand(50, 200)
                ];
            }
            
            // Centreer de grafiek op de pagina
            $chartX = 55;
            $chartY = 60;
            $chartWidth = 100;
            $chartHeight = 100;
            
            // Teken cirkeldiagram
            $pdf->SetFont('helvetica', '', 8);
            $this->drawPieChart($pdf, $chartX, $chartY, $chartWidth, $labels, $values, $colors);
            
            // Teken legenda
            $legendX = 15;
            $legendY = $chartY + $chartHeight + 10;
            
            for ($i = 0; $i < count($labels); $i++) {
                // Teken kleurvak
                $pdf->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
                $pdf->Rect($legendX, $legendY + ($i * 6), 5, 5, 'F');
                
                // Teken label
                $pdf->SetXY($legendX + 8, $legendY + ($i * 6) - 1);
                $pdf->Cell(60, 6, $labels[$i], 0, 0, 'L');
                
                // Teken bedrag
                $pdf->SetXY($legendX + 70, $legendY + ($i * 6) - 1);
                if ($accounts[$i]['balance'] >= 0) {
                    $pdf->SetTextColor(0, 128, 0);
                } else {
                    $pdf->SetTextColor(255, 0, 0);
                }
                $pdf->Cell(30, 6, '€' . number_format($accounts[$i]['balance'], 2, ',', '.'), 0, 0, 'R');
                $pdf->SetTextColor(0);
                
                // Teken percentage
                $pdf->SetXY($legendX + 105, $legendY + ($i * 6) - 1);
                $percentage = array_sum($values) > 0 ? ($values[$i] / array_sum($values)) * 100 : 0;
                $pdf->Cell(20, 6, number_format($percentage, 1) . '%', 0, 0, 'R');
            }
        }
        
        // PDF opslaan
        $filePath = __DIR__ . '/../public/exports/' . $filename;
        
        // Zorg ervoor dat de directory bestaat
        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }
        
        $pdf->Output($filePath, 'F');
        
        return $filePath;
    }
    
    /**
     * Helper methode om een cirkeldiagram te tekenen
     */
    private function drawPieChart($pdf, $x, $y, $radius, $labels, $values, $colors) {
        // Controleer of er waarden zijn
        if (empty($values) || array_sum($values) == 0) {
            return;
        }
        
        // Bereken de totale som
        $total = array_sum($values);
        
        // Begin- en eindhoeken
        $currentAngle = 0;
        
        // Teken elke segment
        for ($i = 0; $i < count($values); $i++) {
            // Bereken de hoek voor dit segment (in graden)
            $sectorAngle = ($values[$i] / $total) * 360;
            
            // Bereken start- en eindpunten
            $endAngle = $currentAngle + $sectorAngle;
            
            // Converteer hoeken naar radialen
            $currentAngleRad = deg2rad($currentAngle);
            $endAngleRad = deg2rad($endAngle);
            
            // Bereken punten voor het segment
            $x1 = $x + $radius * cos($currentAngleRad);
            $y1 = $y + $radius * sin($currentAngleRad);
            $x2 = $x + $radius * cos($endAngleRad);
            $y2 = $y + $radius * sin($endAngleRad);
            
            // Stel de vulkleur in
            $pdf->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
            
            // Teken het segment
            $pdf->Pie($x, $y, $radius, $currentAngle, $endAngle, 'FD', false, 0, 2);
            
            // Update de huidige hoek
            $currentAngle = $endAngle;
        }
    }
    
    /**
     * Helper methode voor het vertalen van transactietypes
     */
    private function translateTransactionType($type) {
        switch ($type) {
            case 'expense':
                return 'Uitgave';
            case 'income':
                return 'Inkomst';
            case 'transfer':
                return 'Overschrijving';
            default:
                return $type;
        }
    }
}