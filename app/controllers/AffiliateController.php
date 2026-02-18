<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Models\Affiliate;

final class AffiliateController extends Controller
{
    public function store(): void
    {
        if (!csrf_verify()) {
            Session::flash('error', 'Sesión expirada (CSRF). Intenta de nuevo.');
            $this->redirect('/#afiliacion');
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        // Sanitización básica
        $name = preg_replace('/\s+/', ' ', $name) ?? '';
        $phone = preg_replace('/[^0-9+\s()-]/', '', $phone) ?? '';

        if ($name === '' || $phone === '' || $email === '' || $message === '') {
            Session::flash('error', 'Completa todos los campos para afiliarte.');
            $this->redirect('/#afiliacion');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Email inválido.');
            $this->redirect('/#afiliacion');
        }

        (new Affiliate())->create(
            mb_substr($name, 0, 120),
            mb_substr($phone, 0, 40),
            mb_substr($email, 0, 120),
            mb_substr($message, 0, 1000),
            Request::ip(),
            Request::userAgent()
        );

        Session::flash('success', 'Gracias. Tu solicitud de afiliación fue enviada.');
        $this->redirect('/#afiliacion');
    }
}

