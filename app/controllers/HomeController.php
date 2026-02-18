<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Board;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\News;
use Throwable;

final class HomeController extends Controller
{
    public function index(): void
    {
        $news = [];
        $gallery = [];
        $documents = [];
        $board = [];

        $noticiasSection = get_cms_section('noticias');
        if ($noticiasSection !== null && !empty($noticiasSection['items']) && is_array($noticiasSection['items'])) {
            $news = array_slice(
                array_filter($noticiasSection['items'], fn($i) => !empty($i['url'])),
                0,
                6
            );
        } else {
            try {
                $news = (new News())->latest(6);
            } catch (Throwable $e) {
                // Tabla noticias puede no existir si solo se usó el backend API
            }
        }
        try {
            $gallery = (new Gallery())->latest(9);
        } catch (Throwable $e) {
        }
        try {
            $documents = (new Document())->all();
        } catch (Throwable $e) {
        }
        try {
            $board = (new Board())->all();
        } catch (Throwable $e) {
        }

        $directivaSection = get_cms_section('directiva');
        if ($directivaSection !== null && !empty($directivaSection['members']) && is_array($directivaSection['members'])) {
            $board = $directivaSection['members'];
        }

        $aboutSection = get_cms_section('about');

        $this->render('home/index', [
            'title' => 'Inicio',
            'news' => $news,
            'noticias_title' => ($noticiasSection !== null && isset($noticiasSection['title']) && $noticiasSection['title'] !== '') ? $noticiasSection['title'] : 'Noticias',
            'noticias_subtitle' => ($noticiasSection !== null && isset($noticiasSection['subtitle']) && $noticiasSection['subtitle'] !== '') ? $noticiasSection['subtitle'] : 'Comunicados y novedades del sindicato.',
            'gallery' => $gallery,
            'documents' => $documents,
            'board' => $board,
            'directiva_title' => (isset($directivaSection['title']) && $directivaSection['title'] !== '') ? $directivaSection['title'] : 'Directiva',
            'directiva_subtitle' => (isset($directivaSection['subtitle']) && $directivaSection['subtitle'] !== '') ? $directivaSection['subtitle'] : 'Equipo de trabajo y representación.',
            'about_section' => $aboutSection ?? [],
            'flash_success' => Session::flash('success'),
            'flash_error' => Session::flash('error'),
        ]);
    }

    public function notFound(): void
    {
        $this->render('home/404', [
            'title' => '404',
        ], 404);
    }
}

