<?php

namespace App\Http\Controllers\Admin;

use App\Lesson;
use App\Student;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StudentsControler extends Controller
{
    //@return view
    public function show(){

        if(session()->exists('not_found')) {
            return view('admin.students',[
                'not_found' => 1,
            ]);
        }

            if(session()->exists('searched_students'))
                $students = session()->get('searched_students');
            else $students = Student::all();

        return view('admin.students',[
            'all_students' => $this->getStudents($students),
        ]);
    }

    //@return arr
    public function getStudents($students){
        //Сюда складываем всех студентов(масив масивов)
        $array_of_all_students = [];
        foreach ($students as $student){
            $current_student_info = [];
            $current_student_info['name'] = $student->name;
            $current_student_info['surname'] = $student->surname;
            $current_student_info['e-mail'] = $student['e-mail'];
            $current_student_info['id'] = $student['id'];
            $student_active_courses = $student->activeCourses;
            $current_student_info['course_name'] = $this->getCurrentCourse($student);

            $all_amounts_of_lessons = $this->getAmountOfLessons($student_active_courses);

            $current_student_info['amount_of_native'] = $all_amounts_of_lessons['amount_of_native'];
            $current_student_info['amount_of_russian'] = $all_amounts_of_lessons['amount_of_russian'];

            array_push($array_of_all_students,$current_student_info);
        }
         return $array_of_all_students;

        }

    //Тут формируем инфу о кол-ве уроков
    //@param collection $active_course
    //@return arr
    public function getAmountOfLessons($active_courses){

        $all_amounts = [];

        $all_amounts['amount_of_native'] = 0;
        $all_amounts['amount_of_russian'] = 0;

        foreach ($active_courses as $active_course) {

            //Является ли носителем
            $is_native = $active_course->course->is_native;

            //Оставшееся кол-во уроков
            $amount_remain = $active_course->amount_remain;

            $all_amounts['amount_of_native'] += $is_native ? $amount_remain : 0;
            $all_amounts['amount_of_russian'] += $is_native ? 0 : $amount_remain;
        }

        return $all_amounts;
    }

    //Возвращает имя текущего курса
    //@param collection $student
    //@return str
    public function getCurrentCourse($student){

        if(isset($this->getNextLessons($student)[0]))
            return $this->getNextLessons($student)[0]->activeCourse->course->name;
        else return 'Нет урока';

    }

    //@param collection $student
    //@return arr
    public function getNextLessons($student){

        //Сюда складываем модели уроков по порядку
        $next_lessons = [];

        $activeCourses = $student->activeCourses;
        $activeCoursesIds = [];

        foreach ($activeCourses as $activeCourse){
            array_push($activeCoursesIds,$activeCourse->id);
        }

        $lessons = Lesson::whereIn('active_course_id',$activeCoursesIds)->
        orderBy('date')->
        orderBy('time')->
        get();

        foreach ($lessons as $lesson){
            if(strtotime($lesson->date.' '.$lesson->time) < time()) continue;
            array_push($next_lessons,$lesson);
        }

        return $next_lessons;

    }

    public function addStudent(Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'surname' => 'required|max:255',
            'email' => 'required|email',
            'password' => 'required|max:20'
        ]);

        if ($validator->fails()){

            return back()->withErrors($validator)
                ->withInput();

        }

        //Валидация успешная
        $student_name = $request->input('name');
        $student_surname = $request->input('surname');
        $student_email = $request->input('email');
        $student_password = Hash::make($request->input('password'));

        $user = new User;
        $user->password = $student_password;
        $user->remember_token = Str::random(20);
        $user->role_id = 2;
        $user->save();

        $student = new Student;
        $student->name = $student_name;
        $student->surname = $student_surname;
        $student['e-mail'] = $student_email;
        $student->user_id = $user['id'];
        $student->save();

        return back();


    }

    public function searchStudents(Request $request){

        if(!$request->has('search') || !$request->filled('search')) return back();

        $search_str = $request->input('search');

        $students_by_column = Student::where('name','like','%'.$search_str.'%')
            ->orWhere('surname','like','%'.$search_str.'%')
            ->orWhere('e-mail','like','%'.$search_str.'%')
            ->get();

        if($students_by_column->isNotEmpty()){
            return back()->with('searched_students',$students_by_column)->withInput();
        }
        //Не нашёл студента по полной строке
        else{

            //Ищем по названию курса
            $all_students = Student::all();
            $students_to_show = [];
            foreach ($all_students as $student){

                $current_course_name = $this->getCurrentCourse($student);
                if($current_course_name === $search_str)
                    array_push($students_to_show,$student);

            }
            if(!empty($students_to_show)) return back()->with('searched_students',$students_to_show)->withInput();

            //Тут понимаем ,что по целой
            // строке ничего не найти

            //Делим на строки
            $exploded_str = explode(' ',$search_str);
            //Проверяем удалось ли разделить
            if(isset($exploded_str) && (count($exploded_str) < 2)){
                //Строку нельзя разделить
                return back()->with('not_found',1)->withInput();
            }
            else{
                //Разделили строку

                if(isset($exploded_str)) {
                    //Ищем по имени и фамилии одновременно

                    //Порядок имя,фамилия
                    $students = Student::where('name', 'like', $exploded_str[0])
                        ->where('surname', 'like', $exploded_str[1])
                        ->get();
                    if ($students->isNotEmpty())
                        return back()->with('searched_students', $students)->withInput();

                    //Порядок фамилия,имя
                    $students = Student::where('name', 'like', $exploded_str[1])
                        ->where('surname', 'like', $exploded_str[0])
                        ->get();
                    if ($students->isNotEmpty())
                        return back()->with('searched_students', $students)->withInput();


                }

                //Не нашли таким образом
                    return back()->with('not_found', 1)->withInput();

            }

        }

    }

    public function showStudentInfo(Request $request){

        $user_id = $request->id;
        $student = Student::where('id',$user_id)->first();

        $next_lessons = $this->getNextLessons($student);

        if(isset($next_lessons)) {
            if(isset($next_lessons[0]->date)) {
                $this->first_lesson_date = $this->setDateFormat($next_lessons[0]->date);
                $this->first_lesson_time = $this->setTimeFormat($next_lessons[0]->time);
            }
            else{
                $this->first_lesson_date = 'Нет';
                $this->first_lesson_time = 'Уроков';
            }
            if(isset($next_lessons[1]->date)){
                $this->second_lesson_date = $this->setDateFormat($next_lessons[1]->date);
                $this->second_lesson_time = $this->setTimeFormat($next_lessons[1]->time);
            }
            else {
                $this->second_lesson_date = 'Нет';
                $this->second_lesson_time = 'Уроков';
            }

            $active_courses = $student->activeCourses;
            $amounts_of_lessons = $this->getAmountOfLessons($active_courses);
            return view('admin.studentInfo',[
                'student' => $student,
                'date_of_birth' => $this->changeDateDelimiterAndOrder($student->birthday,'-','.'),
                'second_lesson_time' => $this->second_lesson_time,
                'second_lesson_date' => $this->second_lesson_date,
                'first_lesson_time' => $this->first_lesson_time,
                'first_lesson_date' => $this->first_lesson_date,
                'amount_of_native' => $amounts_of_lessons['amount_of_native'],
                'amount_of_russian'  => $amounts_of_lessons['amount_of_russian'],
                'course_name' => $this->getCurrentCourse($student),
                'teacher_name' => $this->getNextTeacherName($student),
            ]);
        }
    else{
            return view('student.profile',[
                'second_lesson_time' => 'Нет',
                'second_lesson_date' => 'Уроков',
                'first_lesson_time' => 'Нет',
                'first_lesson_date' => 'Уроков',
            ]);
        }



    }

        //Изменяем формат вывода даты
        public function setDateFormat($date){

            $weak_day_number =  date('N',strtotime($date));
            switch ($weak_day_number){
                case 1:
                    $weak_day = 'ПН';
                    break;
                case 2:
                    $weak_day = 'ВТ';
                    break;
                case 3:
                    $weak_day = 'СР';;
                    break;
                case 4:
                    $weak_day = 'ЧТ';;
                    break;
                case 5:
                    $weak_day = 'ПТ';;
                    break;
                case 6:
                    $weak_day = 'СБ';;
                    break;
                case 7:
                    $weak_day = 'ВС';;
                    break;
                default:
                    $weak_day = "что-то не так с днём недели";
            }

            return $weak_day;


        }

        //Изменяем формат вывода времени
        public function setTimeFormat($time){

            $new_time = '';
            $splited_time = explode(':',$time);
            $splited_time[2] = '-';
            foreach ($splited_time as $time_part){
                if(!isset($was_used)) $new_time.=$time_part.':';
                else $new_time.=$time_part.' ';
                $was_used = true;
            }

            return $new_time;

        }

    //Возвращает имя учителя для ближайшего урока
    public function getNextTeacherName($student){

        if(isset($this->getNextLessons($student)[0]))
            return $this->getNextLessons($student)[0]->teacher->name;
        else return 'Нет учителя';

    }

    public function changeDateDelimiterAndOrder($date,$curDel,$newDel){

        if(!$date) return 'Нет данных';

        $date_values = array_reverse(explode($curDel,$date));

        $new_date = $date_values[0].$newDel;
        $new_date.=$date_values[1].$newDel;
        $new_date.=$date_values[2];

        return $new_date;
    }

    public function changeStudentInfo(Request $request){

        $student_id = $request->id;
        $all_request_data = $request->all();
        $accepted_request_data = [
            'birthday',"e-mail",
            "telephone","skype","country",
        ];

        $student = Student::where('id',$student_id)->first();

        foreach ($all_request_data as $request_datum => $request_datum_value){
            if(!in_array($request_datum,$accepted_request_data)) continue;
            if(!$request->filled($request_datum)) continue;
            if($request_datum == 'birthday')
                $request_datum_value = $this->changeDateDelimiterAndOrder($request_datum_value,'.','-');

            $student[$request_datum] = $request_datum_value;

        }

        $student->save();
        return back();

    }


}
