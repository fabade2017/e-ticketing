<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Spatie\Permission\Models\Role;

class BulkUploadController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        return Inertia::render('Admin/BulkUpload', [
            'roles' => Role::pluck('name'),
            'departments' => Department::pluck('name','id'),
            'templateHeaders' => ['name','email','service_number','rank','unit','department','phone','role'],
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $rows = [];
        $header = [];
        if (in_array($ext, ['xlsx','xls'])) {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();
            $header = array_map(fn($h) => strtolower(trim($h)), $data[0] ?? []);
            for ($i=1; $i<count($data); $i++) $rows[] = $data[$i];
        } else {
            $path = $request->file('file')->getRealPath();
            $handle = fopen($path, 'r');
            $header = array_map(fn($h) => strtolower(trim($h)), fgetcsv($handle) ?? []);
            while (($row = fgetcsv($handle)) !== false) $rows[] = $row;
            if (isset($handle)) fclose($handle);
        }
        $expected = ['name','email','service_number','rank','unit','department','phone','role'];

        $results = ['created' => 0, 'skipped' => 0, 'errors' => []];

        $line = 1;
        foreach ($rows as $row) {
            $line++;
            if (count(array_filter($row, fn($v)=>$v!==null && $v!=='')) < 3) { $results['skipped']++; $results['errors'][] = "Line $line: too few columns"; continue; }
            $data = array_combine($header, array_pad($row, count($header), null));
            $data = array_map(fn($v)=> is_string($v)?trim($v):$v, $data);

            if (empty($data['name']) || empty($data['email']) || empty($data['service_number'])) {
                $results['skipped']++; $results['errors'][] = "Line $line: name/email/service_number required"; continue;
            }
            if (User::where('email', $data['email'])->orWhere('service_number', $data['service_number'])->exists()) {
                $results['skipped']++; $results['errors'][] = "Line $line: email or service_number exists ({$data['email']})"; continue;
            }
            $roleName = $data['role'] ?: 'Soldier';
            if (!Role::where('name', $roleName)->exists()) {
                $results['skipped']++; $results['errors'][] = "Line $line: role $roleName not found"; continue;
            }

            $deptId = null;
            if (!empty($data['department'])) {
                $dept = Department::where('name',$data['department'])->orWhere('code',$data['department'])->first();
                if (!$dept) { $results['skipped']++; $results['errors'][] = "Line $line: department {$data['department']} not found"; continue; }
                $deptId = $dept->id;
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'service_number' => $data['service_number'],
                'rank' => $data['rank'] ?? null,
                'unit' => $data['unit'] ?? null,
                'department_id' => $deptId,
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make('Password@123'),
                'is_active' => true,
            ]);
            $user->assignRole($roleName);
            $results['created']++;
        }

        // store import report for download
        $report = implode("\n", array_merge(["Bulk import " . now() . " — created {$results['created']}, skipped {$results['skipped']}"], $results['errors']));
        $filename = 'bulk_import_' . now()->format('Ymd_His') . '.txt';
        \Illuminate\Support\Facades\Storage::disk('local')->put("exports/$filename", $report);

        return back()->with('success', "Bulk import done: {$results['created']} created, {$results['skipped']} skipped. Report: storage/exports/$filename — See /docs for spec.")->with('bulkResult', $results);
    }

    public function template(Request $request)
    {
        if (!auth()->user()->hasRole('SuperAdmin')) abort(403);
        $headers = ['name','email','service_number','rank','unit','department','phone','role'];
        $rows = [
            ['Soldier Two','soldier2@army.mil.ng','NA/SL/004','Pte','Outpost B','Logistics','08012345678','Soldier'],
            ['Support One','support@army.mil.ng','NA/SP/005','Sgt','HQ','Medical','08087654321','Support'],
        ];
        if ($request->query('format') === 'xlsx') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($headers, null, 'A1');
            $sheet->fromArray($rows, null, 'A2');
            // style header
            $sheet->getStyle('A1:H1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A1:H1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('0B3D0B');
            foreach (range('A','H') as $col) $sheet->getColumnDimension($col)->setWidth(18);
            // roles dropdown
            $roles = Role::pluck('name')->toArray();
            $rolesList = '"' . implode(',', $roles) . '"';
            for ($r=2; $r<=101; $r++) {
                $validation = $sheet->getCell("H{$r}")->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST)->setErrorStyle(DataValidation::STYLE_STOP)->setAllowBlank(false)->setShowDropDown(true)->setFormula1($rolesList);
            }
            // departments dropdown
            $depts = Department::pluck('name')->toArray();
            $deptList = '"' . implode(',', $depts) . '"';
            for ($r=2; $r<=101; $r++) {
                $validation = $sheet->getCell("F{$r}")->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST)->setErrorStyle(DataValidation::STYLE_INFORMATION)->setAllowBlank(true)->setShowDropDown(true)->setFormula1($deptList);
            }
            $writer = new Xlsx($spreadsheet);
            $tmp = tempnam(sys_get_temp_dir(), 'bulk');
            $writer->save($tmp);
            return response()->download($tmp, 'bulk_users_template.xlsx', ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        }
        $csv = implode(',', $headers) . "\n" . implode("\n", array_map(fn($r) => implode(',', $r), $rows));
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bulk_users_template.csv"',
        ]);
    }
}
