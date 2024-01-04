<?php

namespace App\Services;

use Vtiful\Kernel\Excel;

class ExportService
{

    public function excel($headers, $rows, $name)
    {
        $config = [
            'path' => sys_get_temp_dir()
        ];

        $excel = new Excel($config);

        $fileName = $name . '.xlsx';

        // 此处会自动创建一个工作表
        $fileObject = $excel->fileName($fileName);

        $filePath = $fileObject->header($headers)
            ->data($rows)->output();

        // Set Header
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        ob_clean();
        flush();

        if (copy($filePath, 'php://output') === false) {
            // Throw exception
        }

        // Delete temporary file
        @unlink($filePath);
    }
}
