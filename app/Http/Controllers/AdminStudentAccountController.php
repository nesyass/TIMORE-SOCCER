<?php

namespace App\Http\Controllers;

use App\Models\AdminRecord;
use App\Models\AdminStudentAccount;
use Illuminate\Support\Facades\DB;

class AdminStudentAccountController extends \App\Http\Controllers\Controller
{
    public function reset(int $id)
    {
        $access = DB::transaction(function () use ($id) {
            $student = AdminRecord::where('section', 'students')->lockForUpdate()->findOrFail($id);
            $existing = $student->account()->exists();
            $verifiedRegistration = data_get($student->payload, 'registration_id');
            $verifiedPayment = $verifiedRegistration
                && AdminRecord::where('section', 'registrations')->whereKey($verifiedRegistration)->where('status', 'active')->exists()
                && AdminRecord::where('cash_payment_source', 'REG-'.$verifiedRegistration)
                    ->where('status', 'income')->where('amount', '>', 0)->exists();
            abort_unless($student->status === 'active' && ($existing || $verifiedPayment), 409);
            return AdminStudentAccount::issueFor($student);
        });

        return back()->with('success', 'Akses siswa siap disalin.')->with('student_access', $access);
    }
}
