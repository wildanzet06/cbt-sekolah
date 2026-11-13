<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penulis file Excel (.xlsx) sederhana tanpa dependensi eksternal.
 *
 * Aplikasi ini tidak menyertakan folder vendor/, jadi PhpSpreadsheet tidak
 * tersedia saat runtime. Kelas ini membuat file .xlsx minimal (satu sheet)
 * memakai ekstensi ZipArchive yang sudah menjadi syarat aplikasi.
 *
 * Pemakaian:
 *   $this->load->library('simple_xlsx');
 *   $this->simple_xlsx->download('rekap.xlsx', 'Rekap', $headers, $rows, $widths);
 */
class Simple_xlsx {

    /**
     * Kirim file .xlsx langsung ke browser.
     *
     * @param string $filename Nama file unduhan
     * @param string $sheetName Nama sheet (maks. 31 karakter)
     * @param array  $headers   Judul kolom (baris pertama)
     * @param array  $rows      Baris data (array of array)
     * @param array  $widths    Lebar kolom opsional (angka, urut sesuai header)
     */
    public function download($filename, $sheetName, $headers, $rows, $widths = array()) {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            show_error('Gagal membuat file Excel sementara.', 500);
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            show_error('Gagal membuat file Excel.', 500);
        }

        $zip->addFromString('[Content_Types].xml', $this->_content_types());
        $zip->addFromString('_rels/.rels', $this->_rels());
        $zip->addFromString('xl/workbook.xml', $this->_workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->_workbook_rels());
        $zip->addFromString('xl/styles.xml', $this->_styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->_sheet($headers, $rows, $widths));
        $zip->close();

        $data = file_get_contents($tmp);
        @unlink($tmp);

        if ($data === false) {
            show_error('Gagal membaca file Excel.', 500);
        }

        // Nama file aman untuk header HTTP
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $filename);
        if ($filename === '' || substr($filename, -5) !== '.xlsx') {
            $filename = 'rekap.xlsx';
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($data));
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        echo $data;
        exit;
    }

    // =====================================================================
    // BAGIAN XML
    // =====================================================================

    private function _content_types() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function _rels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function _workbook($sheetName) {
        $sheetName = $this->_sheet_name($sheetName);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->_xml($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function _workbook_rels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function _styles() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="2">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '</fills>'
            . '<borders count="1">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function _sheet($headers, $rows, $widths) {
        $headers = array_values((array) $headers);
        $rows    = array_values((array) $rows);
        $widths  = array_values((array) $widths);

        $jumlah_kolom = count($headers);
        foreach ($rows as $row) {
            $jumlah_kolom = max($jumlah_kolom, count((array) $row));
        }
        if ($jumlah_kolom < 1) {
            $jumlah_kolom = 1;
        }

        $jumlah_baris = count($rows) + (empty($headers) ? 0 : 1);
        $ref_akhir = $this->_col($jumlah_kolom) . max(1, $jumlah_baris);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . $ref_akhir . '"/>'
            . '<sheetViews><sheetView workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>';

        // Lebar kolom
        $xml .= '<cols>';
        for ($i = 1; $i <= $jumlah_kolom; $i++) {
            $w = isset($widths[$i - 1]) ? (float) $widths[$i - 1] : 18;
            if ($w <= 0) {
                $w = 18;
            }
            $xml .= '<col min="' . $i . '" max="' . $i . '" width="' . $w . '" customWidth="1"/>';
        }
        $xml .= '</cols>';

        $xml .= '<sheetData>';
        $baris = 1;

        // Baris judul (tebal)
        if (!empty($headers)) {
            $xml .= '<row r="1">';
            foreach ($headers as $i => $judul) {
                $xml .= $this->_cell($this->_col($i + 1) . '1', $judul, 1);
            }
            $xml .= '</row>';
            $baris = 2;
        }

        // Baris data
        foreach ($rows as $row) {
            $row = array_values((array) $row);
            $xml .= '<row r="' . $baris . '">';
            for ($i = 0; $i < $jumlah_kolom; $i++) {
                $nilai = isset($row[$i]) ? $row[$i] : '';
                $xml .= $this->_cell($this->_col($i + 1) . $baris, $nilai, 0);
            }
            $xml .= '</row>';
            $baris++;
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    private function _cell($ref, $value, $style = 0) {
        $s = $style ? ' s="' . (int) $style . '"' : '';

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $ref . '"' . $s . '><v>' . $value . '</v></c>';
        }

        return '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">'
            . $this->_xml($value) . '</t></is></c>';
    }

    private function _sheet_name($nama) {
        $nama = preg_replace('/[\\\\\\/?*\\[\\]:]/', ' ', (string) $nama);
        $nama = trim($nama);
        if ($nama === '') {
            $nama = 'Sheet1';
        }
        if (mb_strlen($nama, 'UTF-8') > 31) {
            $nama = mb_substr($nama, 0, 31, 'UTF-8');
        }
        return $nama;
    }

    // Konversi nomor kolom (1 = A, 27 = AA)
    private function _col($n) {
        $n = (int) $n;
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m) . $s;
            $n = (int) (($n - $m) / 26);
        }
        return $s === '' ? 'A' : $s;
    }

    private function _xml($value) {
        $value = (string) $value;

        // Buang karakter kontrol yang tidak diizinkan XML 1.0
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);

        // Pastikan UTF-8 valid; data lama bisa saja bukan UTF-8
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
