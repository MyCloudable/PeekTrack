<?php

use App\Http\Controllers\FileUpload;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagController;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\RolesController;
use App\Mail\JobCardRejectionNotification;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\Clock\CrewsController;
use App\Http\Controllers\Clock\DepartController;
use App\Http\Controllers\FullCalenderController;
use App\Http\Controllers\Clock\CrewTypeController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\Clock\TimesheetController;
use App\Http\Controllers\Clock\TimesheetManagementConroller;

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

Route::get('/', function () {
    return redirect('sign-in');
})->middleware('guest');

Route::get('/sendmail', function() {
    


// The email sending is done using the to method on the Mail facade

Mail::to('qtconsultants@gmail.com')->send(new JobCardRejectionNotification());
});

Route::get('/autocomplete', [SearchController::class, 'autocomplete'])->name('autocomplete');



Route::post('/jobs/shareJobcard', [JobsController::class, 'shareJobcard'])->name('jobs.shareJobcard');

Route::get('sign-up', [RegisterController::class, 'create'])->middleware('guest')->name('register');
Route::post('sign-up', [RegisterController::class, 'store'])->middleware('guest');

Route::get('sign-in', [SessionsController::class, 'create'])->middleware('guest')->name('login');
Route::post('sign-in', [SessionsController::class, 'store'])->middleware('guest');

Route::post('sign-out', [SessionsController::class, 'destroy'])->middleware('auth')->name('logout');

Route::post('verify', [SessionsController::class, 'show'])->middleware('guest');
Route::post('reset-password', [SessionsController::class, 'update'])->middleware('guest')->name('password.update');

Route::get('fullcalender', [FullCalenderController::class, 'index']);
Route::post('fullcalenderAjax', [FullCalenderController::class, 'ajax']);

Route::get('verify', function () {
	return view('sessions.password.verify');
})->middleware('guest')->name('verify');

Route::get('reset-password/{token}', function ($token) {
	return view('sessions.password.reset', ['token' => $token]);
})->middleware('guest')->name('password.reset');

Route::post('/set-session-variable', function () {
    Session::start();

	if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the selected option value from the POST data
    $selectedCrew = $_POST["selectedOption"];

    // Set the session variable with the selected option value
    $_SESSION["selectedCrew"] = $selectedCrew;

    // Send a response back to the client (if needed)
    echo "Crew set to: " . $_SESSION["selectedCrew"];
}

});

Route::get('dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::get('send', 'NotifyController@index');

Route::get('jobs', [JobsController::class, 'index'])->middleware('auth')->name('jobs');

Route::get('jobs/review', [JobsController::class, 'review'])->middleware('auth')->name('jobs.review');

Route::get('jobs/globalreview', [JobsController::class, 'globalreview'])->middleware('auth')->name('jobs.globalreview');

Route::get('jobs/history', [JobsController::class, 'history'])->middleware('auth')->name('jobs.history');

Route::get('schedule', [ScheduleController::class, 'index'])->middleware('auth')->name('schedule');

Route::get('reports', [ReportsController::class, 'index'])->middleware('auth')->name('reports');

Route::get('/jobs/{id}/edit/{crewType}', [JobsController::class, 'edit'])->middleware('auth')->name('jobs.edit');

Route::post('/jobs/entryupdate', [JobsController::class, 'entryupdate'])->name('jobs.entryupdate');

Route::get('/jobs/{id}/update', [JobsController::class, 'store'])->middleware('auth')->name('jobs.update');

Route::get('/jobs/{id}/view', [JobsController::class, 'view'])->middleware('auth')->name('jobs.view');

Route::get('/jobs/{id}/jobcardview', [JobsController::class, 'jobcardview'])->middleware('auth')->name('jobs.jobcardview');

Route::get('/jobs/{id}/myjobcards', [JobsController::class, 'myjobcards'])->middleware('auth')->name('jobs.myjobcards');

Route::post('jobs/{id}/update', [JobsController::class, 'store'])->name('jobs.store');

Route::post('jobs/updatecard', [JobsController::class, 'updatecard'])->name('jobs.updatecard');

Route::post('jobs/opencard', [JobsController::class, 'opencard'])->name('jobs.opencard');

Route::post('jobs/exportFile', [JobsController::class, 'exportFile'])->name('jobs.exportFile');

Route::post('jobs/roadListReport', [JobsController::class, 'roadList'])->name('jobs.roadListReport');

Route::get('jobs/{link}/removeLineJBRP/{id}/REF/{ref}', [JobsController::class, 'removeLineJBRP'])->name('jobs.removeLineJBRP');

Route::get('jobs/{link}/removeLineJBRM/{id}/REF/{ref}', [JobsController::class, 'removeLineJBRM'])->name('jobs.removeLineJBRM');

Route::get('jobs/{link}/removeLineJBRE/{id}/REF/{ref}', [JobsController::class, 'removeLineJBRE'])->name('jobs.removeLineJBRE');

Route::get('jobs/{link}/removeJC/{id}', [JobsController::class, 'removeJC'])->name('jobs.removeJC');

Route::post('jobs/changeJobNum', [JobsController::class, 'changeJobNum'])->name('jobs.changeJobNum');

Route::post('jobs/rejectcard', [JobsController::class, 'rejectcard'])->name('jobs.rejectcard');

Route::post('jobs/submit', [JobsController::class, 'submitjob'])->name('jobs.submit');

Route::get('/jobs/{id}/overview', [JobsController::class, 'overview'])->middleware('auth')->name('jobs.overview');

Route::get('/jobs/{id}/jobreview', [JobsController::class, 'jobreview'])->middleware('auth')->name('jobs.jobreview');

Route::get('/jobs/{id}/jobcard', [JobsController::class, 'jobcard'])->middleware('auth')->name('jobs.jobcard');

Route::get('/upload-file', [FileUpload::class, 'createForm']);

Route::post('/fileUpload', [FileUpload::class, 'fileUpload'])->name('fileUpload');

Route::post('/uploadpo', [FileUpload::class, 'uploadpo'])->name('uploadpo');

Route::post('/upload-file', [FileUpload::class, 'jobcardUpload'])->name('jobcardUpload');

Route::get('/delete-file/{id}', [FileUpload::class, 'fileDelete'])->name('fileDelete');


Route::get('user-profile', [UserController::class, 'index'])->middleware('auth')->name('user-profile');
Route::post('user-profile', [UserController::class, 'update'])->middleware('auth')->name('user.update');
Route::post('user-profile/password', [UserController::class, 'passwordUpdate'])->middleware('auth')->name('password.change');


Route::get('roles', [RolesController::class, 'index'])->middleware('auth')->name('roles');
Route::post('roles/{id}', [RolesController::class, 'destroy'])->middleware('auth')->name('delete.role');
Route::get('new-role', [RolesController::class, 'create'])->middleware('auth')->name('add.role');
Route::post('new-role', [RolesController::class, 'store'])->middleware('auth');
Route::post('edit-role/{id}', [RolesController::class, 'update'])->middleware('auth');
Route::get('edit-role/{id}', [RolesController::class, 'edit'])->middleware('auth')->name('edit.role');


Route::get('category', [CategoryController::class, 'index'])->middleware('auth')->name('category');
Route::post('category/{id}', [CategoryController::class, 'destroy'])->middleware('auth')->name('delete.category');
Route::get('new-category', [CategoryController::class, 'create'])->middleware('auth')->name('add.category');
Route::post('new-category', [CategoryController::class, 'store'])->middleware('auth');
Route::post('edit-category/{id}', [CategoryController::class, 'update'])->middleware('auth');
Route::get('edit-category/{id}', [CategoryController::class, 'edit'])->middleware('auth')->name('edit.category');


Route::get('tag',[TagController::class, 'index'])->middleware('auth')->name('tag');
Route::post('tag/{id}', [TagController::class, 'destroy'])->middleware('auth')->name('delete.tag');
Route::get('new-tag', [TagController::class, 'create'])->middleware('auth')->name('add.tag');
Route::post('new-tag', [TagController::class, 'store'])->middleware('auth');
Route::post('edit-tag/{id}', [TagController::class, 'update'])->middleware('auth');
Route::get('edit-tag/{id}', [TagController::class, 'edit'])->middleware('auth')->name('edit.tag');

Route::get('items', [ItemsController::class, 'index'])->middleware('auth')->name('items');
Route::get('new-item', [ItemsController::class, 'create'])->middleware('auth')->name('add.item');
Route::post('new-item',[ItemsController::class, 'store'])->middleware('auth');
Route::get('edit-item/{id}',[ItemsController::class, 'edit'])->middleware('auth')->name('edit.item');
Route::post('edit-item/{id}',[ItemsController::class, 'update'])->middleware('auth');
Route::post('items/{id}', [ItemsController::class, 'destroy'])->middleware('auth')->name('delete.item');


Route::get('users-management', [UserManagementController::class, 'index'])->middleware('auth')->name('users');
Route::get('add-new-user', [UserManagementController::class, 'create'])->middleware('auth')->name('add.user');
Route::post('add-new-user', [UserManagementController::class, 'store'])->middleware('auth');
Route::get('edit-user/{id}',[UserManagementController::class, 'edit'])->middleware('auth')->name('edit.user');
Route::post('edit-user/{id}',[UserManagementController::class, 'update'])->middleware('auth');
Route::post('users-management/{id}',[UserManagementController::class, 'destroy'])->middleware('auth')->name('delete.user');

Route::group(['middleware' => 'auth'], function () {
	Route::get('charts', function () {
		return view('pages.charts');
	})->name('charts');

	Route::get('notifications', function () {
		return view('pages.notifications');
	})->name('notifications');

	Route::get('pricing-page', function () {
		return view('pages.pricing-page');
	})->name('pricing-page');

    Route::get('rtl', function () {
		return view('pages.rtl');
	})->name('rtl');

	Route::get('sweet-alerts', function () {
		return view('pages.sweet-alerts');
	})->name('sweet-alerts');

	Route::get('widgets', function () {
		return view('pages.widgets');
	})->name('widgets');

	Route::get('vr-default', function () {
		return view('pages.vr.vr-default');
	})->name('vr-default');

	Route::get('vr-info', function () {
		return view("pages.vr.vr-info");
	})->name('vr-info');

	Route::get('new-user', function () {
		return view('pages.users.new-user');
	})->name('new-user');


    Route::get('general', function () {
		return view('pages.projects.general');
	})->name('general');

	Route::get('new-project', function () {
		return view('pages.projects.new-project');
	})->name('new-project');

	Route::get('timeline', function () {
		return view('pages.projects.timeline');
	})->name('timeline');

	Route::get('overview', function () {
		return view('pages.profile.overview');
	})->name('overview');

	Route::get('projects', function () {
		return view("pages.profile.projects");
	})->name('projects');

	Route::get('billing', function () {
		return view('pages.account.billing');
	})->name('billing');

    Route::get('invoice', function () {
		return view('pages.account.invoice');
	})->name('invoice');

    Route::get('security', function () {
		return view('pages.account.security');
	})->name('security');

	Route::get('settings', function () {
		return view('pages.account.settings');
	})->name('settings');

	Route::get('referral', function () {
		return view('ecommerce.referral');
	})->name('referral');

	Route::get('details', function () {
		return view('ecommerce.orders.details');
	})->name('details');

	Route::get('list', function () {
		return view("ecommerce.orders.list");
	})->name('list');

	Route::get('edit-product', function () {
		return view('ecommerce.products.edit-product');
	})->name('edit-product');

    Route::get('new-product', function () {
		return view('ecommerce.products.new-product');
	})->name('new-product');

    Route::get('product-page', function () {
		return view('ecommerce.products.product-page');
	})->name('product-page');

    Route::get('products-list', function () {
		return view('ecommerce.products.products-list');
	})->name('products-list');

	Route::get('automotive', function () {
		return view('dashboard.automotive');
	})->name('automotive');

	Route::get('discover', function () {
		return view('dashboard.discover');
	})->name('discover');

	Route::get('sales', function () {
		return view('dashboard.sales');
	})->name('sales');

	Route::get('smart-home', function () {
		return view("dashboard.smart-home");
	})->name('smart-home');

	Route::get('404', function () {
		return view('errors.404');
	})->name('404');

    Route::get('500', function () {
		return view('errors.500');
	})->name('500');

    Route::get('basic-lock', function () {
		return view('authentication.lock.basic');
	})->name('basic-lock');

    Route::get('cover-lock', function () {
		return view('authentication.lock.cover');
	})->name('cover-lock');

    Route::get('illustration-lock', function () {
		return view('authentication.lock.illustration');
	})->name('illustration-lock');

    Route::get('basic-reset', function () {
		return view('authentication.reset.basic');
	})->name('basic-reset');

    Route::get('cover-reset', function () {
		return view('authentication.reset.cover');
	})->name('cover-reset');

    Route::get('illustration-reset', function () {
		return view('authentication.reset.illustration');
	})->name('illustration-reset');

    Route::get('basic-sign-in', function () {
		return view('authentication.sign-in.basic');
	})->name('basic-sign-in');

    Route::get('cover-sign-in', function () {
		return view('authentication.sign-in.cover');
	})->name('cover-sign-in');

    Route::get('illustration-sign-in', function () {
		return view('authentication.sign-in.illustration');
	})->name('illustration-sign-in');

    Route::get('basic-sign-up', function () {
		return view('authentication.sign-up.basic');
	})->name('basic-sign-up');

    Route::get('cover-sign-up', function () {
		return view('authentication.sign-up.cover');
	})->name('cover-sign-up');

    Route::get('illustration-sign-up', function () {
		return view('authentication.sign-up.illustration');
	})->name('illustration-sign-up');

    Route::get('basic-verification', function () {
		return view('authentication.verification.basic');
	})->name('basic-verification');

    Route::get('cover-verification', function () {
		return view('authentication.verification.cover');
	})->name('cover-verification');

    Route::get('illustration-verification', function () {
		return view('authentication.verification.illustration');
	})->name('illustration-verification');

    Route::get('calendar', function () {
		return view('applications.calendar');
	})->name('calendar');

    Route::get('crm', function () {
		return view('applications.crm');
	})->name('crm');

    Route::get('datatables', function () {
		return view('applications.datatables');
	})->name('datatables');

    Route::get('kanban', function () {
		return view('applications.kanban');
	})->name('kanban');

    Route::get('stats', function () {
		return view('applications.stats');
	})->name('stats');

    Route::get('wizard', function () {
		return view('applications.wizard');
	})->name('wizard');


	// ----------- clock management -----------

	//crews & crew members
    Route::resource('crews', CrewsController::class);

	// timesheet - verify & clockin
	Route::get('crew-members', [TimesheetController::class, 'getCrewMembers']);
	Route::post('verify-crew-members', [TimesheetController::class, 'verifyCrewMembers']);
	Route::post('clockinout-crew-members', [TimesheetController::class, 'clockinoutCrewMembers']);

	Route::get('getusers-for-crewentry', [TimesheetController::class, 'getAllUsers']);
	Route::post('add-new-crew-members', [TimesheetController::class, 'addNewCrewMember']);
	Route::post('delete-crew-members', [TimesheetController::class, 'deleteCrewMember']);
	Route::post('hf-per-diem', [TimesheetController::class, 'hfPerDiem']);
	Route::post('ready-for-verification', [TimesheetController::class, 'readyForVerification']);
	Route::post('wather-entry', [TimesheetController::class, 'weatherEntry']);

	// depart
	Route::get('getjobs-for-depart', [DepartController::class, 'getAllJobs']);
	Route::post('track-time-travel', [DepartController::class, 'trackTravelTime']);

	//crew types
	Route::resource('crewTypes', CrewTypeController::class);

	// timesheet management
	Route::get('timesheet-management', [TimesheetManagementConroller::class, 'index'])->name('timesheet-management.index');
	Route::get('timesheet-management/getall', [TimesheetManagementConroller::class, 'getAll']);
	Route::post('/timesheet-management/update-checkbox-approval', [TimesheetManagementConroller::class, 'updateCheckboxApproval']);
	Route::post('timesheet-management/update-checkbox-approval-bulk', [TimesheetManagementConroller::class, 'updateCheckboxApprovalBulk']);
	Route::post('/timesheet-management/update-times', [TimesheetManagementConroller::class, 'updateTimes']);
	Route::delete('/timesheets/{id}', [TimesheetManagementConroller::class, 'deleteTimesheet']);
	Route::post('/timesheet-management/store', [TimesheetManagementConroller::class, 'storeTimesheet']);



	// ----------- end clock management -----------

	Route::get('test-vue', function(){
		return view('test-vue');
	});

});


Route::get('test', function () {
	dd('test route on dev server');
});