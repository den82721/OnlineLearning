<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use \Illuminate\Http\Request;


Route::group(['middleware' => 'auth','prefix' => '/student'],function (){

    Route::get('/', 'StudentController@showStudentInfo')->name('home');

    Route::get('/teacher', 'StudentTeacherController@showTeacherInfo')->name('teacher');

    Route::get('/lessons', 'LessonsController@showLessonsInfo');

    Route::get('/payments','PaymentController@showStudentPayments');


    Route::get('/buylessons', 'BuyLessonsController@showBuyLessons');

    Route::get('/freelessons','FreeLessonsController@showFreeLessons');

    Route::get('/writetoadmin', 'WriteToAdminController@show');


    Route::get('/changepassword','ChangePasswordController@show')->name('changePassword');


    Route::post('/changepassword','StudentController@changePassword')->name('toChangePassword');

});

Route::group(['middleware' => 'auth','prefix' => 'admin'],function (){

    Route::get('/','Admin\StudentsControler@show');

    Route::post('/','Admin\StudentsControler@addStudent')->name('students');

    Route::get('/student/{id}','Admin\StudentsControler@showStudentInfo')->name('adminStudentInfo');
    Route::post('/student/{id}','Admin\StudentsControler@changeStudentInfo');

    Route::get('/teachers','Admin\TeachersController@show')->name('teachers');
    Route::get('/teacher/{id}','Admin\TeachersController@showTeacherInfo')->name('adminTeacherInfo');
    Route::post('/teacher/{id}','Admin\TeachersController@changeTeacherInfo');

    Route::post('/teachers','Admin\TeachersController@addTeacher');

    Route::get('/lessons','Admin\LessonsController@showLessons')->name('adminLessons');
    Route::post('/lessons','Admin\LessonsController@changeStatus');

    Route::get('/payments','Admin\PaymentsController@show');



    Route::get('/paymentsout','Admin\PaymentsOutController@show');
    Route::post('/paymentsout','Admin\PaymentsOutController@addPayout')->name('addPayout');
    Route::post('/paymentsout/changeStatus','Admin\PaymentsOutController@changeStatus')
        ->name('changePayoutsStatus');
    Route::post('/paymentsout/setOrder','Admin\PaymentsOutController@setOrder')->name('setOrder');

    Route::group(['prefix' => 'search'],function (){

        Route::post('/teacher','Admin\TeachersController@searchTeachers')->name('searchTeachers');
        Route::post('/student','Admin\StudentsControler@searchStudents')->name('searchStudents');

    });



});

Route::group(['prefix' => 'teacher'],function (){

    Route::get('/',function (){
        return view('teacher.teacher');
    });

    Route::get('/students',function (){
        return view('teacher.students');
    });

    Route::get('/lessons',function (){
        return view('teacher.lessons');
    });

    Route::get('/paymentsout',function (){
        return view('teacher.paymentsout');
    });

    Route::get('/calendar',function (){
        return view('teacher.calendar');
    });

});


Route::get('/',function (){

    $role_name = mb_strtolower(Auth::user()->role->role_name);
        return redirect('/'.$role_name);

})->middleware('auth');

$this->get('/login', 'Auth\LoginController@showLoginForm')->name('login');
$this->post('/login', 'Auth\LoginController@login');
$this->post('student/logout', 'Auth\LoginController@logout')->name('logout');



