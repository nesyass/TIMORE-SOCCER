<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminStudentAccount extends Model
{
    protected $table = 'student_accounts';

    protected $fillable = ['student_id', 'username', 'password', 'must_change_password'];
    protected $hidden = ['password'];
    protected $casts = ['password' => 'hashed', 'must_change_password' => 'boolean'];

    public static function issueFor(AdminRecord $student): array
    {
        $password = Str::password(18, symbols: false);
        $account = self::updateOrCreate(
            ['student_id' => $student->id],
            ['username' => sprintf('tss-%06d', $student->id), 'password' => $password, 'must_change_password' => true],
        );

        return ['name' => $student->title, 'username' => $account->username, 'password' => $password];
    }
}