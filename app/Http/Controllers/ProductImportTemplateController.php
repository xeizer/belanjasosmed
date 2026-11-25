<?php

namespace App\Http\Controllers;

use App\Exports\ProductImportTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportTemplateController extends Controller
{
    /**
     * Download the product import template.
     */
    public function __invoke(): BinaryFileResponse
    {
        return Excel::download(new ProductImportTemplateExport, 'template-import-produk.xlsx');
    }
}
