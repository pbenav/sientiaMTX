<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader\PageBoundaries;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use App\Models\Activity;

class PdfStampingService
{
    /**
     * Estampa el pie de página estilo CSV en todas las páginas de un PDF.
     *
     * @param string $pdfContent Contenido binario del PDF original.
     * @param string $signerName Nombre del firmante.
     * @param string $verificationCode Código de verificación (ej. UUID).
     * @param string $verificationUrl URL para el código QR.
     * @return string Contenido binario del PDF modificado.
     */
    public function stampPdf(string $pdfContent, string $signerName, string $verificationCode, string $verificationUrl): string
    {
        // Crear un archivo temporal para el PDF original porque FPDI necesita un archivo físico o un stream.
        $tempFile = tempnam(sys_get_temp_dir(), 'fpdi_');
        file_put_contents($tempFile, $pdfContent);

        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($tempFile);

        // Generar el código QR y guardarlo temporalmente
        // QrCode devuelve un string con el contenido PNG
        $qrContent = QrCode::format('png')->size(150)->margin(0)->generate($verificationUrl);
        $qrFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        file_put_contents($qrFile, $qrContent);

        $currentDate = now()->format('d/m/Y');

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            // Importar página
            $templateId = $pdf->importPage($pageNo, PageBoundaries::CROP_BOX);
            
            // Obtener el tamaño de la página original
            $size = $pdf->getTemplateSize($templateId);
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Coordenadas y dimensiones del sello en la parte inferior
            // Vamos a poner el sello al final de la página (margen inferior).
            // Asumimos un A4 estándar o basamos las medidas en $size.
            
            $margin = 15;
            $boxHeight = 22;
            $boxWidth = $size['width'] - ($margin * 2);
            $x = $margin;
            $y = $size['height'] - $margin - $boxHeight;

            // Dibujar el fondo blanco para tapar posibles textos debajo
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect($x, $y, $boxWidth, $boxHeight, 'DF'); // DF = Draw + Fill (borde y fondo)

            // Líneas interiores de la tabla
            // Fila 1 (Texto de verificación): altura 8
            // Fila 2 (Firmado por): altura 7
            // Fila 3 (Verificación): altura 7
            $row1Y = $y + 8;
            $row2Y = $row1Y + 7;
            
            // Columna principal vs Columna de paginación (derecha) vs QR (extremo derecho)
            $qrWidth = 20;
            $qrX = $x + $boxWidth - $qrWidth - 1; // 1mm de padding interno
            $qrY = $y + 1; // 1mm padding interno
            
            // Línea separadora del QR
            $pdf->Line($qrX - 1, $y, $qrX - 1, $y + $boxHeight);
            
            // Columna Paginación/Fecha (a la izquierda del QR)
            $colDataWidth = 35;
            $colDataX = $qrX - 1 - $colDataWidth;
            
            // Línea separadora de la columna de fecha/pág
            $pdf->Line($colDataX, $row1Y, $colDataX, $y + $boxHeight);
            
            // Líneas horizontales
            $pdf->Line($x, $row1Y, $qrX - 1, $row1Y); // separa Cabecera
            $pdf->Line($x, $row2Y, $qrX - 1, $row2Y); // separa Firmado y Verificación
            
            // Separador "FIRMADO POR" (etiqueta) y su valor
            $labelWidth = 35;
            $pdf->Line($x + $labelWidth, $row1Y, $x + $labelWidth, $y + $boxHeight);

            // ----- TEXTOS -----
            // Configuración de fuente estándar (Helvetica)
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetTextColor(0, 0, 0);

            // Fila 1: Cabecera
            $headerText1 = mb_convert_encoding('Puede verificar la integridad de este documento mediante la lectura del código QR adjunto o mediante el acceso', 'ISO-8859-1', 'UTF-8');
            $headerText2 = mb_convert_encoding('a la dirección ' . $verificationUrl . ' indicando el código de VERIFICACIÓN', 'ISO-8859-1', 'UTF-8');
            $pdf->SetXY($x, $y + 1);
            $pdf->Cell($qrX - 1 - $x, 3.5, $headerText1, 0, 1, 'C');
            $pdf->SetXY($x, $y + 4.5);
            $pdf->Cell($qrX - 1 - $x, 3.5, $headerText2, 0, 1, 'C');

            // Fila 2: Firmado por
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetXY($x, $row1Y);
            $pdf->Cell($labelWidth, 7, 'FIRMADO POR', 0, 0, 'C');
            
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY($x + $labelWidth + 2, $row1Y);
            $pdf->Cell($colDataX - ($x + $labelWidth) - 4, 7, mb_convert_encoding($signerName, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
            
            $pdf->SetXY($colDataX, $row1Y);
            $pdf->Cell($colDataWidth, 7, $currentDate, 0, 0, 'C');

            // Fila 3: Verificación
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetXY($x, $row2Y);
            $pdf->Cell($labelWidth, 7, mb_convert_encoding('VERIFICACIÓN', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
            
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetXY($x + $labelWidth + 2, $row2Y);
            $pdf->Cell($colDataX - ($x + $labelWidth) - 4, 7, $verificationCode, 0, 0, 'L');
            
            $pdf->SetXY($colDataX, $row2Y);
            $pdf->Cell($colDataWidth, 7, mb_convert_encoding('PÁG. ' . $pageNo . '/' . $pageCount, 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');

            // ----- QR CODE -----
            // Insertar imagen PNG del QR
            $pdf->Image($qrFile, $qrX, $qrY, $qrWidth - 2, $qrWidth - 2, 'PNG');
        }

        // Limpiar archivos temporales
        @unlink($tempFile);
        @unlink($qrFile);

        return $pdf->Output('S'); // Retorna el contenido binario del PDF
    }
}
