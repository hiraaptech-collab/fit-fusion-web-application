<?php
use App\Http\Controllers\Gym\ {
    DashboardController,AccountController,StaffController,StaffOperationsController,MembershipController,FinanceController,FitnessPlanController,ClientController,CommunicationController,AiHistoryController,SettingsController
}
;
use App\Http\Controllers\ {
    PublicDataController,PublicSubmissionController
}
;
use Illuminate\Support\Facades\Route;
Route::get('/api/config',[PublicDataController::class,'configuration']);
Route::get('/api/workouts',[PublicDataController::class,'workouts']);
Route::get('/api/diets',[PublicDataController::class,'diets']);
Route::get('/membership-plans',[PublicDataController::class,'memberships'])->name('public.memberships');
Route::post('/api/contact',[PublicSubmissionController::class,'contact'])->middleware('throttle:6,1');
Route::post('/api/newsletter',[PublicSubmissionController::class,'newsletter'])->middleware('throttle:6,1');
Route::middleware('auth')->group(function() {
    Route::get('/dashboard',[DashboardController::class,'redirect'])->name('dashboard');
    Route::get('/owner/dashboard',[DashboardController::class,'management'])->middleware('role:owner')->name('owner.dashboard');
    Route::get('/manager/dashboard',[DashboardController::class,'management'])->middleware('role:manager')->name('manager.dashboard');
    Route::middleware('role:owner,manager')->prefix('manage')->group(function() {
        Route::get('/accounts/{accountType}',[AccountController::class,'index'])->whereIn('accountType',['clients','managers'])->name('gym.accounts');
        Route::get('/accounts/{accountType}/{record}/edit',[AccountController::class,'edit'])->whereIn('accountType',['clients','managers'])->whereNumber('record')->name('gym.account.edit');
        Route::post('/accounts/{accountType}',[AccountController::class,'store'])->whereIn('accountType',['clients','managers'])->name('gym.account.store');
        Route::put('/accounts/{accountType}/{record}',[AccountController::class,'update'])->whereIn('accountType',['clients','managers'])->whereNumber('record')->name('gym.account.update');
        Route::get('/staff/{staffType}',[StaffController::class,'index'])->whereIn('staffType',['trainers','workers'])->name('gym.staff');
        Route::get('/staff/{staffType}/{record}/edit',[StaffController::class,'edit'])->whereIn('staffType',['trainers','workers'])->whereNumber('record')->name('gym.staff.edit');
        Route::post('/staff/{staffType}',[StaffController::class,'store'])->whereIn('staffType',['trainers','workers'])->name('gym.staff.store');
        Route::put('/staff/{staffType}/{record}',[StaffController::class,'update'])->whereIn('staffType',['trainers','workers'])->whereNumber('record')->name('gym.staff.update');
        Route::delete('/staff/{staffType}/{record}',[StaffController::class,'archive'])->whereIn('staffType',['trainers','workers'])->whereNumber('record')->name('gym.staff.archive');
        Route::get('/staff-operations/{operation}',[StaffOperationsController::class,'index'])->whereIn('operation',['schedules','attendance'])->name('gym.staff-operations');
        Route::post('/schedules',[StaffOperationsController::class,'schedule'])->name('gym.schedule.store');
        Route::put('/schedules/{record}',[StaffOperationsController::class,'schedule'])->whereNumber('record')->name('gym.schedule.update');
        Route::delete('/schedules/{record}',[StaffOperationsController::class,'deleteSchedule'])->middleware('can:staff.schedules')->name('gym.schedule.delete');
        Route::post('/attendance',[StaffOperationsController::class,'attendance'])->name('gym.attendance.store');
    }
    );
}
);
Route::middleware(['auth','role:owner,manager'])->prefix('manage')->group(function() {
    Route::middleware('can:memberships.manage')->group(function() {
        Route::get('/memberships',[MembershipController::class,'index'])->name('gym.memberships');
        Route::post('/memberships',[MembershipController::class,'store'])->name('gym.membership.store');
        Route::post('/memberships/{membership}/renew',[MembershipController::class,'renew'])->name('gym.membership.renew');
        Route::post('/memberships/{membership}/cancel',[MembershipController::class,'cancel'])->name('gym.membership.cancel');
        Route::get('/membership-plans',[MembershipController::class,'plans'])->name('gym.membership-plans');
        Route::post('/membership-plans',[MembershipController::class,'savePlan'])->name('gym.membership-plan.store');
        Route::put('/membership-plans/{record}',[MembershipController::class,'savePlan'])->whereNumber('record')->name('gym.membership-plan.update');
        Route::delete('/membership-plans/{plan}',[MembershipController::class,'archivePlan'])->name('gym.membership-plan.archive');
    }
    );
    Route::get('/finance',[FinanceController::class,'index'])->name('gym.finance');
    Route::post('/payments',[FinanceController::class,'payment'])->name('gym.payment.store');
    Route::put('/payments/{payment}/status',[FinanceController::class,'paymentStatus'])->name('gym.payment.status');
    Route::post('/payments/{payment}/refund',[FinanceController::class,'refund'])->name('gym.payment.refund');
    Route::post('/expenses',[FinanceController::class,'expense'])->name('gym.expense.store');
    Route::get('/fitness-plans/{planType}',[FitnessPlanController::class,'index'])->whereIn('planType',['workouts','diets'])->name('gym.fitness-plans');
    Route::get('/fitness-plans/{planType}/create',[FitnessPlanController::class,'form'])->whereIn('planType',['workouts','diets'])->name('gym.fitness-plan.create');
    Route::get('/fitness-plans/{planType}/{record}/edit',[FitnessPlanController::class,'form'])->whereIn('planType',['workouts','diets'])->whereNumber('record')->name('gym.fitness-plan.edit');
    Route::post('/fitness-plans/{planType}',[FitnessPlanController::class,'save'])->whereIn('planType',['workouts','diets'])->name('gym.fitness-plan.store');
    Route::put('/fitness-plans/{planType}/{record}',[FitnessPlanController::class,'save'])->whereIn('planType',['workouts','diets'])->whereNumber('record')->name('gym.fitness-plan.update');
    Route::delete('/fitness-plans/{planType}/{record}',[FitnessPlanController::class,'archive'])->whereIn('planType',['workouts','diets'])->whereNumber('record')->name('gym.fitness-plan.archive');
    Route::post('/assignments/{planType}',[FitnessPlanController::class,'assign'])->whereIn('planType',['workouts','diets'])->name('gym.assignment.store');
    Route::put('/assignments/{planType}/{assignment}/end',[FitnessPlanController::class,'endAssignment'])->whereIn('planType',['workouts','diets'])->name('gym.assignment.end');
    Route::get('/reports',[DashboardController::class,'report'])->middleware('can:reports.view')->name('gym.reports');
}
);
Route::middleware('auth')->group(function() {
    Route::get('/messages',[CommunicationController::class,'index'])->name('gym.messages');
    Route::post('/messages',[CommunicationController::class,'send'])->middleware('throttle:20,1')->name('gym.message.store');
    Route::put('/messages/{message}/read',[CommunicationController::class,'read'])->name('gym.message.read');
    Route::put('/inquiries/{inquiry}',[CommunicationController::class,'replyInquiry'])->middleware('can:messages.manage')->name('gym.inquiry.reply');
    Route::get('/notifications',[CommunicationController::class,'notifications'])->name('gym.notifications');
    Route::put('/notifications/{notification}/read',[CommunicationController::class,'markNotification'])->name('gym.notification.read');
    Route::middleware('role:owner')->group(function() {
        Route::post('/notifications',[CommunicationController::class,'broadcast'])->name('gym.notification.store');
        Route::get('/gym-settings',[SettingsController::class,'index'])->name('gym.settings');
        Route::put('/gym-settings',[SettingsController::class,'update'])->name('gym.settings.update');
        Route::get('/audit-logs',[SettingsController::class,'audit'])->name('gym.audit');
    }
    );
    Route::middleware('role:client')->prefix('client')->group(function() {
        Route::get('/dashboard',[DashboardController::class,'client'])->name('client.dashboard');
        Route::get('/account-profile',[ClientController::class,'profile'])->name('client.account-profile');
        Route::put('/account-profile',[ClientController::class,'updateProfile'])->name('client.profile.update');
        Route::get('/memberships',[ClientController::class,'memberships'])->name('client.memberships');
        Route::get('/plans',[ClientController::class,'plans'])->name('client.plans');
        Route::get('/payments',[ClientController::class,'payments'])->name('client.payments');
        Route::get('/progress',[ClientController::class,'progress'])->name('client.progress');
        Route::post('/progress',[ClientController::class,'saveProgress'])->name('client.progress.store');
        Route::delete('/progress/{progress}',[ClientController::class,'deleteProgress'])->name('client.progress.delete');
        Route::get('/ai',[AiHistoryController::class,'index'])->name('client.ai');
        Route::get('/ai/{conversation}',[AiHistoryController::class,'index'])->name('client.ai.show');
        Route::post('/ai',[AiHistoryController::class,'send'])->middleware(['throttle:fusion-ai','ai-access'])->name('client.ai.send');
        Route::delete('/ai/{conversation}',[AiHistoryController::class,'destroy'])->name('client.ai.delete');
    }
    );
}
);


Route::get('/ai-usage',[\App\Http\Controllers\Gym\AiUsageController::class,'index'])->middleware(['auth','role:owner'])->name('gym.ai-usage');
