<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\News;

final class NewsController extends Controller
{
    public function index(): void
    {
        $noticiasSection = get_cms_section('noticias');
        if ($noticiasSection !== null && !empty($noticiasSection['items']) && is_array($noticiasSection['items'])) {
            $items = array_filter($noticiasSection['items'], fn($i) => !empty($i['url']));
        } else {
            try {
                $items = (new News())->allPublished();
            } catch (\Throwable $e) {
                $items = [];
            }
        }

        $this->render('news/index', [
            'title' => ($noticiasSection !== null && isset($noticiasSection['title']) && $noticiasSection['title'] !== '') ? $noticiasSection['title'] : 'Noticias',
            'subtitle' => ($noticiasSection !== null && isset($noticiasSection['subtitle']) && $noticiasSection['subtitle'] !== '') ? $noticiasSection['subtitle'] : 'Comunicados y novedades.',
            'items' => $items,
        ]);
    }
}

