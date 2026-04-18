<?php
// file: routes/web.php
use Illuminate\Support\Facades\Route;

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
Route::get('/', 'Payments@index')->name('home');
Route::get('/register/{shortcode}','Payments@register');
Route::get('/dashboard','Payments@index')->name('dashboard');
Route::post('/dashboard','Payments@index2');
Route::get('/shortcode','Payments@shortcode')->name('shortcode');
Route::get('/services','Payments@services')->name('services');
Route::post('/signin','Auth\LoginController@login')->name('signin');
Route::get('/transaction','Payments@transaction')->name('transaction');
Route::get('/grouptrans/{shortcode}','Payments@checktrans')->name('transaction.shortcode');
Route::get('/grouptrans/{shortcode}/{service}','Payments@checktrans')->name('transaction.service');
Route::post('/alltrans','Datatables@alltrans');
Route::post('/shortcodetrans','Datatables@get_trans_by_shortcode');
Route::post('/servicetrans','Datatables@get_trans_by_service');
Route::get('/transaction-reports','TransactionReportController@index')->name('transaction-reports.index');
Route::post('/transaction-reports/datatable','TransactionReportController@datatable')->name('transaction-reports.datatable');
Auth::routes();
Route::get('/home', 'Payments@index')->name('home');
Route::post('/saveshortcode','Payments@saveshortcode');
Route::post('/editshortcode','Payments@editshortcode');
Route::post('/notify', 'Payments@startnotification');
Route::post('/addservice','Payments@addservice');
Route::post('/editservice','Payments@editservice');
Route::post('/deleteservice','Payments@destroyservice')->name('services.destroy');
Route::get('/profile', 'ProfileController@show')->name('profile.show');
Route::post('/profile', 'ProfileController@update')->name('profile.update');
Route::get('/documentation', 'MpesaDocumentationController@index')->name('documentation.index');
Route::post('/documentation/sandbox-preview', 'MpesaDocumentationController@preview')->name('documentation.preview');
Route::get('/users','UserManagementController@index')->name('users.index');
Route::post('/get_users','UserManagementController@datatable')->name('users.datatable');
Route::post('/adduser','UserManagementController@store')->name('users.store');
Route::post('/edituser','UserManagementController@update')->name('users.update');
Route::post('/edituser/custom-permissions','UserManagementController@updateCustomPermissions')->name('users.custom-permissions.update');
Route::post('/edituser/resource-access','UserManagementController@updateResourceAccess')->name('users.resource-access.update');
Route::post('/toggleuserstatus','UserManagementController@toggleStatus')->name('users.toggle-status');
Route::post('/deleteuser','UserManagementController@destroy')->name('users.destroy');
Route::get('/roles','UserManagementController@roles')->name('roles.index');
Route::post('/roles/datatable','UserManagementController@rolesDatatable')->name('roles.datatable');
Route::post('/roles','UserManagementController@storeRole')->name('roles.store');
Route::post('/roles/update','UserManagementController@updateRole')->name('roles.update');
Route::get('/keywords','UserManagementController@keywords')->name('keywords.index');
Route::post('/keywords/save','UserManagementController@saveKeyword')->name('keywords.save');
Route::post('/keywords/delete','UserManagementController@deleteKeyword')->name('keywords.delete');
Route::get('/audit-logs','AuditLogController@index')->name('audit-logs.index');
Route::post('/audit-logs/datatable','AuditLogController@datatable')->name('audit-logs.datatable');
Route::get('/audit-logs/{auditLog}/details','AuditLogController@details')->name('audit-logs.details');
Route::post('/audit-logs/{auditLog}/restore','AuditLogController@restore')->name('audit-logs.restore');
Route::post('/user-location','UserLocationController@store')->name('user-location.store');
Route::post('/updaterecord','Payments@updaterecord');
Route::get('/c2btest','Payments@c2btest');
Route::middleware(['checkIp'])->group(function () {
    Route::post( '/app/c2bvalidation', 'Callbacks@C2BRequestValidation' );
    Route::post( '/app/c2bconfirmation', 'Callbacks@processC2BRequestConfirmation' );
});
Route::get('/email/send','Callbacks@sendEmail');
