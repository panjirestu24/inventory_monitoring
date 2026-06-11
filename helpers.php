<?php
// ════════════════════════════════════════════════════════════════
//  HELPER FUNCTIONS - Role Based Access Control (RBAC)
// ════════════════════════════════════════════════════════════════

// Cek apakah user punya hak akses tertentu
function hasPermission($action) {
    $role = $_SESSION['user_role'] ?? 'viewer';
    
    // Definisi hak akses per role
    $permissions = [
        'admin' => [
            'view', 'create', 'edit', 'delete', 'export', 'manage_users'
        ],
        'operator' => [
            'view', 'create', 'edit', 'export'
        ],
        'viewer' => [
            'view'
        ]
    ];
    
    // Cek apakah role punya permission untuk action ini
    return in_array($action, $permissions[$role] ?? []);
}

// Alias untuk pengecekan cepat
function canView() { return hasPermission('view'); }
function canCreate() { return hasPermission('create'); }
function canEdit() { return hasPermission('edit'); }
function canDelete() { return hasPermission('delete'); }
function canExport() { return hasPermission('export'); }

// Get user role untuk JavaScript
function getUserRole() {
    return $_SESSION['user_role'] ?? 'viewer';
}

// Get user info
function getUserInfo() {
    return [
        'id' => $_SESSION['user_id'] ?? 0,
        'name' => $_SESSION['user_name'] ?? 'Guest',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'viewer',
    ];
}
