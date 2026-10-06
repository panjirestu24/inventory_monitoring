<?php
// ════════════════════════════════════════════════════════════════
//  CEK AUTENTIKASI - Pastikan User Sudah Login
// ════════════════════════════════════════════════════════════════
//  File ini di-include di halaman yang perlu login
//  Contoh: require_once 'auth_check.php';
// ════════════════════════════════════════════════════════════════

session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    // Kalau belum login, redirect ke halaman login
    header('Location: login.php');
    exit;
}

// Kalau sudah login, lanjut ke halaman yang diminta
// Data user bisa diakses lewat $_SESSION
// Contoh:
// - $_SESSION['user_id']
// - $_SESSION['user_name']
// - $_SESSION['user_email']
// - $_SESSION['user_role']
