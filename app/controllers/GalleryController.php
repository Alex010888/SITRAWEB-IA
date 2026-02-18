<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Upload;
use App\Models\Gallery;

final class GalleryController extends Controller
{
    public function index(): void
    {
        $items = (new Gallery())->all();

        $this->render('gallery/index', [
            'title' => 'Galería',
            'items' => $items,
            'flash_success' => Session::flash('success'),
            'flash_error' => Session::flash('error'),
        ]);
    }

    /**
     * Subida de imagen (preparado para futuro panel).
     */
    public function upload(): void
    {
        if (!defined('PUBLIC_GALLERY_UPLOAD') || PUBLIC_GALLERY_UPLOAD !== true) {
            http_response_code(403);
            Session::flash('error', 'Subida deshabilitada. Habilítala cuando exista panel/admin.');
            $this->redirect('/galeria');
        }

        if (!csrf_verify()) {
            Session::flash('error', 'Sesión expirada (CSRF). Intenta de nuevo.');
            $this->redirect('/galeria');
        }

        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '') {
            $title = 'Foto';
        }

        $file = $_FILES['image'] ?? null;
        if (!is_array($file)) {
            Session::flash('error', 'Debes seleccionar una imagen.');
            $this->redirect('/galeria');
        }

        $targetDir = BASE_PATH . '/public/img/gallery';
        $result = Upload::image($file, $targetDir, '/img/gallery');

        if (!$result['ok']) {
            Session::flash('error', $result['error'] ?? 'No se pudo subir la imagen.');
            $this->redirect('/galeria');
        }

        (new Gallery())->create($title, (string)$result['filename']);
        Session::flash('success', 'Imagen agregada a la galería.');
        $this->redirect('/galeria');
    }
}

