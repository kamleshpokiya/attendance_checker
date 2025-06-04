<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        return view('attendance');
    }

    public function check_attendance(Request $request)
    {
        $request->validate([
            'files' => 'required|array|size:2',
            'files.*' => 'file|mimes:xlsx,xls,csv',
        ]);

        $employeeData = null;
        $attendanceData = null;

        // Identify files by headers
        foreach ($request->file('files') as $file) {
            $collection = Excel::toArray(null, $file)[0];
            $headers = array_map('strtolower', $collection[0]);
            if (in_array('paid_hours', $headers) && in_array('employee_id', $headers) && in_array('name', $headers)) {
                $employeeData = $collection;
            } elseif (in_array('employee_id', $headers) && in_array('date', $headers) && in_array('check_in', $headers) && in_array('check_out', $headers)) {
                $attendanceData = $collection;
            }
        }

        if (!$employeeData || !$attendanceData) {
            return response()->json(['error' => 'Could not identify both files. Please ensure the first file has headers: employee_id, name, paid_hours, and the second file has: employee_id, date, check_in, check_out'], 422);
        }

        // Prepare employee paid hours
        $employees = [];
        $employeeHeaders = $employeeData[0];
        for ($i = 1; $i < count($employeeData); $i++) {
            $row = array_combine($employeeHeaders, $employeeData[$i]);
            $employees[$row['employee_id']] = [
                'name' => $row['name'],
                'paid_hours' => floatval($row['paid_hours']),
                'worked_hours' => 0,
            ];
        }

        // Calculate worked hours from attendance
        $attendanceHeaders = $attendanceData[0];
        for ($i = 1; $i < count($attendanceData); $i++) {
            $row = array_combine($attendanceHeaders, $attendanceData[$i]);
            $id = $row['employee_id'];
            if (!isset($employees[$id])) continue;
            try {
                $checkIn = Carbon::parse($row['check_in']);
                $checkOut = Carbon::parse($row['check_out']);
                if ($checkOut->greaterThan($checkIn)) {
                    $hours = $checkIn->floatDiffInHours($checkOut);
                    $employees[$id]['worked_hours'] += $hours;
                }
            } catch (\Exception $e) {
                // Skip rows with invalid date/time
                continue;
            }
        }

        // Find mismatches with reasons
        $mismatches = [];
        foreach ($employees as $id => $data) {
            $reason = null;
            if ($data['worked_hours'] == 0) {
                $reason = 'missing attendance';
            } elseif ($data['worked_hours'] < $data['paid_hours']) {
                $reason = 'underworked';
            } elseif ($data['worked_hours'] > $data['paid_hours']) {
                $reason = 'exceeds max';
            } else {
                $reason = 'matched';
            }
            if ($reason) {
                $mismatches[] = [
                    'employee_id' => $id,
                    'name' => $data['name'],
                    'paid_hours' => $data['paid_hours'],
                    'worked_hours' => round($data['worked_hours'], 2),
                    'discrepancy_reason' => $reason,
                ];
            }
        }

        return response()->json(['mismatches' => $mismatches]);
    }
}
