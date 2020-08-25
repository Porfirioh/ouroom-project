<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\StudentClass\StudentClass;
use App\Model\User\User;
use App\Model\User\UserLoginHistory;
use Carbon\Carbon;
use Auth;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $id_user = Auth::id();
        if ($this->getUserPermission('index home')) {
            if ($this->getUserLogin()->account_type == User::ACCOUNT_TYPE_TEACHER) {
                $siswa = StudentClass::where('teacher_id', $this->getUserLogin()->id)
                    ->with('hasUser')
                    ->count();
                $class = StudentClass::where('teacher_id', $this->getUserLogin()->id)->count();
                $teacher = User::where('account_type', User::ACCOUNT_TYPE_TEACHER)->count();
            } else {
                $siswa = User::where('account_type', User::ACCOUNT_TYPE_SISWA)->count();
                $class = StudentClass::count();
                $teacher = User::where('account_type', User::ACCOUNT_TYPE_TEACHER)->count();
            }
            $last_login = UserLoginHistory::findLastlogin();
            if ($last_login != null) {
                $last_login = Carbon::parse($last_login->date);
                $last_login = $last_login->format('d M Y');
            }
            $this->systemLog(false, 'Mengakses Halaman Home');
            return view('home.index', ['last_login' => $last_login, 'active' => 'home', 'id_user' => $id_user, 'siswa' => $siswa, 'class' => $class, 'teacher' => $teacher]);
        } else {
            $this->systemLog(true, 'Gagal Mengakses Halaman Home');
            return view('error.unauthorized', ['active' => 'home']);
        }
    }
}
