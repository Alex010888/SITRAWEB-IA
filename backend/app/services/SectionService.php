<?php
/**
 * Section Service
 * 
 * Lógica de negocio para gestión de secciones CMS.
 * Valida, transforma y coordina operaciones sobre secciones.
 */

namespace App\Services;

use App\Models\Section;

class SectionService
{
    private Section $sectionModel;

    public function __construct()
    {
        $this->sectionModel = new Section();
    }

    /**
     * Obtiene todas las secciones
     * 
     * @return array
     */
    public function getAllSections(): array
    {
        return $this->sectionModel->getAll();
    }

    /**
     * Obtiene una sección por key
     * 
     * @param string $key
     * @return array|null
     */
    public function getSectionByKey(string $key): ?array
    {
        $this->validateSectionKey($key);
        return $this->sectionModel->findByKey($key);
    }

    /**
     * Actualiza o crea una sección
     * 
     * @param string $key
     * @param array $content
     * @return bool
     * @throws \RuntimeException Si la validación falla
     */
    public function updateSection(string $key, array $content): bool
    {
        $this->validateSectionKey($key);
        $this->validateContent($content);

        return $this->sectionModel->upsert($key, $content);
    }

    /**
     * Elimina una sección
     * 
     * @param string $key
     * @return bool
     */
    public function deleteSection(string $key): bool
    {
        $this->validateSectionKey($key);
        return $this->sectionModel->delete($key);
    }

    /**
     * Verifica si una sección existe
     * 
     * @param string $key
     * @return bool
     */
    public function sectionExists(string $key): bool
    {
        return $this->sectionModel->exists($key);
    }

    /**
     * Obtiene metadatos de una sección
     * 
     * @param string $key
     * @return array|null Array con metadata (key, last_updated, exists)
     */
    public function getSectionMetadata(string $key): ?array
    {
        $lastUpdated = $this->sectionModel->getLastUpdated($key);

        if (!$lastUpdated) {
            return null;
        }

        return [
            'section_key' => $key,
            'last_updated' => $lastUpdated,
            'exists' => true,
        ];
    }

    /**
     * Valida que el section_key sea válido
     * 
     * Reglas:
     * - Solo letras, números, guiones y guiones bajos
     * - Entre 2 y 50 caracteres
     * - No puede estar vacío
     * 
     * @param string $key
     * @throws \RuntimeException Si el key no es válido
     */
    private function validateSectionKey(string $key): void
    {
        if (empty($key)) {
            throw new \RuntimeException("section_key no puede estar vacío");
        }

        if (strlen($key) < 2 || strlen($key) > 50) {
            throw new \RuntimeException("section_key debe tener entre 2 y 50 caracteres");
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
            throw new \RuntimeException("section_key solo puede contener letras, números, guiones y guiones bajos");
        }
    }

    /**
     * Valida que el contenido sea válido
     * 
     * Reglas:
     * - Debe ser un array
     * - No puede estar vacío
     * - Debe ser serializable a JSON
     * 
     * @param mixed $content
     * @throws \RuntimeException Si el contenido no es válido
     */
    private function validateContent($content): void
    {
        if (!is_array($content)) {
            throw new \RuntimeException("El contenido debe ser un objeto JSON válido");
        }

        if (empty($content)) {
            throw new \RuntimeException("El contenido no puede estar vacío");
        }

        // Verificar que sea serializable a JSON
        $json = json_encode($content);
        if ($json === false) {
            throw new \RuntimeException("El contenido no es serializable a JSON");
        }

        // Verificar que no exceda límites razonables (ej. 1MB)
        if (strlen($json) > 1048576) {
            throw new \RuntimeException("El contenido excede el tamaño máximo permitido (1MB)");
        }
    }

    /**
     * Sanitiza un section_key (útil para slugs)
     * 
     * @param string $input
     * @return string Key sanitizado
     */
    public function sanitizeSectionKey(string $input): string
    {
        // Convertir a minúsculas
        $key = strtolower($input);
        
        // Reemplazar espacios por guiones
        $key = str_replace(' ', '-', $key);
        
        // Remover caracteres no permitidos
        $key = preg_replace('/[^a-z0-9_-]/', '', $key);
        
        // Limitar longitud
        $key = substr($key, 0, 50);
        
        return $key;
    }
}
