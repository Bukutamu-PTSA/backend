<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplaintCategory;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => ComplaintCategory::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_code' => 'required|string|unique:complaint_categories,category_code',
            'category_name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $category = ComplaintCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil disimpan',
            'data' => $category,
        ], 201);
    }

    public function pdf()
    {
        $field = fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

        $rows = '';
        foreach (ComplaintCategory::orderBy('id')->get() as $category) {
            $rows .= '<tr>
            <td class="num">'.$category->id.'</td>
            <td>'.$field($category->category_code).'</td>
            <td>'.$field($category->category_name).'</td>
        </tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="3" class="empty">Belum ada kategori terdaftar.</td></tr>';
        }

        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>
    @page { margin: 18mm 20mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1f2d3d; line-height: 1.45; }
    h1 { font-size: 15pt; margin: 0 0 3px; text-align: center; color: #2b3a55; }
    .meta { text-align: center; font-size: 9pt; color: #6b7c8c; }
    .rule { border-bottom: 2px solid #2b3a55; margin: 10px 0 16px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #dde3e9; padding: 6px 8px; font-size: 9.5pt; vertical-align: top; }
    th { background-color: #f4f7f9; font-size: 9pt; text-align: left; color: #2b3a55; }
    td.num, th.num { width: 10%; text-align: center; }
    th.code { width: 24%; }
    td.empty { text-align: center; color: #8a9aa8; font-style: italic; }
</style></head><body>

    <h1>Daftar Kategori Pengaduan</h1>
    <div class="meta">Dicetak: '.now()->format('d M Y H:i').'</div>
    <div class="rule"></div>

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="code">Kode</th>
                <th>Nama Kategori</th>
            </tr>
        </thead>
        <tbody>
            '.$rows.'
        </tbody>
    </table>

</body></html>';

        $dompdf = new Dompdf((new Options)->setIsRemoteEnabled(false));
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'daftar-kategori-'.now()->format('Ymd_His').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"; filename*=UTF-8\'\''.$filename,
        ]);
    }
}
