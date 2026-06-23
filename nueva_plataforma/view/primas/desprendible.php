<?php
require_once __DIR__ . '/../../../fpdf/fpdf.php';

function primaParam(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}

function primaMoney($value): string
{
    return number_format((float) $value, 0, ',', '.');
}

function primaValorEnLetras(float $valor): string
{
    if (class_exists('NumberFormatter')) {
        $fmt = new NumberFormatter('es_CO', NumberFormatter::SPELLOUT);
        $texto = $fmt->formatCurrency($valor, 'COP');
        $texto = preg_replace('/\bcoma\b.*$/i', '', (string) $texto);
        return strtoupper(trim($texto)) . ' PESOS';
    }

    return primaMoney($valor) . ' PESOS';
}

$datos = [
    'cedula' => primaParam('cedula'),
    'nombre' => primaParam('nombre'),
    'cargo' => primaParam('cargo'),
    'fechaini' => primaParam('fechaini'),
    'fechafin' => primaParam('fechafin'),
    'diastrabajados' => primaParam('diastrabajados', '0'),
    'totaldeveng' => (float) primaParam('totaldeveng', '0'),
    'firma' => primaParam('firma'),
    'sede' => primaParam('sede'),
    'transporte' => (float) primaParam('transporte', '0'),
    'sueldobasico' => (float) primaParam('sueldobasico', '0'),
    'semestre' => primaParam('semestre') === 'Segunda' ? '2' : '1',
    'confirmado' => primaParam('confirmado'),
];

class PrimaPDF extends FPDF
{
    public array $datos = [];

    public function Header(): void
    {
        $logo = __DIR__ . '/../../../images/logoDesprendible.jpg';

        $this->SetLineWidth(0.5);
        $this->SetDrawColor(0, 0, 0);
        $this->Rect(10, 10, 190, 60, 'D');

        $this->SetFont('Arial', 'B', 25);
        $this->SetY(15);
        $this->Cell(150, 10, 'LIQUIDACION DE PRIMA', 0, 1, 'C');

        if (file_exists($logo)) {
            $this->Image($logo, 165, 12, 30);
        }

        $this->SetY(30);
        $this->SetX(20);
        $this->SetDrawColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 10);

        $lineas = [
            'CEDULA:  ' . $this->datos['cedula'],
            'NOMBRE:  ' . $this->datos['nombre'],
            'CARGO:  ' . $this->datos['cargo'],
            'SEMESTRE:  ' . $this->datos['semestre'],
            'FECHA INICIAL:  ' . $this->datos['fechaini'] . '    FECHA CORTE:  ' . $this->datos['fechafin'],
        ];

        foreach ($lineas as $linea) {
            $this->SetX(20);
            $this->Cell(150, 7, $linea, 1);
            $this->Ln();
        }

        $this->SetX(20);
        $this->Cell(100, 7, 'No DAVIVIENDA', 1);
        $this->Cell(50, 7, $this->datos['sede'], 1);
        $this->Ln();
    }

    public function Footer(): void
    {
        $this->SetY(-10);
        $this->SetFont('Times', 'I', 8);
    }
}

$pdf = new PrimaPDF();
$pdf->datos = $datos;
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Times', '', 12);
$pdf->Ln(20);
$pdf->SetY($pdf->GetY() - 10);

$pdf->SetLineWidth(0.5);
$pdf->SetDrawColor(0, 0, 0);
$pdf->Rect(10, 72, 95, 15);
$pdf->Rect(105, 72, 95, 15);
$pdf->Cell(190, 2, 'CALCULO PRIMA                                                                      BASE PARA EL CALCULO', 0, 1, 'C');
$pdf->SetY($pdf->GetY() + 5);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(60, 5, 'Concepto', 1);
$pdf->Cell(35, 5, 'Valor', 1);
$pdf->Cell(55, 5, 'Concepto', 1);
$pdf->Cell(40, 5, 'Valor', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 5, 'Dias trabajados', 1);
$pdf->Cell(35, 5, $datos['diastrabajados'], 1);
$pdf->Cell(55, 5, 'Sueldo basico', 1);
$pdf->Cell(40, 5, primaMoney($datos['sueldobasico']), 1);
$pdf->Ln();

$pdf->Cell(60, 5, '', 1);
$pdf->Cell(35, 5, '', 1);
$pdf->Cell(55, 5, 'Subsidio transporte', 1);
$pdf->Cell(40, 5, primaMoney($datos['transporte']), 1);
$pdf->Ln();

$pdf->Cell(60, 10, 'TOTAL PRIMA', 1);
$pdf->Cell(35, 10, primaMoney($datos['totaldeveng']), 1);
$pdf->Cell(55, 10, '', 1);
$pdf->Cell(40, 10, '', 1);
$pdf->Ln();

$pdf->Cell(190, 10, 'TOTAL                                                                                                                    VALOR A PAGAR:  ' . primaMoney($datos['totaldeveng']), 1);
$pdf->Ln();
$pdf->Cell(190, 10, 'VALOR EN LETRAS:  ' . primaValorEnLetras($datos['totaldeveng']), 1);
$pdf->Ln(20);

$pdf->SetDrawColor(255, 255, 255);
$pdf->Cell(95, 10, '', 1);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetFont('Arial', '', 7);

if ($datos['confirmado'] !== '' && $datos['firma'] !== '') {
    $firma = __DIR__ . '/../../../imgHojasDeVida/' . $datos['firma'];
    if (file_exists($firma)) {
        $pdf->Image($firma, $pdf->GetX() + 5, $pdf->GetY() + 20, 40, 14);
    } else {
        $pdf->Cell(0, 5, 'Si no se ve la firma revisar foto y volver a cargar', 0, 1);
    }
}

$pdf->MultiCell(95, 25, 'RECIBI A SATISFACCION Y ACEPTO EN TODAS SUS PARTES ESTE PAGO     ' . $datos['confirmado'], 1);
$pdf->Ln(10);

$pdf->Output('I', 'desprendible_prima_' . preg_replace('/\D+/', '', $datos['cedula']) . '.pdf');
