<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CommunityRequestController;

Route::get('/settings', [SettingController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Community Requests (Solicitudes)
    Route::get('/community-requests/pending-count', [CommunityRequestController::class, 'pendingCount']);
    Route::get('/community-requests', [CommunityRequestController::class, 'index']);
    Route::post('/community-requests/{id}/handle', [CommunityRequestController::class, 'handle']);

    // Notifications (Residents see their own, Admins see admin notes)
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Admin/Master Only Notifications
    Route::post('/admin/notifications/send', [NotificationController::class, 'sendToUser']);

    // Admin/Master Routes
    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::post('/admin/users/{id}/approve', [AdminUserController::class, 'approve']);
    Route::post('/admin/users/{id}/disapprove', [AdminUserController::class, 'disapprove']);
    Route::post('/admin/users/{id}/role', [AdminUserController::class, 'updateRole']);
    Route::post('/admin/users/{id}/properties', [AdminUserController::class, 'updateUserProperties']);
    
    Route::post('/admin/settings', [SettingController::class, 'update']);
    Route::post('/admin/settings/background', [SettingController::class, 'uploadBackground']);

    // Roles & Permissions Management
    Route::get('/admin/roles-permissions', [RolePermissionController::class, 'index']);
    Route::post('/admin/roles', [RolePermissionController::class, 'storeRole']);
    Route::post('/admin/roles/{id}/sync', [RolePermissionController::class, 'updateRolePermissions']);
    Route::delete('/admin/roles/{id}', [RolePermissionController::class, 'destroyRole']);
    Route::post('/admin/permissions', [RolePermissionController::class, 'storePermission']);
    Route::delete('/admin/permissions/{id}', [RolePermissionController::class, 'destroyPermission']);
    Route::post('/admin/users/{id}/sync-permissions', [RolePermissionController::class, 'syncUserPermissions']);
});
