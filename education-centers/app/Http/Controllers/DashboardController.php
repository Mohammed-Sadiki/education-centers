<?php
namespace App\Http\Controllers;
use App\Models\AppNotification;
use App\Models\Attendance;
use App\Models\Center;
use App\Models\Certificate;
use App\Models\ClassRoom;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Formation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
class DashboardController extends Controller {
    public function index(Request $request): View {
        $user = $request->user();
        if (view()->exists('dashboard.' . $user->role)) {
            if ($user->isSuperAdmin()) return $this->superAdminDashboard($request);
            if ($user->isCenterAdmin()) return $this->centerAdminDashboard($user);
            if ($user->isTeacher()) return $this->teacherDashboard($user);
            if ($user->isStudent()) return $this->studentDashboard($user);
        }
        return view('dashboard', compact('user'));
    }
    private function superAdminDashboard(Request $request): View {
        $stats = [
            'centers'     => Center::count(),
            'users'       => User::count(),
            'students'    => Student::count(),
            'teachers'    => Teacher::count(),
            'formations'  => Formation::count(),
            'classes'     => ClassRoom::count(),
            'enrollments' => Enrollment::count(),
            'rooms'       => Room::count(),
            'schedules'   => Schedule::count(),
            'payments_total' => Payment::where('status','paid')->sum('amount'),
            'pending_enrollments' => Enrollment::where('status','pending')->count(),
        ];
        $centers = Center::withCount(['students','teachers','formations','classes','enrollments'])->get();
        $recentEnrollments = Enrollment::with(['student','formation','center'])->latest()->take(10)->get();
        return view('dashboard.super_admin', compact('stats','centers','recentEnrollments'));
    }
    private function centerAdminDashboard(User $user): View {
        $cid = $user->center_id;
        $stats = [
            'students'             => Student::where('center_id',$cid)->count(),
            'teachers'             => Teacher::where('center_id',$cid)->count(),
            'formations'           => Formation::where('center_id',$cid)->count(),
            'classes'              => ClassRoom::where('center_id',$cid)->count(),
            'rooms'                => Room::where('center_id',$cid)->count(),
            'enrollments_pending'  => Enrollment::where('center_id',$cid)->where('status','pending')->count(),
            'enrollments_accepted' => Enrollment::where('center_id',$cid)->where('status','accepted')->count(),
            'payments_total'       => Payment::where('center_id',$cid)->where('status','paid')->sum('amount'),
            'invoices_pending'     => Invoice::where('center_id',$cid)->whereIn('status',['sent','overdue'])->sum('total_amount'),
            'documents'            => Document::where('center_id',$cid)->where('status','active')->count(),
            'certificates'         => Certificate::where('center_id',$cid)->where('status','issued')->count(),
        ];
        $upcoming = Schedule::where('center_id',$cid)->where('date','>=',now()->toDateString())->where('status','scheduled')->with(['classRoom','teacher.user','room'])->orderBy('date')->orderBy('start_time')->take(5)->get();
        $recentEnrollments = Enrollment::where('center_id',$cid)->with(['student','formation'])->latest()->take(8)->get();
        $notifications = AppNotification::where('user_id',$user->id)->whereNull('read_at')->latest()->take(5)->get();
        return view('dashboard.center_admin', compact('stats','upcoming','recentEnrollments','notifications'));
    }
    private function teacherDashboard(User $user): View {
        $teacher = $user->teacher;
        $cid = $user->center_id;
        $classes = $teacher ? $teacher->classes()->with('formation')->get() : collect();
        $upcoming = Schedule::where('center_id',$cid)->where('teacher_id', $teacher?->id)->where('date','>=',now()->toDateString())->where('status','scheduled')->with(['classRoom','room'])->orderBy('date')->orderBy('start_time')->take(10)->get();
        $recentSchedules = Schedule::where('center_id',$cid)->where('teacher_id', $teacher?->id)->where('date','<',now()->toDateString())->where('status','completed')->orderBy('date','desc')->take(5)->get();
        $notifications = AppNotification::where('user_id',$user->id)->whereNull('read_at')->latest()->take(5)->get();
        return view('dashboard.teacher', compact('classes','upcoming','recentSchedules','teacher','notifications'));
    }
    private function studentDashboard(User $user): View {
        $student = $user->student;
        $cid = $user->center_id;
        $enrollments = $student ? Enrollment::where('student_id',$student->id)->with(['formation','classRoom'])->get() : collect();
        $attendances = $student ? Attendance::where('student_id',$student->id)->where('center_id',$cid)->whereMonth('date',now()->month)->get() : collect();
        $presentCount = $attendances->where('status','present')->count();
        $absentCount  = $attendances->where('status','absent')->count();
        $lateCount    = $attendances->where('status','late')->count();
        $grades = $student ? \App\Models\Grade::where('student_id',$student->id)->where('center_id',$cid)->with('evaluation')->get() : collect();
        $notifications = AppNotification::where('user_id',$user->id)->whereNull('read_at')->latest()->take(5)->get();
        $payments = $student ? Payment::where('student_id',$student->id)->where('status','paid')->latest('payment_date')->take(5)->get() : collect();
        $certificates = $student ? Certificate::where('student_id',$student->id)->where('status','issued')->latest('issued_date')->take(5)->get() : collect();
        return view('dashboard.student', compact('student','enrollments','presentCount','absentCount','lateCount','grades','notifications','payments','certificates'));
    }
}